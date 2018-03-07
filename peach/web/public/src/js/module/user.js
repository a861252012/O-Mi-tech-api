/*
 * @ description User用户
 * @ type Class
 * @ return null
 */
(function (__User, window) {

    //user实例化
    var instanceUser;

    //初始化Captcha
    var cap = new Captcha();

    __User = function () {

        /*
         * @ description 设置注册提示颜色
         * @ type 特权方法
         * @ param str
         * @ return null
         */
        this.setRegErrorTips = function (str) {
            $(".rTip").text(str).css("color", "#c1111c");
        }

        /*
         * @ description 设置登录提示颜色
         * @ type 特权方法
         * @ param str
         * @ return null
         */
        this.setLoginErrorTips = function (str) {
            $(".lTip").text(str).css("color", "#c1111c");
        }

        /*
         * @ description 用户注册
         * @ type function
         * @ param option: 按钮
         * @ return null
         */
        this.submitR = function (option, sucCallback) {

            var that = this;

            var $regName = $("#rName"),
                $regNickname = $("#rNickname"),
                $regPwd = $("#rPassword"),
                $regPwdAgain = $("#rAPassword"),
                $regScode = $("#rsCodeIpt");

            $(option).off("click");
            $(option).on("click", function () {

                //用户邮箱验证
                if ($regName.val().length == 0) {
                    that.setRegErrorTips("请输入登录邮箱！");
                    return;
                } else if (!Validation.isEmail($regName.val())) {
                    that.setRegErrorTips("邮箱格式不正确，且注册邮箱不能使用中文/:;\\空格，等特殊符号！");
                    return;
                }
                ;

                //用户昵称验证
                if ($regNickname.val().length == 0) {
                    that.setRegErrorTips("请输入昵称！");
                    return;
                } else if (!Validation.isAccount($regNickname.val())) {
                    that.setRegErrorTips("注册昵称不能使用/:;\\空格,等特殊符号！(2-8位的昵称)");
                    return;
                }
                ;

                //用户密码不能为空验证
                if ($regPwd.val().length == 0) {
                    that.setRegErrorTips("请输入密码！");
                    return;
                }
                ;

                //用户再次输入密码不能为空验证
                if ($regPwdAgain.val().length == 0) {
                    that.setRegErrorTips("请输入确认密码！");
                    return;
                }
                ;

                //用户两次确认密码不能相同
                if ($regPwd.val().length != 0 && $regPwdAgain.val().length != 0 && $regPwd.val() != $regPwdAgain.val()) {
                    that.setRegErrorTips("两次密码输入不同！");
                    return;
                }
                ;

                //用户security code验证
                if ($regScode.val().length == 0) {
                    that.setRegErrorTips("请输入验证码！");
                    return;
                }
                ;

                var pw = $.trim($regPwd.val());
                var rpw = $.trim($regPwdAgain.val());

                UserService.actionRegister({
                    username: $regName.val(),
                    nickname: $regNickname.val(),
                    password: pw,
                    repassword: rpw,
                    captcha: $regScode.val(),
                    sucCallback: function (res) {
                        if (window.IFRAME_LOGIN_STATE) {
                            IFRAME_LOGIN_STATE.regSuc();
                        }
                        ;
                        //window.location.reload();

                        if (sucCallback) {
                            sucCallback();
                        }
                        ;
                    },
                    errCallback: function (res) {
                        that.setRegErrorTips(res.msg);
                        cap.flashCaptcha($("#rsCodeImg"));
                        Utility.log(res.msg);
                    }
                });

            });

            $("#rsCodeIpt, #rAPassword, #rPassword, #uid, #rName").on("keydown", function (e) {
                if (e.keyCode == "13") {
                    $(option).trigger("click");
                }
                ;
            });
        }


        /*
         * @ description 用户登录(跨域登录)
         * @ type function
         * @ param option: 按钮
         * @ return null
         */
        this.submitL = function (option) {

            var that = this;

            var $loginName = $("#lName"),
                $loginPwd = $("#lPassword"),
                $loginScode = $("#lsCodeIpt"),
                $loginAuto = $("#lAuto");

            $(option).off("click");
            $(option).on("click", function () {

                //输入信息
                var userName = $.trim($loginName.val());
                var userPwd = $.trim($loginPwd.val());
                var userCode = $.trim($loginScode.val());

                //判断
                if (userName.length == 0) {
                    that.setLoginErrorTips("请输入登录邮箱！");
                    return;
                }
                ;

                if (userPwd.length == 0) {
                    that.setLoginErrorTips("请输入登录密码！");
                    return;
                }
                ;

                if (!$loginScode.is(":hidden") && userCode.length == 0) {
                    that.setLoginErrorTips("请输入验证码！");
                    return;
                }
                ;

                //登录接口调用
                UserService.actionLogin({
                    username: userName,
                    password: userPwd,
                    captcha: userCode,
                    remember: $loginAuto.prop('checked') ? 1 : 0,
                    sucCallback: function (res) {
                        if (window.IFRAME_LOGIN_STATE) {
                            IFRAME_LOGIN_STATE.loginSuc();
                        }
                        ;
                        //window.location.href = "/";
                        if (res.status == 1) {
                            //location.reload();
                            window.location.href = window.location.href;
                            // var direction = res.redirect;
                            // var scriptSrc = res.synstr;
                            //
                            // //success
                            // var _success = function () {
                            //   window.location.href = direction;
                            // }
                            //
                            // //error
                            // var _error = function () {
                            //   if (window.console) {
                            //     console.warn("problem in server 500");
                            //   }
                            // }
                            //
                            // var script = document.createElement('script');
                            // script.src = scriptSrc;
                            // script.type = 'text/javascript';
                            //
                            // //for FF chrome
                            // script.onload = function (e) {
                            //   _success();
                            // }
                            //
                            // script.onerror = function (e) {
                            //   _error();
                            // }
                            //
                            // //for ie
                            // script.onreadystatechange = function (e) {
                            //   if (this.readyState == 'complete') {
                            //     _success();
                            //   } else if (this.readyState == 'loaded') {
                            //     _error();
                            //   }
                            // }
                            //
                            // document.documentElement.firstChild.appendChild(script);
                        }
                    },
                    errCallback: function (res) {
                        //提示错误信息
                        that.setLoginErrorTips(res.msg);

                        $loginName.afterIcon(vcIconWarnTMP);
                        $loginPwd.afterIcon(vcIconWarnTMP);

                        //刷新code
                        cap.flashCaptcha($("#lsCodeImg"));
                        //隐藏code
                        // if (res.failNums >= 5) {
                        //   $(".login-code").show();
                        // }
                        // ;

                        Utility.log(res.msg);
                    }
                });


            });

            $("#lsCodeIpt, #lPassword, #lName").on("keydown", function (e) {
                if (e.keyCode == "13") {
                    $(option).trigger("click");
                }
                ;
            });
        }

        /*
         * @ description 用户登录成功处理
         * @ type function
         * @ param data 登录成功时返回的数据 func: 回调
         * @ return null
         */
        this.getUserInfoSuccess = function (data, func) {
            console.log(data)

            //内部处理逻辑
            var dataInfo = data.info;

            //获取是否隐身信息
            var hiddenState = dataInfo["hidden"];

            //头部隐身处理
            if (typeof hiddenState != "undefined") {

                //用户在线状态值
                var hiddenText = parseInt(hiddenState, 10) ? "隐身" : "在线";
                var hiddenClassState = parseInt(hiddenState, 10) ? "dropdown-title-hidden" : "dropdown-title-online";

                //用户在线状态下拉菜单列表
                var userState = ["<div class='loginDropdown dropdown' id='loginDropdown'>",
                    "<div class='dropdown-title " + hiddenClassState + "'><div class='dropdown-title-text'>" + hiddenText + "</div><span class='dropdown-tri'></span></div>",
                    "<div class='dropdown-list'>",
                    "<div class='dropdown-item' id='loginOnline' data-value='0'>在线</div>",
                    "<div class='dropdown-item' id='loginHide' data-value='1'>隐身</div>",
                    "</div></div>"].join("");

            }
            ;

            $(".user-por").append(userState);

            //绑定头部下拉菜单事件
            var loginDropdown = new Dropdown({
                id: "loginDropdown",
                handleItem: function (target) {
                    $.ajax({
                        url: "/member/hidden/" + target.getAttribute("data-value"),
                        type: "get",
                        dataType: "json",
                        data: "",
                        success: function (json) {

                            if (json.status == 1) {
                                //if(console){ console.log("hidden set succ")};

                                var targetVal = parseInt(target.getAttribute("data-value"));
                                var $targetTitle = $("#loginDropdown").find(".dropdown-title");
                                //0 在线，1 隐身
                                if (targetVal == 0) {
                                    $targetTitle.addClass("dropdown-title-online").removeClass("dropdown-title-hidden");
                                } else {
                                    $targetTitle.addClass("dropdown-title-hidden").removeClass("dropdown-title-online");
                                }
                                ;

                            } else {
                                if (console) {
                                    console.log(json.message)
                                }
                                ;
                            }
                            ;
                        },

                        error: function () {
                            Utility.log("hidden set error(500)!");
                        }
                    });
                }
            });

            //将UID赋值到User上
            __User.UID = dataInfo.uid;
            //user level
            __User.UL = parseInt(dataInfo.lv_rich, 10);
            //points
            __User.POINTS = dataInfo.points;

            //info
            __User.INFO = dataInfo;

            //download
            __User.DOWNLOADURL = data.downloadUrl;

            //my ticket 一对多
            __User.MY_TICKET = data.myticket;

            //my res 一对一
            __User.MY_RES = data.myres;

            //执行回调
            if (func) {
                func(data)
            }
            ;

        }

        //绑定登录相关事件
        this.bindLoginEvent = function () {
            //loginDialog : function(){

            //绑定刷新验证码按钮
            cap.bindChangeCaptcha();

            //登录
            $('#lName').accountInput(".lTip");
            $('#lPassword').passwordInput('.lTip');
            $("#lsCodeIpt").sCodeInput(".lTip");

            //登录事件
            instanceUser.submitL('.lButton');

        }

        /*
         * @ description 初始化User
         * @ type function
         * @ return null
         */
        this.init = function () {
            //__User.instanceUser = new User();
            instanceUser = this;

        }

        this.init();
    }

    //静态属性和方法
    $.extend(__User, {

        UID: $.cookie("webuid"),
        /*
         * @ description 返回是否有过链接状态，用于7天免登录
         * @ type 静态方法
         * @ param null
         * @ return bool
         */
        IMG_URL:[],//图片地址
        QRCODE_IMG: [],//二维码下载
        DOWNLOADAPPURL: [],//APP下载
        MY_TICKET: [], //门票房间初始化
        MY_RES: [], //一对一房间初始化

        //登录弹窗
        loginDialog: $.dialog({
            id: "loginDialog",
            title: "用户中心",
            content: ['<div class="J-dialog-tab J-tab">',
                '<ul class="tab-title J-tab-menu clearfix" style="list-style: none;">',
                '<li class="tab-item active J-tab-menu-item" id="dialogLogin">',
                '<h3>用户登录</h3>',
                '</li>',
                '<li class="tab-item J-tab-menu-item" id="dialogRegister">',
                '<h3>用户注册</h3>',
                '</li>',
                '</ul>',

                '<div class="J-tab-main active">',
                '<div class="m-form-wrapper">',
                '<form action="" class="lForm m-form" onSubmit="return false">',

                '<div class="m-form-item">',
                '<label for="lName">登录邮箱：</label>',
                '<input type="text" class="txt" id="lName" tabIndex="1" placeholder="您的登录邮箱" />',
                '</div>',
                '<div class="m-form-item">',
                '<label for="lPass word">登录密码：</label>',
                '<input type="password" class="txt" id="lPassword" tabIndex="2" autocomplete="off" placeholder="您的密码"/>',
                '</div>',

                '<div class="m-form-item">',
                '<label for="lsCodeIpt">验&nbsp;&nbsp;证&nbsp;&nbsp;码：</label>',
                '<input type="text" class="txt txt-short" id="lsCodeIpt" tabIndex="3" placeholder="不区分大小写"/>',
                '<img src="" alt="验证码" id="lsCodeImg" class="s-code-img" />',
                '<a href="javascript:void(0);" class="m-form-tip J-change-scode">换一换</a>',
                '</div>',

                '<div class="m-form-item clearfix">',
                // '<label for="lAuto" class="login-auto">',
                // '<input type="checkbox" id="lAuto" class="login-checkbox" />',
                // '<span>7天免登录</span>',
                // '</label>',
                '<a href="/getpwd" target="_blank" class="forget-pw" title="忘记密码怎么办？点我">',
                '<span>忘记密码</span>',
                '<span class="i-vc i-vc-help"></span>',
                '</a>',
                '</div>',
                '<div class="lTip"></div>',
                '</form>',
                '</div>',
                '<div class="m-form-btnbox clearfix">',
                '<button class="btn lButton btn-left" tabIndex="4">登 录</button>',
                '<button class="btn rButtonSwitch btn-white btn-right" tabIndex="5">注 册</button>',
                '</div>',

                '</div>',
                '<div class="J-tab-main">',
                '<div class="m-form-wrapper">',
                '<form action="" class="rForm m-form" onSubmit="return false">',
                '<input type="text" style="display: none;" autocomplete="off"/>',
                '<input type="password" style="display: none;" autocomplete="off"/>',
                '<div class="m-form-item">',
                '<label for="rName">登录邮箱：</label>',
                '<input type="text" class="txt" id="rName" tabIndex="1" placeholder="填写您的邮箱地址"/>',
                '</div>',
                '<div class="m-form-item">',
                '<label for="rNickname">您的昵称：</label>',
                '<input type="text" class="txt" maxlength="16" id="rNickname" tabIndex="2" placeholder="2-8位汉字、数字或字母组成"/>',
                '</div>',
                '<div class="m-form-item">',
                '<label for="rPassword">登录密码：</label>',
                '<input type="password" class="txt" id="rPassword" tabIndex="3" autocomplete="off" placeholder="6-22个字母和数字组成"/>',
                '</div>',
                '<div class="m-form-item">',
                '<label for="rAPassword">确认密码：</label>',
                '<input type="password" class="txt" id="rAPassword" tabIndex="4" autocomplete="off"/>',
                '</div>',
                '<div class="m-form-item">',
                '<label for="rsCodeIpt">验&nbsp;&nbsp;证&nbsp;&nbsp;码：</label>',
                '<input type="text" class="txt txt-short" id="rsCodeIpt" tabIndex="5" placeholder="不区分大小写"/>',
                '<img src="" alt="验证码" id="rsCodeImg" class="s-code-img" />',
                '<a href="javascript:void(0);" class="m-form-tip J-change-scode">换一换</a>',
                '</div>',
                '<span class="rTip"></span>',
                '</form>',
                '</div>',
                '<div class="m-form-btnbox">',
                '<button class="btn btn-register rButton" tabIndex="6">立即注册，马上去看</button>',
                '</div>',
                '</div>',
                '<div class="d-gg">',
                '<a href="" target="_blank" class="d-gg-a">',
                '<img class="d-gg-img" src="">',
                '</a>',
                '</div>',
                '</div>'].join(""),

            onshow: function () {

                //面板内部登录，注册切换
                var user = cross.make("User");

                $("#dialogLogin, #dialogRegister").on("click", function (e) {
                    if (e.currentTarget.id == "dialogLogin") {
                        cap.flashCaptcha($("#lsCodeImg"));
                    } else if (e.currentTarget.id == "dialogRegister") {
                        cap.flashCaptcha($("#rsCodeImg"));
                    }
                    ;
                });

                //从登录面板跳转到注册面板
                $(".rButtonSwitch").on("click", function () {
                    $("#dialogRegister").trigger("click");
                });

                //登录和注册tab切换绑定事件
                Utility.tabSwitch($(".J-dialog-tab"));

                //绑定刷新验证码按钮
                cap.bindChangeCaptcha();

                //登录
                $('#lName').accountInput(".lTip");
                $('#lPassword').passwordInput('.lTip');
                $("#lsCodeIpt").sCodeInput(".lTip");

                //注册
                $('#rName').accountInput(".rTip");
                $('#rNickname').isNickname('.rTip');
                $('#rPassword').regPasswordInput('.rTip');
                $("#rsCodeIpt").sCodeInput(".rTip");
                //调用重复密码验证
                $('#rAPassword').passwordAgain('#rPassword', '.rTip');

                //注册事件
                user.submitR('.rButton', function () {
                    window.location.reload();
                });

                //登录事件
                user.submitL('.lButton');

                //载入广告，链式判断
                if (User.info && User.info.gg && User.info.gg["login_ad"]) {

                    //载入广告数据
                    $(".d-gg-a").attr("href", User.info.gg["login_ad"].link);
                    $(".d-gg-img").attr({
                        "title": User.info.gg["login_ad"].title,
                        "src": User.info.gg["login_ad"].img
                    });
                } else {
                    $(".d-gg").remove();
                    return;
                }

            }
        }),

        isConnection: function () {
            return !!$.cookie("webuid") || !!$.cookie("v_remember_encrypt") || !!$.cookie("PHPSESSID");
        },

        /*
         * @ description 返回是否登录
         * @ type 静态方法
         * @ param null
         * @ return bool
         */
        isLogin: function () {
            return this.UID ? true : false;
        },

        /*
         * @ description 处理登录弹窗
         * @ type 静态方法
         * @ param u: User的实例 func: 回调
         * @ return bool
         */
        // handleLoginDialog: function(u, func){

        //     instanceUser = u;

        //      //__User.showLoginDialog();

        //     if (func) { func() };

        // },

        loginSuccess: function () {

        },
        /**
         * getUserInfoCallback userInfo 请求完成后触发,外挂方法
         */
        handleAfterGetUserInfo: function () {

        },
        /**
         * 显示登录窗口
         * @type {[type]}
         */
        // showLoginDialog: function(){
        //     this.loginDialog();
        // },

        getUserInfo: function (successCallback) {
            var that = this;
            //如果没有登录，获取站点头部记录
            $.ajax({
                url: '/indexinfo',
                type: 'GET',
                dataType: 'json',
                cache: false,
                success: function (json) {
                    if (successCallback && json.ret) {
                        successCallback(json);
                    } else {
                        console.log("未登录")
                    }
                    User.DOWNLOADAPPURL = json.downloadAppurl;
                    User.IMG_URL = json.img_url;
                    User.QRCODE_IMG = json.qrcode_img;
                    that.handleAfterGetUserInfo();
                },
                error: function (json) {
                    Utility.log("server error!");
                }

            });
        },

        flashUserInfo: function () {
            //1. 加载首页我的关注加载
            //2. 加载隐身组件模块
            User.getUserInfo(function (json) {
                //如果登录成功
                user.getUserInfoSuccess(json, function (data) {

                    // 序列化主播顺序，热播>直播>休息
                    var sortByLiveStatus = function (obj) {

                        var hot = [],
                            live = [],
                            free = [];

                        for (var i = 0; i < obj.length; i++) {
                            switch (obj[i].live_status) {
                                case 0:
                                    free.push(obj[i]);
                                    break;
                                case 1:
                                    live.push(obj[i]);
                                    break;
                                case 2:
                                    hot.push(obj[i]);
                                    break;
                                default:
                                    free.push(obj[i]);
                            }
                        }

                        return hot.concat(live, free);
                    };

                    //加载我的关注数据
                    //if(data['myfav'].length != 0){
                    //    var tmp = renderItem(sortByLiveStatus(data['myfav']));
                    //}else{
                    //    var tmp = '<div class="main-tips">您暂时还没有关注的主播哟，快快去关注吧！</div>';
                    //}
                    //
                    //$("#fav").html(tmp);

                    //加载我的预约数据
                    var ordTmp = "";
                    var oneToManyTmp = "";

                    if (data['myres'].length != 0 || data['myticket'].length != 0) {
                        ordTmp = renderOrdItem(sortByLiveStatus(data['myres']));
                        oneToManyTmp = renderOneToMoreItem(sortByLiveStatus(data['myticket']));

                    } else {
                        ordTmp = '<div class="main-tips">您暂时还没有预约主播哟，快快查看一对一房间了立即预约吧！</div>';
                    }

                    $("#res").html(ordTmp + oneToManyTmp);

                    //邮箱弹窗相关.....  验证用户身份..
                    //如果未绑定安全邮箱..
                    if (!__User.INFO.safemail.length) {

                        //如果是老用户..
                        if (!__User.INFO.new_user) {

                            $(".mail-remind-text-old").show();

                            //var mailCheckClose = $(".mail-check-close");
                            //
                            //if(mailCheckClose.length){
                            //    $(".mail-check-close").show();
                            //}else{
                            //    //在其他页面开启提示框...
                            $(".user-safemail-remind").show();
                            //}

                        } else {
                            $(".mail-remind-text-new").show();
                        }

                        //邮箱弹窗关闭逻辑..
                        $(".mail-check-close").click(function () {
                            $(".mail-check-wrap").hide();
                            $(".user-safemail-remind").show();
                            //在用户右上角 增加提示框..
                        })
                    } else {
                        if ($("#member-index-safemail").length) {
                            //$("#member-index-safemail").hide();
                        }
                    }

                });

            });
        },

        /**
         * 显示登录窗口
         * @type {[type]}
         */
        showLoginDialog: function () {
            this.loginDialog.show();
            $("#dialogLogin").trigger("click");
        },

        /**
         * 显示注册窗口
         * @type {[type]}
         */
        showRegDialog: function () {
            this.loginDialog.show();
            $("#dialogRegister").trigger("click");
        }
    });

    window.User = __User;

})(typeof User !== "undefined" ? User : {}, window);