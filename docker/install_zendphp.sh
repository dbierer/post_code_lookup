#!/usr/bin/sh
. /tmp/secrets.sh
echo "Installing ZendPHP ..."
cd /tmp
curl -L https://repos.zend.com/zendphp/zendphpctl -o zendphpctl
curl -L https://repos.zend.com/zendphp/zendphpctl.sig -o zendphpctl.sig
CHECK=`echo "$(cat zendphpctl.sig) zendphpctl" | sha256sum --check`
rm ./zendphpctl.sig
chmod +x ./zendphpctl
mv ./zendphpctl /usr/local/bin/zendphpctl
/usr/local/bin/zendphpctl repo install
/usr/local/bin/zendphpctl php install $PHP_VER
/usr/local/bin/zendphpctl fpm install $PHP_VER
#/usr/local/bin/zendphpctl fpm config $PHP_VER
