#!/bin/sh
php startManiaControl.php 2>&1 &
echo $! > ManiaControl.pid
