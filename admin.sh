#!/bin/bash
. ./docker/secrets.sh
DIR=`pwd`
export USAGE="Usage: admin.sh up|down|build|shell|ls"
# If you're using Docker, change these two env vars
# replace "podman" with "docker"
export DOCKER=podman
export COMPOSE=podman-compose
if [[ -z "$1" ]]; then
    echo $USAGE
    exit 1
fi
if [[ "$1" = "up" || "$1" = "start" ]]; then
    $COMPOSE up -d
    sudo chown -R $USER *
elif [[ "$1" = "down" || "$1" = "stop" ]]; then
    $DOCKER exec -it $CONTAINER /tmp/backup.sh
    $COMPOSE down
    sudo chown -R $USER:$USER *
elif [[ "$1" = "ls" ]]; then
    $DOCKER container ls
elif [[ "$1" = "build" ]]; then
    $COMPOSE build
elif [[ "$1" = "shell" ]]; then
    if [[ -z ${CONTAINER} ]]; then
        echo "Unable to locate running container: $CONTAINER"
    else
        $DOCKER exec -it $CONTAINER /bin/bash
    fi
else
    echo $USAGE
    exit 1
fi
