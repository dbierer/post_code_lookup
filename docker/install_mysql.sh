#!/usr/bin/sh
echo "Installing Mariadb (MySQL open source equivalent) ..."
apt-get update
apt-get install -y mariadb-server
/etc/init.d/mariadb restart
sleep 3
. /tmp/secrets.sh
/usr/bin/mysql -uroot -ppassword -v -e "CREATE DATABASE IF NOT EXISTS $DB_NAM;"
/usr/bin/mysql -uroot -ppassword -v -e "CREATE USER IF NOT EXISTS '$DB_USR'@'$DB_HOST' IDENTIFIED BY '$DB_PWD';"
/usr/bin/mysql -uroot -ppassword -v -e "GRANT ALL PRIVILEGES ON *.* TO '$DB_USR'@'$DB_HOST';"
/usr/bin/mysql -uroot -ppassword -v -e "CREATE USER IF NOT EXISTS '$DB_USR'@'localhost' IDENTIFIED BY '$DB_PWD';"
/usr/bin/mysql -uroot -ppassword -v -e "GRANT ALL PRIVILEGES ON *.* TO '$DB_USR'@'localhost';"
/usr/bin/mysql -uroot -ppassword -v -e "FLUSH PRIVILEGES;"
