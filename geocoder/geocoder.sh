#!/bin/bash
if ps -ef | grep -v grep | grep geocoder.php5 ; then
        exit 0
else
        dir=`dirname $0`
        cmd="$dir/geocoder.php5"
        $cmd >/dev/null 2>&1 &
        exit 0
fi