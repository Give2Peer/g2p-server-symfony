#!/bin/sh

# Change to project directory
SCRIPT=$(readlink -f $0)
SCRIPTPATH=$(dirname $SCRIPT)
cd $SCRIPTPATH
cd ..

sudo setfacl -R -m u:www-data:rwx -m u:`whoami`:rwx app/cache app/logs
sudo setfacl -dR -m u:www-data:rwx -m u:`whoami`:rwx app/cache app/logs
sudo setfacl -R -m u:www-data:rwx -m u:`whoami`:rwx web
sudo setfacl -dR -m u:www-data:rwx -m u:`whoami`:rwx web

echo "RWX permissions set on cache and web directories !"