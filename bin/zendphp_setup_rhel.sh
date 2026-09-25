#!/bin/bash
if [[ ! "$USER" = "root" ]]; then
    echo "You need to run this as root (hint: `sudo zendphp_setup.sh`)"
    exit 1;
fi
export AZ_USR=azureuser
export NGINX_USR=nginx
export RHEL_PHP_VER=81
cp /home/$AZ_USR/docker/*.conf /tmp/
echo "Adding/enabling PHP extensions ..."
/usr/local/bin/zendphpctl ext-install sqlite3
/usr/local/bin/zendphpctl ext-install pdo_sqlite
echo "Copying files to /var/www/demo ..."
mkdir /var/www/demo
cp -r * /var/www/demo
chown -R $NGINX_USR /var/www/demo
echo "Configuring nginx ..."
rm -f /etc/nginx/sites-enabled/*
cp -f /tmp/*.conf /etc/nginx/sites-available/
ln -s -f /etc/nginx/sites-available/nginx.default.conf /etc/nginx/sites-enabled/default
systemctl restart nginx
echo "Configuring PHP-FPM ..."
sed -i "s/listen\ \=\ \/var\/opt\/zend\/php"$RHEL_PHP_VER"zend\/run\/php-fpm\/www\.sock/listen\ \=\ 127\.0\.0\.1\:9000/g" /etc/opt/zend/php"$RHEL_PHP_VER"zend/php-fpm.d/www.conf 
systemctl restart php"$RHEL_PHP_VER"zend-php-fpm.service 
echo "If you want to add additional countries to the postcode database, proceed as follows:"
echo "    /var/www/demo/src/import_postcode.sh ISO2" 
echo "    -- where 'ISO2' is the uppercase 2-digit country code"

