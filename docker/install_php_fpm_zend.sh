#!/usr/bin/sh
. /tmp/secrets.sh
echo "Configuring PHP-FPM ..."
/usr/local/bin/zendphpctl ext install fpm
sed -i 's/user\ \=\ zendphp/user\ \=\ www-data/g' /etc/php/$PHP_VER-zend/fpm/pool.d/www.conf
sed -i 's/group\ \=\ zendphp/group\ \=\ www-data/g' /etc/php/$PHP_VER-zend/fpm/pool.d/www.conf
sed -i "s/\/run\/php\/php$PHP_VER\-zend\-fpm\.sock/0\.0\.0\.0\:9000/g" /etc/php/$PHP_VER-zend/fpm/pool.d/www.conf
