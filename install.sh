#!/bin/sh

rsync -avzh ./app/addons/ $1
rsync -avzh ./app/var/ $1
cp esi.php $1
