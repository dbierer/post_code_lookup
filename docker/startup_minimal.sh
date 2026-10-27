#!/bin/bash
. /tmp/secrets.sh

# Nothing is started here: keep the container running so you can shell in
# and run bin/simple_setup.sh. Sleep in the background and wait on it so
# that "podman stop" (SIGTERM) shuts the container down immediately.
trap 'exit 0' TERM INT
sleep infinity &
wait $!
