#!/usr/bin/sh
. /tmp/secrets.sh
echo "Installing Node + Version Manager (nvm) ..." && \
wget -qO- https://raw.githubusercontent.com/nvm-sh/nvm/master/install.sh | /bin/bash && \
. ~/.bashrc && \
nvm install node
