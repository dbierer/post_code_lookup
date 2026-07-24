#!/usr/bin/sh
. /tmp/secrets.sh
echo "Installing PHP extensions ..."
/usr/local/bin/zendphpctl ext install gd
/usr/local/bin/zendphpctl ext install simplexml
/usr/local/bin/zendphpctl ext install intl
/usr/local/bin/zendphpctl ext install mbstring
/usr/local/bin/zendphpctl ext install mysql
/usr/local/bin/zendphpctl ext install curl
/usr/local/bin/zendphpctl ext install dom
/usr/local/bin/zendphpctl ext install mbstring
/usr/local/bin/zendphpctl ext install zip
/usr/local/bin/zendphpctl ext install sqlite3
