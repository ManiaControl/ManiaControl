#!/bin/sh
php ManiaControl.php >ManiaControl.log 2>&1 &
echo $! > ManiaControl.pid
