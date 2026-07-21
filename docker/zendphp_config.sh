#!/usr/bin/sh
. /tmp/secrets.sh
echo "Configuring ZendPHP + FPM ..."
sed -i 's/display_errors\ \=\ Off/display_errors\ \=\ On/g' /etc/php/$PHP_VER-zend/cli/php.ini
sed -i 's/display_errors\ \=\ Off/display_errors\ \=\ On/g' /etc/php/$PHP_VER-zend/fpm/php.ini
sed -i 's/\;error_log\ \=\ syslog/error_log\ \=\ \/var\/log\/php_errors.log/g' /etc/php/$PHP_VER-zend/cli/php.ini
sed -i 's/\;error_log\ \=\ syslog/error_log\ \=\ \/var\/log\/php_errors.log/g' /etc/php/$PHP_VER-zend/fpm/php.ini
sed -i 's/user\ \=\ zendphp/user\ \=\ www-data/g' /etc/php/$PHP_VER-zend/fpm/pool.d/www.conf
sed -i 's/group\ \=\ zendphp/group\ \=\ www-data/g' /etc/php/$PHP_VER-zend/fpm/pool.d/www.conf
sed -i "s/listen = \/run\/php\/php$PHP_VER-zend-fpm\.sock/listen\ \=\ 127\.0\.0\.1\:9000/g" /etc/php/$PHP_VER-zend/fpm/pool.d/www.conf
