#!/bin/sh

rysnc -avzh ./app/addons/ $1
rysnc -avzh ./app/var/ $1
cp esi.php $1
