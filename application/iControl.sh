#!/bin/sh
php iControl.php 2>&1 &
echo $! > iControl.pid
