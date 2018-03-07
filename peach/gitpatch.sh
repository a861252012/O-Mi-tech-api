#!/bin/bash
#author Orino
#注意冒号后的空格
read -p "请输入上一版本git的hash值:
" oldv
[[ ! -n "$oldv" ]] && exit 0
#read -p "请输入新版本git的hash值:
#" newv
#自动获取最新的git修改hash值
newv=`git log -p -1 | grep commit`
newv=${newv/commit /}
read -p "请输入打包的版本号:
" vno
#过滤出文件有改动的相对网站根目录的路径
git diff --patch-with-raw  $oldv $newv > patch.txt
urfile=patch.txt
difffiles=""
delFiles=""
#删除目录的标记
delFlag="web/public/dist/v"
delVfiles=
REMOTE_SSH=192.168.10.244
#... A ... M   
#:100644 000000 c80f54f... 0000000... D #长度是40 
#echo ${#str}得出长度
#字符串连接，如果以空格链接，会出问题
function str_concat {
	difffiles="$1|$2"
}
function str_concat1 {
#	if [ "$2" =~ "$delFlag" -a "$delVfiles" != "" ];then
		
#	fi
	delFiles="$1|$2"
}
#逐步读取文件的每一行
while read line;do
	#判断字符串是否是空行，空行后可以跳过
	if [ -z "$line" ]
	then
		break
	fi
	#echo $line
	tmpstr=${line:39}
	#echo $line|cut -c40-
	#echo "$tmpstr1"
	#过滤添加和修改的文件，删除的文件？要不要列出来。。
#	[[ "${line/ A/}" != " A" ]] && echo "include"
#A,C,D,M,R
	[[ "$line" =~ ". A" ]] && str_concat $difffiles $tmpstr
	[[ "$line" =~ ". M" ]] && str_concat $difffiles $tmpstr
	[[ "$line" =~ ". C" ]] && str_concat $difffiles $tmpstr
	[[ "$line" =~ ". R" ]] && str_concat $difffiles $tmpstr
	[[ "$line" =~ ". D" ]] && str_concat1 $delFiles $tmpstr
# echo $line
done < $urfile
#echo $difffiles
#将|换成空格
tarname=${difffiles//|/" "}
delname=${delFiles//|/" "}
#echo $tarname $delname
echo $delname>delFiles.txt
tar -zvcf patch$vno.tar.gz $tarname
scp patch$vno.tar.gz delFiles.txt root@$REMOTE_SSH:/data/www/video-front
rm -rf *gz patch.txt delFiles.txt
