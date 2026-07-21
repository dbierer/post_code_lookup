#!/usr/bin/sh
. /tmp/secrets.sh
cd /tmp
echo "Installing PHP ..."
add-apt-repository ppa:ondrej/php
sudo apt update
apt-get install -y php"$PHP_VER"-cli
apt-get install -y \
    php"$PHP_VER"-gd \
    php"$PHP_VER"-xml \
    php"$PHP_VER"-intl \
    php"$PHP_VER"-mbstring \
    php"$PHP_VER"-mysql \
	php"$PHP_VER"-sqlite3 \
	php"$PHP_VER"-pdo \
	php"$PHP_VER"-fpm
sed -i 's/display_errors\ \=\ Off/display_errors\ \=\ On/g' /etc/php/$PHP_VER/cli/php.ini
sed -i 's/display_errors\ \=\ Off/display_errors\ \=\ On/g' /etc/php/$PHP_VER/fpm/php.ini
sed -i 's/\;error_log\ \=\ syslog/error_log\ \=\ \/var\/log\/php_errors.log/g' /etc/php/$PHP_VER/cli/php.ini
sed -i 's/\;error_log\ \=\ syslog/error_log\ \=\ \/var\/log\/php_errors.log/g' /etc/php/$PHP_VER/fpm/php.ini
touch /var/log/php_errors.log
sed -i "s/\/run\/php\/php$PHP_VER\-fpm\.sock/0\.0\.0\.0\:9000/g" /etc/php/$PHP_VER/fpm/pool.d/www.conf
/etc/init.d/php"$PHP_VER"-fpm restart
