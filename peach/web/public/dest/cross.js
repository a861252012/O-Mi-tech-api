// 页面模块
var crossList = {
    'libJs.js': [
        'vendor/jquery/jquery.min.js'
    ],
    'commonCss.css' : [
        'style/module/reset.css',
        'style/module/common.less'
    ],
    'commonJs.js' : [
        'js/widget/jquery.cookie.js',
        'js/module/dialog.js',
        'js/module/validation.js',
        'js/module/utility.js',
        'service/captcha.js',
        'js/module/user.js',
        'js/module/dropdown.js',
        'service/user.js',
        'js/module/common.js'
    ],

    //login
    'loginJs.js' : [
        'js/page/page-login.js'
    ],
    //home
    'indexCss.css' : [
        'style/widget/flexslider.less',
        //'style/module/task.less',
        //'style/page/page-index.less',
        'style/page/page-index.less'
    ],
    'indexJs.js' : [
        'js/widget/base64.min.js',
        'js/module/info-panel.js',
        'js/module/array.js',
        //'js/module/task.js',
        'js/module/room-ticket.js',
        'js/module/room-pwd.js',
        'js/module/room-timecount.js',
        'js/widget/jquery.flexslider.js',
        'js/widget/DPlayer.min.js',
        'js/page/page-index.js'
    ],
    //rank
    'rankCss.css' : [
        'style/page/page-rank.less'
    ],
    'rankJs.js' : [
        'js/module/info-panel.js',
        'js/page/page-ranking.js'
    ],
    //about
    'aboutCss.css' : [
        'style/page/page-about.less'
    ],

    'aboutJs.js' : [
        'js/page/page-about.js'
    ],

    //business
    'agreementJs.js': [
        'js/page/page-bus-agree.js'
    ],

    'signupJs.js': [
        'js/page/page-bus-signup.js'
    ],

    //member
    'anchorCss.css': [
        'style/widget/uploadify.css',
        'style/page/page-mem-anchor.less'
    ],
    
    'anchorJs.js': [
        'js/page/page-mem-anchor.js'
    ],

    'attentionCss.css': [
        'style/page/page-mem-attention.less'
    ],

    'attentionJs.js': [
        'js/page/page-mem-attention.js'
    ],

    'shopCss.css': [
        'style/module/noble.less',
        'style/page/page-mem-shop.less'
    ],
    //新版充值
    'payCss.css': [
        'style/page/page-pay.less'
    ],

    'shopJs.js': [
        'js/module/noble.js',
        'js/module/shop.js',
        'js/page/page-mem-shop.js'
    ],

    'memberIndexCss.css': [
        'style/page/page-mem-index.less',
        'style/widget/uploadify.css'
    ],

    'memberIndexJs.js': [
        'js/widget/ctry/select.js',
        'js/widget/uploadify/jquery.uploadify.js',
        'js/page/page-mem-index.js',
    ],

    'memberLinkCss.css': [
        'style/page/page-mem-link.less'
    ],

    'memberLinkJs.js': [
        'js/page/page-mem-invite.js',
    ],

    'memberMsgCss.css': [
        'style/page/page-mem-msg.less'
    ],

    'memberMsgJs.js': [
        'js/page/page-mem-sysmsg.js'
    ],

    'memberSceneCss.css': [
        'style/page/page-mem-scene.less'
    ],

    //广告
    'busCss.css': [
        'style/page/page-business.less'
    ],

    'pageCommonJs.js': [
    ],

    //个人空间
    'spaceCss.css': [
        'style/page/page-space.less'
    ],

    //充值
    'chargeCss.css': [
        'style/page/page-charge.less'
    ],

    'chargeJs.js': [
        'js/module/array.js',
        'js/page/page-charge.js'
    ],
    //自主渠道充值
    'chargePayJs.js': [
        'js/module/array.js',
        'js/page/page-charge-pay.js'
    ],
    //安全邮箱验证
    'mailCss.css': [
        'style/page/page-mail.less'
    ],

    'mailJs.js': [
        'js/page/page-mail.js'
    ],

    //房间设置
    'roomsetCss.css': [
        'style/page/page-mem-roomset.less'
    ],
    'roomsetJs.js': [
        'js/page/page-mem-roomset.js'
    ],

    //直播间页面
    'liveCss.css': [
        'style/module/noble.less',
        'style/page/page-live.less'
    ],
    //H5直播间页面
    'liveH5Css.css': [
        'style/module/noble.less',
        'style/page/page-live-h5.less'
    ],
    'liveJs.js': [
        'js/widget/swfobject.js',
        'js/module/noble.js',
        'js/page/page-live.js'
    ],
    'liveH5Js.js': [
        'js/module/noble.js',
        'js/page/page-live-h5.js'
    ],
    //贵族
    'nobleCss.css': [
        'style/module/noble.less',
        'style/page/page-noble.less'
    ],

    'nobleJs.js': [
        'js/module/noble.js',
        'js/page/page-noble.js'
    ],

    //第三方平台
    'platJs.js': [
        'js/widget/base64.min.js',
        'js/module/room-ticket.js',
        'js/page/page-plat.js'
    ]
};
var CDN = typeof CDN_HOST == "undefined" ? false : CDN_HOST;
//cdn 数组
var cdnPathArr = [
    CDN || '',
    //'http://s.bigpeach52.com',
    //'http://s.mitaoclub52.net'
];

/**
 * 随机切换CDN方法
 * @param  {[type]} arr [cdn数组]
 * @return {[type]}     [返回cdn中的一个值]
 */
var __randomSeedFromArr = function(arr){
    var arrLen = arr.length;
    var randomNum = Math.floor(Math.random()*arrLen);
    return arr[randomNum];
}

//优化目的将ued_config 改为了 Config
var __cdn = __randomSeedFromArr(cdnPathArr);

var Config = {
    publishVersion: "v2017090701",
    subPublishVersion: "1.0",
    resource: typeof crossList == "undefined" ? {}: crossList,
    //language: navigator.language || navigator.browserLanguage,
    cdnPath: __cdn + '/public',
    cdnOrigin: __cdn,
    //cdnPath: '/public',
    imagePath: __cdn + '/public/src/img',
    roomSrcPath: __cdn + '/roomh5/src/www',
    liveMode: '/h5', //直播间播放方式: 旧直播间'', h5直播间'/h5'
    mode: 'online' //dev/online/onlinedev
};
/**
 * 静态文件加载器 - v0.1.2 - 2015-11-25
 * Copyright (c) 2015 Young Foo
 */

var Application = function(config){

    //私有变量less，css，js模板
    var __jsTemplate = '<script src="${src}" charset="utf-8" type="text/javascript" itemid="${itemid}"><\/script>',
    __cssTemplate = '<link rel="stylesheet" type="text/css" href="${href}" itemid="${itemid}" />';

    /*
     *$aliases the registered type aliases
     *@var array
     * private
     */
    var aliases = [];

    //容器
    this.container = {};

    //配置
    this.config = {}

    //cdn path
    this.cdnPath = "";

    //dest path
    this.destPath = "";

    //容器
    this.container = {};

    /**
     * Alias a type to a different name.
     * @param  string  abstract
     * @param  string  alias
     * @return void
     */
    this.alias = function(abstract, alias){
        aliases[alias] = abstract;
    };

    /**
     * Get the alias for an abstract if available.
     * @param  string  $abstract
     * @return string
     */
    this.getAlias = function(abstract){
        return aliases[abstract] ? aliases[abstract] : abstract;
    }

    /**
     * 装载配置文件
     * @param  {[type]} str [description]
     * @return {[type]}     [description]
     */
    this.configure = function(conf){
        this.config = conf;
    }




    //将实例注册到容器上  user  user config 学习singleton ， provider, provider用于提供工具类之类的东西
    this.register = function(objName){
        this.container[objName] = new window[objName]();
    }

    /**
     * 获取容器上的对象
     * @param obj 对象名
     * @return 返回容器中的对象
     */
    this.make = function(objName){
        //判断容器中是否存在，如果存在就返回对象
        for(var key in this.container){
            if(key == objName){
                return this.container[objName];
            }
        };

        //如果不存在就直接返回注册对象
        this.register(objName);
        return this.container[objName];
    }
 
    /**
     * 初始化导入
     * @return {[type]} [description]
     */
    this.initImport = function(){

        this.cdnPath = this.config.cdnPath || "/";
        this.destPath = this.config.cdnPath + "/dest/" + window.PUBLISH_VERSION + "/";

        /**
         * 根据是否是开发版修改cdn路径和判断加载less编译文件
         */
        if (this.config.mode == "dev") {

            //dev环境路径配置
            this.cdnPath = this.cdnPath + "/";

        };

    };

    /**
     * [__importdest 导入压缩文件]
     * @param  {[type]} file     [文件]
     * @param  {[type]} fileType [文件类型]
     * @param  {[type]} id       [文件id]
     * @return {[type]}          [null]
     */
    var __importDest = function(file, fileType, ins, isHead){

        var outStr = '';
        var fileDest = '';
        
        if(ins.config.mode == "onlinedev"){
            fileDest = file;
        }
        
        if(ins.config.mode == "online"){
            fileDest = file.split(".")[0] + "-min." + fileType;
        }
        
        if (fileType == "js") {
            
            outStr = __jsTemplate.replace("${src}", ins.destPath + "js/" + fileDest).replace("${itemid}", file);
        
        } else if (fileType == "css") {

            outStr = __cssTemplate.replace("${href}", ins.destPath + "css/" + fileDest).replace("${itemid}", file);
        
        }

        if(isHead){
            ins.asyncImportJs(ins.cdnPath + file);
        }else{
            document.write(outStr);
        }
    }

    /**
     * [__importDest 导入debug文件]
     * @param  {[type]} files    [文件数组]
     * @param  {[type]} fileType [文件类型]
     * @param  {[type]} id       [文件id]
     * @return {[type]}          [null]
     */
    var __importDev = function(files, fileType, ins, isHead) {

        for(var i = 0; i < files.length; i++) {

            var outStr = '';

            if (fileType == "js") {

                outStr = __jsTemplate.replace("${src}", ins.cdnPath + "src/" + files[i]).replace("${itemid}", files[i]);

            } else if (fileType == "css") {

                outStr = __cssTemplate.replace("${href}", ins.cdnPath + "dev/" + files[i]).replace("${itemid}", files[i]).replace(".less", ".css");
            
            };

            if(isHead){
                ins.asyncImportJs(ins.cdnPath + files[i]);
            }else{
                document.write(outStr);
            }
        }

    }

    /**
     * @function importFile 导入文件
     * @param id 静态文件的id名称
     * @param fileType  文件类型  js/css
     * @param mode 运行环境 dev/online dev表示环境加载多个源码文件 online代表线上环境 加载单个合并压缩后的文件
    */
    this.importFile = function(id, fileType, mode, isHead) {

        var __mode = this.config.mode,
            __id = id +'.' + fileType;

        //判断是否有指定模式
        if (mode) {
            __mode = mode;
        }

        //判断resource数组是否为空
        if (!this.config.resource[__id]) {
            return false;
        }

        //通过不同的模式导入不同的文件
        if (__mode == "online" || __mode == "onlinedev") {

            //线上模式导入
            __importDest(__id, fileType, this, isHead);

        } else if (__mode == "dev") {

            //调试模式导入
            __importDev(this.config.resource[__id], fileType, this, isHead);
        }
    }

    /** 
     * @function asyncImport 异步导入
     * @param src js的路径
    */
    this.asyncImportJs = function(src, charset) {
        
        var head = document.getElementsByTagName("head")[0];

        //创建script
        var script = document.createElement("script");
        script.type = "text/javascript";

        //设置为异步
        script.async = true;
        script.src = src;


        //charset设置
        if (charset) {
            script.charset = charset;
        }

        //防止没有head标签的情况
        if (!head) {
            document.body.insertBefore(script, document.body.firstChild);
        } else {
            head.appendChild(script);
        }
    }

    /**
     * 构造函数
     * @return {[type]} [description]
     */
    this.init = function(conf){

        //初始化配置文件
        this.configure(conf);

        //导入配置文件
        this.initImport();

    }

    //初始化
    this.init(config);

}

var cross = new Application(window.Config);

