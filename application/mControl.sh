#!/bin/sh
php mControl.php 2>&1 &
echo $! > mControl.pid
