#!/bin/bash
WWWROOT=`pwd`

WEB_NAME=VideoSrcWeb.tar.gz
if [ -f "$WEB_NAME" ];then
		tar -zvxf $WEB_NAME -C $WWWROOT
		rm -f $WEB_NAME
	else
		echo 'VideoSrcWeb.tar.gz不存在，非调试环境更新，可以忽略此问题'
fi


#防止产生的logs日志，只有读的权限，导致的删除非常慢
chown www:www -R ./
chmod 777 app/cache app/logs web -R
chmod 777 Vcore/Cache -R
rm -rf app/cache/dev/*  app/cache/prod/*
rm -rf Vcore/Cache/View/*
WWWROOT_VIEW=$WWWROOT/Vcore/App/View

#rm -rf web/ssi/*
#生成ssi的软链接
#ln -s $WWWROOT_VIEW/header.html.twig web/ssi/header.html
#ln -s $WWWROOT_VIEW/footer.html.twig web/ssi/footer.html
#ln -s $WWWROOT_VIEW/ad.html.twig web/ssi/ad.html
#ln -s $WWWROOT_VIEW/sidead.html.twig web/ssi/sidead.html
#ln -s $WWWROOT_VIEW/activity-list.html.twig web/ssi/activity-list.html
#ln -s $WWWROOT_VIEW/meta.html.twig web/ssi/meta.html
#ln -s $WWWROOT_VIEW/meta-cache.html.twig web/ssi/meta-cache.html
#ln -s $WWWROOT_VIEW/online-consultation.html.twig web/ssi/online-consultation.html
#ln -s $WWWROOT_VIEW/google-analytics.html.twig web/ssi/google-analytics.html
#ln -s $WWWROOT_VIEW/chargead.html.twig web/ssi/chargead.html
