#!/bin/bash
echo "Usage: zendphp_setup.sh NGINX_USER PHP_VER"
if [[ ! "$USER" = "root" ]]; then
    echo "You need to run this as root (hint: `sudo zendphp_setup.sh`)"
    exit 1;
fi
if [[ "$1" = "" ]]; then
    echo "Enter the name of the nginx user: "
    cat /etc/nginx/nginx.conf |grep user
    exit 1;
fi
if [[ "$2" = "" ]]; then
    echo "Enter the major and minor PHP version (e.g. 8.4): "
    php -v
    exit 1;
fi
export NGINX_USER=$1
export PHP_VER=$2
echo "Adding/enabling PHP extensions ..."
/usr/local/bin/zendphpctl ext-install sqlite3
/usr/local/bin/zendphpctl ext-install pdo_sqlite
echo "Copying files to /var/www/demo ..."
mkdir /var/www/demo
cd ..
cp -r * /var/www/demo
chown -R $NGINX_USR /var/www/demo
echo "Configuring nginx ..."
rm -f /etc/nginx/sites-enabled/*
cp -f nginx.default.conf /etc/nginx/sites-available/nginx.default.conf
ln -s -f /etc/nginx/sites-available/nginx.default.conf /etc/nginx/sites-enabled/default
/etc/init.d/nginx restart
echo "Configuring PHP-FPM ..."
sed -i "s/listen = \/run\/php\/php"$PHP_VER"-zend-fpm\.sock/listen\ \=\ 127\.0\.0\.1\:9000/g" /etc/php/"$PHP_VER"-zend/fpm/pool.d/www.conf
/etc/init.d/php"$PHP_VER"-zend-fpm restart
echo "If you want to add additional countries to the postcode database, proceed as follows:"
echo "    /var/www/demo/src/import_postcode.sh ISO2" 
echo "    -- where 'ISO2' is the uppercase 2-digit country code"

