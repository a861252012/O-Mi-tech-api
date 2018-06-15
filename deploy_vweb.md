测试环境-后台部署文档
## 服务器准备

10.1.100.172	php前台(Nginx+web+定时任务), tomcat, php后台(Nginx+web+定时任务)

10.1.100.173	业务聊天socket， ics， 四大美人，票据验证，zookeeper

10.1.100.174	业务聊天socket，Websocket-proxy， 票据，zookeeper

10.1.100.175	蜜情socket， 蜜情service，zookeeper

10.1.100.176	haproxy， redis， 数据库备

10.1.100.177	haproxy，数据库主，代理商后台+错误收集系统，redis(错误收集系统用)

目录结构


## 执行步骤

- 配置nginx

   [项目目录]/deplay/nginx/vweb.conf 文件到10.1.100.172 中/etc/nginx/conf.d/  目录下
   
   vweb.conf 修改根据实际环境调整,对应位置：  root   【项目目录】/public;
    
- 配置php.ini
    
    upload_max_filesize = 100M
    
    max_file_uploads = 100
   
 
一.前端部署步骤

    1.1代码发布到 10.1.100.172
    
## 环境配置

   - 计划任务
   
\*   * * * * * /usr/bin/php 【项目目录】/artisan schedule:run >> /var/log/vweb_schedule.log >>/dev/null 2>&1
           
 
## 前端部署步骤

- 代码发布到 10.1.100.172

- 配置文件修改：

       REDIS_HOST=10.1.100.161
       REDIS_PASSWORD=123456
       REDIS_PORT=6379
        
- 执行php artisan site:init





