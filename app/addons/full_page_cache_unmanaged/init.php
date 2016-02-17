<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

/** @var \Tygh\Application $application */
$application = Tygh::$app;

if (AREA == 'C') {
    $application->extend('session', function (\Tygh\Web\Session $session, \Tygh\Application $app) {
        $session->setSessionNamePrefix(
            'fpc_' . $session->getSessionNamePrefix()
        );

        $session->setName(ACCOUNT_TYPE);

        $session->start_on_init = false;
        $session->start_on_read = $session->requestHasSessionID();
        $session->start_on_write = $session->requestHasSessionID()
            || (fn_strtolower($_SERVER['REQUEST_METHOD']) == 'post');

        return $session;
    });
}