#!/bin/bash
if [[ ! "$USER" = "root" ]]; then
    echo "You need to run this as root!"
    exit 1
fi
cp docker/* /tmp/
chmod +x /tmp/*.sh
. /tmp/secrets.sh
echo "Installing misc tools ..."
apt-get update
apt-get install -y net-tools zip unzip apt-utils tree mysql-client
echo "Installing nginx ..."
apt-get install -y nginx
echo "Installing PHP via ZendPHP ..."
/tmp/install_zendphp.sh
echo "Adding/enabling PHP extensions ..."
/tmp/install_php_ext.sh
/usr/local/bin/zendphpctl ext install mysql
/usr/local/bin/zendphpctl ext install pdo_mysql
echo "Configuring PHP-FPM ..."
sed -i "s/listen = \/run\/php\/php$PHP_VER-zend-fpm\.sock/listen\ \=\ 127\.0\.0\.1\:9000/g" /etc/php/$PHP_VER-zend/fpm/pool.d/www.conf
echo "Copying files to /var/www/demo ..."
mkdir /var/www/demo
cp -r * /var/www/demo
chown -R $NGINX_USR /var/www/demo
echo "Configuring nginx ..."
rm -f /etc/nginx/sites-enabled/*
cp -f /tmp/*.conf /etc/nginx/sites-available/
ln -s -f /etc/nginx/sites-available/nginx.default.conf /etc/nginx/sites-enabled/default
/etc/init.d/nginx restart
echo "If you want to add additional countries to the postcode database, proceed as follows:"
echo "    /var/www/demo/src/import_postcode.sh ISO2" 
echo "    -- where 'ISO2' is the uppercase 2-digit country code"
