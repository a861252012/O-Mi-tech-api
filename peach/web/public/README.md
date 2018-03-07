#前端发布配置说明
如果有直播间代码需要发布, 因为架构的不同, 需要先发布直播间, 再发布本目录下的代码

###环境配置
需要nodejs稳定版本环境
执行以下代码
```shell
$ npm install grunt-cli -g
```
进入web/public目录
执行以下代码
```shell
$ npm install
```

###开发
```shell
$ grunt dev
```

###监听
```shell
$ grunt watch
```

###发布
```shell
$ grunt
```