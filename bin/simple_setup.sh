#!/bin/bash
# Abort on the first failed command
set -e
export USER=`whoami`
if [[ ! "$USER" = "root" ]]; then
    echo "You need to run this as root!"
    exit 1
fi
if [[ "$1" = "" ]]; then
    echo "Usage: simple_setup.sh NAME_OF_USER"
    exit 1
fi
export DIR=`pwd`
if [[ ! -d docker ]]; then
    export DIR="$(dirname "$DIR")"
fi
if [[ ! -d "$DIR"/docker ]]; then
    echo "$DIR"
    echo "You need to run this script from the demo project root folder, or from the 'bin' folder"
    exit 1
fi
cp "$DIR"/docker/* /tmp/
chmod +x /tmp/*.sh
. /tmp/secrets.sh $1
# Prevent apt/debconf prompts (e.g. tzdata asking for a region)
export DEBIAN_FRONTEND=noninteractive
export TZ=${TZ:-Etc/UTC}
ln -fs /usr/share/zoneinfo/$TZ /etc/localtime
echo "$TZ" > /etc/timezone
echo "Installing misc tools ..."
apt-get update
apt-get install -y net-tools zip unzip apt-utils tree curl
echo "Installing nginx ..."
apt-get install -y nginx
echo "Installing PHP via ZendPHP ..."
/tmp/install_zendphp.sh $1
echo "Adding/enabling PHP extensions ..."
/tmp/install_php_ext.sh $1
echo "Configuring PHP-FPM ..."
sed -i "s/listen = \/run\/php\/php$PHP_VER-zend-fpm\.sock/listen\ \=\ 127\.0\.0\.1\:9000/g" /etc/php/"$PHP_VER"-zend/fpm/pool.d/www.conf
echo "Copying files to /var/www/demo ..."
mkdir -p /var/www/demo
cp -r "$DIR"/* /var/www/demo
chown -R $NGINX_USR /var/www/demo
echo "Configuring nginx ..."
rm -f /etc/nginx/sites-enabled/*
cp -f /tmp/*.conf /etc/nginx/sites-available/
ln -s -f /etc/nginx/sites-available/nginx.default.conf /etc/nginx/sites-enabled/default
echo "Restarting nginx and PHP-FPM ..."
/etc/init.d/nginx restart
/etc/init.d/php"$PHP_VER"-zend-fpm restart
echo "If you want to add additional countries to the postcode database, proceed as follows:"
echo "    /var/www/demo/src/import_postcode.sh ISO2" 
echo "    -- where 'ISO2' is the uppercase 2-digit country code"
