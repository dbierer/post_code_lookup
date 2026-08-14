#!/bin/bash
. /tmp/secrets.sh
echo "Setting up database and creating database user ..."
mysql -uroot -ppassword -v -e "CREATE DATABASE IF NOT EXISTS $DB_NAM;" 
mysql -uroot -ppassword -v -e "CREATE USER IF NOT EXISTS '$DB_USR'@'$DB_HOST' IDENTIFIED BY '$DB_PWD';" 
mysql -uroot -ppassword -v -e "GRANT ALL PRIVILEGES ON *.* TO '$DB_USR'@'$DB_HOST';" 
mysql -uroot -ppassword -v -e "CREATE USER IF NOT EXISTS '$DB_USR'@'localhost' IDENTIFIED BY '$DB_PWD';" 
mysql -uroot -ppassword -v -e "GRANT ALL PRIVILEGES ON *.* TO '$DB_USR'@'localhost';" 
mysql -uroot -ppassword -v -e "CREATE DATABASE IF NOT EXISTS $DB_NAM_TEST;" 
mysql -uroot -ppassword -v -e "GRANT ALL PRIVILEGES ON $DB_NAM_TEST.* TO '$DB_USR'@'$DB_HOST';" 
mysql -uroot -ppassword -v -e "FLUSH PRIVILEGES;"

