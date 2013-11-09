#!/bin/sh
php ManiaControl.php 2>&1 &
echo $! > ManiaControl.pid
