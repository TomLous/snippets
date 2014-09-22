#!/bin/bash
if ps -ef | grep -v grep | grep geocoder.php5 ; then
        exit 0
else
        dir=`dirname $0`
        cmd="php $dir/geocoder.php5"
        $cmd 2>&1 &
        # >/dev/null
        exit 0
fi