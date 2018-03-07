#/bin/bash
REMOTE_SSH_191=10.1.100.191
#打包项目
tar -zvcf VideoSrcWeb.tar.gz crontab-list phpcli-list app/config src web Vcore pdomysql.php new_init.sh
#ssh拷贝到远程服务器
scp VideoSrcWeb.tar.gz root@$REMOTE_SSH_191:/data/www/peach-front
rm -rf *gz
echo '----pack over!----'
