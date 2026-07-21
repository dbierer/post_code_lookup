#!/usr/bin/sh
. /tmp/secrets.sh
echo "Installing sendmail simulator ..."
cd /tmp
curl -L https://raw.githubusercontent.com/axllent/sndmail/develop/install.sh -o /tmp/install_sendmail_sim.sh
chmod +x /tmp/install_sendmail_sim.sh
/tmp/install_sendmail_sim.sh
