#!/bin/bash
cp docker/* /tmp/
chmod +x /tmp/*.sh
echo "Installing misc tools ..."
apt-get update
apt-get install -y nano less vim net-tools wget unzip apt-utils tree curl git
echo "Installing Mariadb (MySQL open source equivalent) ..."
/tmp/install_mysql.sh
echo "Installing nginx ..."
apt-get install -y nginx
echo "Installing PHP via ZendPHP ..."
/tmp/install_zendphp.sh
echo "Adding/enabling PHP extensions ..."
/tmp/install_php_ext.sh
echo "If you want to set up the postcode database, proceed as follows:"
echo "    1. Modify the settings in config/config.php to match your database settings."
echo "    2. Run src/import_postcode.sh"
