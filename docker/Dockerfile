FROM ubuntu:noble
MAINTAINER doug@unlikelysource.com
COPY * /tmp/
RUN chmod +x /tmp/*.sh
RUN \
    echo "Installing misc tools ..." && \
    apt-get update && \
    apt-get install -y nano less vim net-tools wget unzip apt-utils tree curl git
RUN \
    echo "Installing Mariadb (MySQL open source equivalent) ..." && \
    /tmp/install_mysql.sh
RUN \
    echo "Installing nginx ..." && \
    apt-get install -y nginx
RUN \
    echo "Installing ZendPHP ..." && \
    /tmp/install_zendphp.sh
RUN \
    echo "Adding/enabling PHP extensions ..." && \
    /tmp/install_php_ext.sh
RUN \
    echo "Installing Composer ..." && \
    /tmp/install_composer.sh
RUN \
    echo "Installing Adminer (phpMyAdmin equivalent) ..." && \
    /tmp/install_adminer.sh
WORKDIR /home/vagrant
ENTRYPOINT /tmp/startup.sh
