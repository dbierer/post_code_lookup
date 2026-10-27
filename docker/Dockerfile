FROM ubuntu:noble
MAINTAINER doug@unlikelysource.com
ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=Etc/UTC
COPY * /tmp/
RUN chmod +x /tmp/*.sh
RUN \
    echo "Run bin/simple_setup.sh to set up the demo ..."
WORKDIR /home/vagrant
ENTRYPOINT ["/tmp/startup_minimal.sh"]
