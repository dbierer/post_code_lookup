#!/bin/bash
. /tmp/secrets.sh

echo "Setting up nginx and assigning permissions ..."
/tmp/zendphp_config.sh
/tmp/setup_nginx.sh
mkdir -p $HOME_DIR/public/admin
cp /tmp/adminer.php $HOME_DIR/public/admin/adminer.php
chown -R www-data:www-data /var/www
chmod -R 775 /var/www/*
# Only chown/chmod the subdirs www-data actually writes to (SQLite DB, backups).
# Avoid recursing over the whole bind-mounted repo ($HOME_DIR) - that reassigns
# host-side file ownership to the container's www-data uid on every start.
chown -R www-data:www-data $HOME_DIR/data $HOME_DIR/backup $HOME_DIR/public
chmod -R 775 $HOME_DIR/data $HOME_DIR/backup $HOME_DIR/public
echo "Updating /etc/hosts ..." && \
echo "$CONTAINER_IP   $HOST_NAME" >> /etc/hosts

# Start the first process
/etc/init.d/mysql start
status=$?
if [ $status -ne 0 ]; then
  echo "Failed to start MariaDB/MySQL: $status"
  exit $status
fi
echo "Started MariaDB/MySQL succesfully"
/tmp/restore.sh

# Start the second process
/etc/init.d/php$PHP_VER-zend-fpm start
status=$?
if [ $status -ne 0 ]; then
  echo "Failed to start php-fpm: $status"
  exit $status
fi
echo "Started php-fpm succesfully"

# Start the third process
/etc/init.d/nginx start
status=$?
if [ $status -ne 0 ]; then
  echo "Failed to start nginx: $status"
  exit $status
fi
echo "Started nginx succesfully"

while sleep 60; do
  ps -ax |grep php-fpm |grep -v grep
  PROCESS_1_STATUS=$?
  ps -ax |grep nginx |grep -v grep
  PROCESS_2_STATUS=$?
  ps -ax |grep mysql |grep -v grep
  PROCESS_3_STATUS=$?
  # If the greps above find anything, they exit with 0 status
  # If they are not both 0, then something is wrong
  if [ -f $PROCESS_1_STATUS -o -f $PROCESS_2_STATUS -o -f $PROCESS_3_STATUS ]; then
    echo "One of the processes has already exited."
    exit 1
  fi
done
