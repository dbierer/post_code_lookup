#!/bin/bash
if [[ "$1" = "" ]]; then
    echo "Usage = import_postcode.sh [ISO2] [DRIVER]"
    echo "    ISO2 : 2 character country code (default: 'US')"
    echo "    DRIVER : mariadb | sqlite | pgsql (default: 'sqlite')"
    exit 1
fi;
export PHP_FN=../src/import_postcode.php
export DATA_DIR=../data
if [[ ! -d $DATA_DIR ]]; then
    export DATA_DIR=../data
fi
export SRC_PATH=./$PHP_FN
if [[ ! -f $SRC_PATH ]]; then
    export SRC_PATH=../src/$PHP_FN
fi
if [[ ! -f $DATA_DIR/$1.txt ]]; then
    curl -X GET https://download.geonames.org/export/zip/$1.zip -o $DATA_DIR/$1.zip
    unzip -o $DATA_DIR/$1.zip -d $DATA_DIR
    rm $DATA_DIR/$1.zip
fi
php $SRC_PATH $1 $2
exit 0
