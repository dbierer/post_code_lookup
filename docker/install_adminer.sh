#!/usr/bin/sh
echo "Downloading Adminer ..."
. /tmp/secrets.sh
curl -L https://github.com/vrana/adminer/releases/download/v$ADMINER_VER/adminer-$ADMINER_VER.php -o /tmp/adminer.php

