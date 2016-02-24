**************************************
Full-Page Cache Add-on for CS-Cart 4.3
**************************************

.. contents::
    :local:
    :depth: 4
    :backlinks: none

=======================
Configuring Environment
=======================

For full-page caching to work, configure the environment accordingly. See how the server components work in the picture below:

.. image:: http://i.imgur.com/Bf2MnyW.png
    :align: center
    :alt: Varnish serves as a reverse proxy between clients and the web server.

------------------------------------
Manual Configuration on Ubuntu 14.04
------------------------------------

The add-on requires Varnish to be installed on your server.

**Note:** The add-on supports **Varnish 4.0.x only.** Version **4.1.x is not supported**.

Install Varnish on Ubuntu 14.04 by running the following commands in the Terminal:

.. code-block:: bash

  sudo apt-get install apt-transport-https
  curl https://repo.varnish-cache.org/GPG-key.txt | apt-key add -
  sudo echo "deb https://repo.varnish-cache.org/ubuntu/ trusty varnish-4.0" >> /etc/apt/sources.list.d/varnish-cache.list
  sudo apt-get update
  sudo apt-get install varnish

Learn more about installing Varnish on other operating systems at `Varnish website <https://www.varnish-cache.org/releases>`_.

Varnish must listen on **port 80** and accept all incoming HTTP connections instead of Apache or Nginx. Configure your web server to listen on **port 8082**. When a client requests non-cached data, Varnish will request static pages or processed PHP from the web server through **port 8082**.

After you install Varnish, configure it. There are two configuration files:

* Varnish daemon settings (located in ``/etc/default/varnish`` on Ubuntu)

* Caching settings (located in ``/etc/varnish/default.vcl`` on Ubuntu)

  **Note:** The **default.vcl** file is written in `Varnish Configuration Language <https://www.varnish-cache.org/docs/2.1/tutorial/vcl.html>`_.

The ``/etc/default/varnish`` file should have the following content:

::

  # Should we start varnishd at boot?  Set to "no" to disable.
  START=yes

  # Maximum number of open files (for ulimit -n)
  NFILES=131072

  # Maximum locked memory size (for ulimit -l)
  # Used for locking the shared memory log in memory.  If you increase log size,
  # you need to increase this number as well
  MEMLOCK=82000

  # Default varnish instance name is the local nodename.  Can be overridden with
  # the -n switch, to have more instances on a single server.
  # INSTANCE=$(uname -n)

  # Main configuration file.
  VARNISH_VCL_CONF=/etc/varnish/default.vcl

  # Default address and port to bind to
  # Blank address means all IPv4 and IPv6 interfaces, otherwise specify
  # a host name, an IPv4 dotted quad, or an IPv6 address in brackets.
  VARNISH_LISTEN_ADDRESS=
  VARNISH_LISTEN_PORT=80

  # Telnet admin interface listen address and port
  VARNISH_ADMIN_LISTEN_ADDRESS=127.0.0.1
  VARNISH_ADMIN_LISTEN_PORT=6082

  # The minimum number of worker threads to start
  VARNISH_MIN_THREADS=1

  # The Maximum number of worker threads to start
  VARNISH_MAX_THREADS=1000

  # Idle timeout for worker threads
  VARNISH_THREAD_TIMEOUT=120

  # Cache file location
  VARNISH_STORAGE_FILE=/var/lib/varnish/$INSTANCE/varnish_storage.bin

  # Cache file size: in bytes, optionally using k / M / G / T suffix,
  # or in percentage of available disk space using the % suffix.
  VARNISH_STORAGE_SIZE=1G

  # File containing administration secret
  VARNISH_SECRET_FILE=/etc/varnish/secret

  # Backend storage specification
  VARNISH_STORAGE="file,${VARNISH_STORAGE_FILE},${VARNISH_STORAGE_SIZE}"

  # Default TTL used when the backend does not specify one
  VARNISH_TTL=120

  # DAEMON_OPTS is used by the init script.  If you add or remove options, make
  # sure you update this section, too.
  DAEMON_OPTS="-a ${VARNISH_LISTEN_ADDRESS}:${VARNISH_LISTEN_PORT} \
               -T ${VARNISH_ADMIN_LISTEN_ADDRESS}:${VARNISH_ADMIN_LISTEN_PORT} \
               -t ${VARNISH_TTL} \
               -f ${VARNISH_VCL_CONF} \
               -p thread_pool_min=${VARNISH_MIN_THREADS} \
               -p thread_pool_max=${VARNISH_MAX_THREADS} \
               -p thread_pool_timeout=${VARNISH_THREAD_TIMEOUT} \
               -S ${VARNISH_SECRET_FILE} \
               -s ${VARNISH_STORAGE}"

Configure ``/etc/varnish/default.vcl`` as follows:

::

  vcl 4.0;

  backend default {
      .host = "127.0.0.1";
      .port = "8082";
  }

  sub vcl_recv {
      return (pass);
  }

  sub vcl_backend_response {
      set beresp.ttl = 0s;
      set beresp.uncacheable = true;
  }

  sub vcl_deliver {
      set resp.http.X-Varnish-Disabled = true;
  }

**Note:** This default configuration will be used on server startup to redirect HTTP requests to the web server listening on port 8082. **This alone won’t enable caching**. When you enable the **Full-Page Cache** add-on, it will automatically generate the VCL file required for the caching to work.

After you configure Varnish and your web server, restart them:

.. code-block:: bash

  sudo service nginx restart ## or sudo service apache2 restart
  sudo service varnish restart

---------------------------------------------------------
Automatic Environment Configuration with Ansible Playbook
---------------------------------------------------------

If you have a clean operating system installation on your server, you can install and configure **Varnish**, **Nginx**, **MySQL** and **PHP 7** with a few commands.

^^^^^^^^^^^^^^^^^^^^^^^^^^
Install Ansible (v. 1.9.x)
^^^^^^^^^^^^^^^^^^^^^^^^^^

Depending on your server’s operating system, run one of these sets of commands to install Ansible:

* Ubuntu

  ::

    sudo apt-get -y update
    sudo apt-get -y install git python-pip python-dev
    sudo pip install ansible

* CentOS 6

  ::

    sudo rpm -Uvh https://dl.fedoraproject.org/pub/epel/epel-release-latest-6.noarch.rpm
    sudo yum install -y gcc python-pip python-devel git
    sudo pip install ansible

* CentOS 7

  ::

    sudo rpm -Uvh https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm
    sudo yum install -y gcc python-pip python-devel git
    sudo pip install ansible

^^^^^^^^^^^^
Run Playbook
^^^^^^^^^^^^

Once you have installed Ansible, you can download and run our playbook (scenario) to configure the server. Follow these steps:

1. Download the repository:

   .. code-block:: bash

     mkdir ~/scenarios && git clone https://github.com/cscart/server-ansible-playbooks.git ~/scenarios

2. Create a file with your configuration:

   ::

     cp ~/scenarios/config/advanced.json  ~/scenarios/config/main.json

3. Modify the settings in ``~/scenarios/config/main.json``:

   * **stores_dir**—your project directory
   * **stores**—an array of projects
   * **example.com**—the domain name of a project
   * **storefronts**—an array with the domain names of the storefronts
   * **database**—the credentials of the database that will be created by the playbook

4. Run the playbook to configure the environment:

   ::

     cd ~/scenarios/ && ansible-playbook -e @config/main.json -c local -i inventory_varnish lvemp7.yml

===================================
Working with Full-Page Cache Add-on
===================================

After you configure the environment, Varnish will listen to all incoming connections on port 80 and serve as a reverse proxy for the web server without caching anything.

Now you can install CS-Cart, if you haven’t done it yet.

**Note:** The **Full-Page Cache** add-on supports **CS-Cart 4.3.6 and higher**. It doesn’t support Multi-Vendor and earlier versions of CS-Cart.

---------------------------------------------
Installing Full-Page Cache Add-on from GitHub
---------------------------------------------

1. Go to `the repository of the Full-Page Cache add-on <https://github.com/cscart/full-page-cache-addon>`_.

2. Click the **Download ZIP** button in the top right corner of the file list.

3. Unpack the add-on into the ``full-page-cache-addon-dev`` folder.

4. Copy all the files from ``full-page-cache-addon-dev`` into the root directory of your CS-Cart installation.

5. By default, the add-on is not installed. Open the Administration panel of your store, go to **Add-ons→Manage Add-ons**, switch to the **Browse all available add-ons** tab, find **Full-page cache** and click **Install**.

6. Go back to the **Installed add-ons** tab and click **Full-page cache**. This will open the add-on’s settings where you need to enter the secret token. You can find the token in ``/etc/varnish/secret``.

7. Activate the add-on to enable full-page caching. To disable full-page caching, just disable the add-on.

**Note:** If the environment isn’t configured properly, you won’t be able to activate the add-on and you’ll see an error notification.

---------------------------
Full-Page Caching Specifics
---------------------------

The URL of the requested page serves as the cache key. Different users can receive pages that are fully or partly cached, or not cached at all:

* **Guests** don't have a cookie containing PHP session ID and receive fully cached store pages from memory without the need for PHP to generate any content.

* **Guests with a session started** have a session cookie and get only the main content of the pages from the cache. The main content is session-independent. Varnish loads the content of session-dependent blocks (like Cart content or Wishlist) dynamically via ESI from PHP backend when building the page before sending it to the client.

* **Users who are logged in** don’t get any content from the cache—the PHP backend handles their requests directly, without any interruptions from Varnish.

**Note:** The caching won’t work for the Administration panel and the REST API.

When you enable the Full-Page Cache add-on:

* The automatic session startup is disabled. A session starts only if a user performs a POST request, or if there already is a cookie with the session ID.

* The ``fpc_`` prefix is added to the name of the cookie that stores the session ID. That way the users who were logged in before the add-on was enabled would have to log in again. See the next paragraph for reasons.

* When a user logs in, the ``disable_cache=Y`` cookie is set. This cookie is deleted when the user logs out.

* A VCL file with the CS-Cart caching logic is generated. We will refer to that file as **enabling-VCL**.

* Varnish restarts, using the newly-generated **enabling-VCL**.

When you disable the Full-Page Cache add-on:

* Varnish restarts using the VCL file that makes Varnish act as a reverse proxy and pass all requests to the backend on port 8082 without interruptions and caching. We will refer to that file as **disabling-VCL**.

* When the add-on is disabled, **disabling-VCL** adds the ``X-Varnish-Disabled: true`` title to all HTTP responses for debugging purposes.

* When the add-on is enabled, **enabling-VCL** adds debug headers to all HTTP responses:

  * ``Age``—the age of the cache record in seconds. This cannot be more than TTL of the cache records (see **Cache Invalidation** for details).

  * ``X-Varnish-Hits``—the number of times this page was retrieved from the cache. If this number doesn’t increase when the page is refreshed, then the page isn’t cached.

  * ``X-Req-Host``—the hostname requested by a client.

  * ``X-Req-URL``—the URL of the request.

  * ``X-Varnish-Disable-Cache``—can be either *true*, or empty. If *true*, then the user is logged in, and the cache shouldn’t work for that user.

  * ``X-Has-Session``—can be either *true*, or empty. If true, then the user has a cookie with a session ID. In that case Varnish will provide the main content of the page from the cache. The blocks that have the ``session_dependent`` flag will be loaded dynamically from the web server.

  * ``X-Req-Cookie``—contains the cookies sent by a client.

To generate **enabling-VCL**, the Full-Page Cache addon uses the following schema: **app/addons/full_page_cache/schemas/full_page_cache/varnish.php**. It contains the paths, locations and extensions of the files that shouldn’t be cached.

The **enabling-VCL** file must be generated again when:

* any add-on is enabled/disabled;

* the settings of any add-on are changed;

* the system settings are changed;

* a storefront is changed or added (storefront data is taken into account when generating enabling-VCL);

* SEO names are changed (SEO names are used to replace non-cached locations like controller.mode with URL-path in enabling-VCL).

**Enabling-VCL** is generated quickly, but Varnish must also reload it as a new configuration. To avoid problems, Varnish must restart after this. However, restarting cuts off the live HTTP connections and it appears as if there’s no response from the server.

That’s why whenever **enabling-VCL** must be generated again, the site administrator will see a notification asking to disable and reenable the add-on.

------------------------------------
Parameters Moved from Session to URL
------------------------------------

Some parameters, such as the selected language and currency, are stored in the session. However, with the full-page caching turned on, a session shouldn’t start up when a user changes a language or currency, or else we won’t be able to cache the page for different languages and currencies.

That’s why the ``sl`` and ``currency`` request parameters are now added to all internal links. That way all URLs have the information about the language and the currency of the pages they refer to.

Since **URL is the only key for the full-page cache**, a page can have multiple cache entries for different combinations of languages and currencies. There is no need to start the session when a user selects other language or currency.

If a standard language or currency of the storefront are selected, the corresponding parameters won’t be added to the URL.

**Note:** If you have the **SEO** add-on installed and active, and the **Show language in the URL** setting is enabled, the additional ``sl`` parameter won’t be added to the URL.

---------------
Important Notes
---------------

* A session will start up only when a user performs an action through a POST request, for example:

  * adding a product to the cart;

  * adding a product to the comparison list;

  * logging in on the website;

  * and some other actions.

* For now caching only works for one storefront. If you have more than one storefront, you can choose which storefront to cache, otherwise the first storefront on the list will be cached. When you enable the **Full-Page Cache** add-on, you’re notified what storefront will be cached.

* Automatic language detection doesn’t work with the beta-version of the **Full Page Cache** add-on. Here’s why:

  * Suppose we have English and Russian languages installed.

  * English is the default language of the customer area.

  * Use a browser with Russian locale to open the storefront with full-page caching enabled. Don’t add any GET parameters, just use the standard URL like http://demo.cs-cart.com.

  * The browser sends the following header: ``Accept-Language:ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4,de;q=0.2,fr;q=0.2,nl;q=0.2``

  * Varnish sends the request to the PHP backend.

  * Without a session ID and the ``sl`` parameter CS-Cart determines the language as Russian based on the ``Accept-Language`` header and generates the page for Varnish.

  * If the cache is empty, Varnish stores this page in the cache for http://demo.cs-cart.com.

  * Then any other user with any other locale will see a page in Russian when opening http://demo.cs-cart.com.

  **Temporary workaround:** Varnish removes the ``Accept-Language`` header before sending the request to CS-Cart, so the storefront uses the default language.

  **Future behavior:** Varnish will parse the ``Accept-Language`` header and add the ``sl`` parameter with the necessary value to all the URLs before sending the request to CS-Cart. This requires inserting some code written in C into the VCL file.

------------------
Cache Invalidation
------------------

There isn’t much need in cache invalidation: every object in Varnish cache has **time to live (TTL)**, which is 90 seconds by default. After that any cached page is invalidated automatically.

This is how cache invalidation works in Varnish:

* PHP passes an HTTP header like ``X-Cache-Tags: qwe,asd,foobar`` when rendering the page.

* Varnish saves the received page with all the headers to the cache.

* The **varnishadm** utility listens for connections on a certain port, which can be specified in the add-on’s settings. When the cache must be invalidated by a certain tag, the utility receives the ``ban`` command from CS-Cart. It looks like this:

  ::

    ban req.http.X-Cache-Tags ~ "qwe"

* After that Varnish invalidates all the entries in the cache with the corresponding ``X-Cache-Tags`` content. In our case the entries with the ``qwe`` tag will be invalidated.

This is how the cache tags are generated:

* A page consists of blocks that take the data from the database.

* Full-page caching only covers session-independent data. The data dependent on session is loaded via ESI when necessary.

* Therefore, the full-page cache only depends on the database tables and uses the table names as the tags that end up in the ``X-Cache-Tags`` header. For now the table-cache dependencies only exist for the blocks that are cached using standard CS-Cart mechanisms. That applies to the **Main Content** block for the ``products.view`` and ``categories.view`` locations. Only the data on these pages will be invalidated properly.

===============
Possible Issues
===============

*When you open a URL that has a path without a slash in the end, you are redirected to port 8082 and get the following error message: "No storefronts defined for this domain."*

**Cause:** By default, when the URL is like http://example.com/path, the Apache web server returns a 301 redirect to http://example.com/path/ (with a trailing slash).

When building the redirect destination URL, Apache considers the ``UseCanonicalName`` and ``UseCanonicalPhysicalPort`` settings. When they are set to *On*, Apache will use Hostname and Port specified in the virtual host settings.

If ``UseCanonicalName`` and ``UseCanonicalPhysicalPort`` are set to *Off*, Apache will take Hostname and Port from the HTTP headers sent by the browser.

In our case the virtual host has **port 8082** specified, and clients address to **port 80**. When building the redirect destination URL, Apache uses the virtual host settings.

**Solution:** Set ``UseCanonicalName`` and ``UseCanonicalPhysicalPort`` to *Off* in the virtual host settings.
