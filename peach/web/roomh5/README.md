#直播间webpack使用
由于直播间是嵌入在roomh5.html.twig当中, 现在的方式是向Vcore/App/View/Room/目录复制roomh5页面.
开发时, 在服务器执行start
发布时, 在真实目录执行build

发布版本时:
需要先执行npm run build
后执行public目录中的grunt

###环境配置
需要nodejs稳定版本环境
执行以下代码
```shellnpm
$ npm install webpack -g
```
进入web/roomh5目录
执行以下代码
```shell
$ npm install
```

### start
```shell
$ npm start
```

### build
```shell
$ npm run build
```
