#!/bin/bash
if [[ ! "$USER" = "root" ]]; then
    echo "You need to run this as root (hint: `sudo zendphp_setup.sh`)"
    exit 1;
fi
cp docker/* /tmp/
chmod +x /tmp/*.sh
. /tmp/secrets.sh
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
/etc/init.d/nginx restart
echo "Configuring PHP-FPM ..."
sed -i "s/listen = \/run\/php\/php$AWS_PHP_VER-zend-fpm\.sock/listen\ \=\ 127\.0\.0\.1\:9000/g" /etc/php/$AWS_PHP_VER-zend/fpm/pool.d/www.conf
/etc/init.d/php restart
echo "If you want to add additional countries to the postcode database, proceed as follows:"
echo "    /var/www/demo/src/import_postcode.sh ISO2" 
echo "    -- where 'ISO2' is the uppercase 2-digit country code"
echo "When finished, copy the file `data/post_code_lookup.sqlite` to /var/www/demo/data/post_code_lookup.sqlite"


