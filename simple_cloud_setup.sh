#!/bin/bash
cp docker/* /tmp/
chmod +x /tmp/*.sh
. /tmp/secrets.sh
echo "Installing misc tools ..."
apt-get update
apt-get install -y nano less vim net-tools wget unzip apt-utils tree curl git
echo "Installing nginx ..."
apt-get install -y nginx
echo "Installing PHP via ZendPHP ..."
/tmp/install_zendphp.sh
echo "Adding/enabling PHP extensions ..."
/tmp/install_php_ext.sh
echo "Configuring PHP-FPM ..."
sed -i "s/listen = \/run\/php\/php$PHP_VER-zend-fpm\.sock/listen\ \=\ 127\.0\.0\.1\:9000/g" /etc/php/$PHP_VER-zend/fpm/pool.d/www.conf
echo "Copying files to /var/www/demo ..."
mkdir /var/www/demo
cp -r /home/ubuntu/post_code_lookup/* /var/www/demo
chown -R $NGINX_USR /var/www/demo
echo "Setting up password protection for Adminer ..."
echo $ADMINER_USR':$apr1$W3uxAT1n$gsmoEIYGrqQ5tPZCKE.fp1' > /etc/nginx/htpasswd/post_code_lookup_admin.htpasswd
echo "Configuring nginx ..."
rm -f /etc/nginx/sites-enabled/*
cp -f /tmp/*.conf /etc/nginx/sites-available/
ln -s -f /etc/nginx/sites-available/nginx.default.conf /etc/nginx/sites-enabled/default
/etc/init.d/nginx restart
echo "If you want to add additional countries to the postcode database, proceed as follows:"
echo "    src/import_postcode.sh ISO2" 
echo "    -- where 'ISO2' is the uppercase 2-digit country code"

