#!/bin/bash
# NOTE: this is for demo only!
export PHP_VER="8.4"
export HOST_NAME=postcode.local
export HOST_URL=http://postcode.local/
export HOME_DIR=/home/vagrant
export DB_USR=db_admin
export DB_PWD=db_password
export DB_NAM=post_code_lookup
export DB_HOST="127.0.0.1"
export DB_PORT=3306
export NGINX_USR=www-data
export REPO_DIR=$HOME_DIR
export REPO_BACKUP_DIR=$REPO_DIR/backup
export ADMINER_VER="5.5.0"
export ADMINER_DIR=/var/www/demo/public/admin
export ADMINER_USR=admin
export ADMINER_PWD=S5cret!
export CONTAINER=post_code_lookup
export CONTAINER_IP="10.10.99.10"
export CONTAINER_SUBNET="10.10.99.0/24"
export CONTAINER_GATEWAY="10.10.99.1"
export AWS_PHP_VER="8.1"
