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

    [项目目录]/deplay/nginx/* 文件到10.1.100.172 中/etc/nginx/conf.d/  目录下
   
   vadmin.conf 修改根据实际环境调整,对应位置：  root 【项目目录】/peach-admin;
    
- 配置php.ini
    
    upload_max_filesize = 100M
    
    max_file_uploads = 100
   
 
一.部署步骤

   - 代码发布到 10.1.100.172
    
   - 计划任务
   
\*   * * * * * /usr/bin/php 【项目目录】/artisan schedule:run >> /var/log/vadmin_schedule.log >>/dev/null 2>&1
   
   - rsync配置
        
        [admin]
           path = 【项目目录】/storage/app/uploads/
           read only = no
           list = false
           auth users = rsyncuser
           secrets file = /etc/rsync.pwd
           
   - 资源文件（只取一站）
   
        礼物：【一站项目目录】/web/flash/v[版本号]/image/gift_material/   移动到    【项目目录】/storage/app/uploads/s88888/goods/
        
        主播海报： [一站项目目录]/public/images/anchorimg/  移动到    【项目目录】/storage/app/uploads/s88888/anchor/
    
        头像: 【一，二站】zimg 资源文件（位置根据实际文件位置来）  迁移到  【合并站】zimg 文件下（位置根据实际文件位置来）
        
   - 修改配置文件
   
   
        'database' => [
                'end' => [
                    'host' => '10.1.100.162:3306',
                    'user' => 'video',
                    'pwd' => '123456',
                    'db' => 'vvv_bos'
                ],
                'front' => [
                    'host' => '10.1.100.162:3306',
                    'user' => 'video',
                    'pwd' => '123456',
                    'db' => 'vvv',
                ],
            ],
        
            'redis' => [
                'host' => '10.1.100.161:6379',
                'password' => '123456'
            ],
            
             'mail' => [
                    'default_server' => 'sparkpost',
                    'servers' => [
                        'sparkpost' => [
                            'host' => 'smtp.sparkpostmail.com',
                            'port' => '2525',
                            'encryption' => 'tls',
                            'username' => 'SMTP_Injection',
                            'password' => '63819aca89f76d580f10639141c9484110344b92',
                            'from' => [
                                'noreply@vkfrt.com' => '蜜桃儿',
                            ],
                            'replyTo' => 'test@qq.com',//用户回复地址 todo 上线需要修改
                        ],
                        '_reg' => [//mailtrap
                            'host' => 'smtp.mailtrap.io',
                            'port' => '2525',
                            'encryption' => 'tls',
                            'username' => '03e8c54c5d8138',
                            'password' => '5b72f383c03833',
                            'from' => [
                                'noreply@vkfrt.com' => '蜜桃儿',
                            ],
                            'replyTo' => 'test@qq.com',//用户回复地址 todo 上线需要修改
                        ],
                        'reg_qq' => [//qq
                            'host' => 'smtp.qq.com',
                            'port' => '587',
                            'encryption' => 'ssl',
                            'username' => '2151350569',
                            'password' => 'hf33910440r',
                            'from' => [
                                'noreply@vkfrt.com' => '蜜桃儿',
                            ],
                            'replyTo' => 'test@qq.com',//用户回复地址 todo 上线需要修改
                        ],
                        'reg' => [//todo 上线需要修改
                            'host' => 'smtp.sparkpostmail.com',
                            'port' => '2525',
                            'encryption' => 'tls',
                            'username' => 'SMTP_Injection',
                            'password' => '63819aca89f76d580f10639141c9484110344b92',
                            'from' => [
                                'verify@vkfrt.com' => '蜜桃儿',
                            ],
                            'replyTo' => 'test@qq.com',//用户回复地址
                        ],
                    ],
                ],
                'workman' => [
                    'host' => '10.1.100.182',
                    'port' => '1234',
                ],
   
   - 邮件服务器
        
        【项目目录】/merge-admin/ 下运行:  php start.php start -d 
        
        
        
        
    
        
        
        





