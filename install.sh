#!/bin/sh

rsync -avzh ./app/addons/ $1/app/addons
rsync -avzh ./var/ $1/var
cp esi.php $1
