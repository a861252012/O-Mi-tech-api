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