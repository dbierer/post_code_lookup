#!/usr/bin/sh
. /tmp/secrets.sh
echo "Configuring nginx ..."
mv /var/www/html /var/www/html.old
ln -s -f $HOME_DIR/public /var/www/html
rm -f /etc/nginx/sites-enabled/*
cp -f /tmp/*.conf /etc/nginx/sites-available/
ln -s -f /etc/nginx/sites-available/nginx.default.conf /etc/nginx/sites-enabled/default
/etc/init.d/nginx restart
