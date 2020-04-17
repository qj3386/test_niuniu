#!/bin/bash
killall php-fpm
sleep 2
/usr/local/php/bin/php-fpm start
