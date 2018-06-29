#!/bin/bash

V_ROOT="/data/www/raby/v"
############################storage文件不能复盖######################################################

/bin/cp -rf $V_ROOT/vweb.env $V_ROOT/vweb/.env
/bin/cp -rf $V_ROOT/vapi.env $V_ROOT/vapi/.env

cd $V_ROOT/vapi
nohup php artisan queue:work --queue=mail:safeMailVerify &
nohup  php artisan queue:work --queue=mail:pwdreset &



##################################storage文件不能复盖 后台#########################################

/bin/cp -rf $V_ROOT/vadmin.config.php $V_ROOT/vadmin/merge-admin/conf/config.php
rm -rf $V_ROOT/vadmin/merge-admin/storage
ln -s $V_ROOT/vadmin/storage $V_ROOT/vadmin/merge-admin/storage
cd $V_ROOT/vadmin/merge-admin
php workman.php restart -d

/bin/chmod -R 777 $V_ROOT