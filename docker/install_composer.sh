#!/usr/bin/sh
echo "Installing Composer ..."
curl -L https://getcomposer.org/composer.phar -o ./composer.phar
chmod +x ./composer.phar
mv ./composer.phar /usr/bin/composer
