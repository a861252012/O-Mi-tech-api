(function ($) {

	var pluses = /\+/g;

	function encode(s) {
		return config.raw ? s : encodeURIComponent(s);
	}

	function decode(s) {
		return config.raw ? s : decodeURIComponent(s);
	}

	function stringifyCookieValue(value) {
		return encode(config.json ? JSON.stringify(value) : String(value));
	}

	function parseCookieValue(s) {
		if (s.indexOf('"') === 0) {
			// This is a quoted cookie as according to RFC2068, unescape...
			s = s.slice(1, -1).replace(/\\"/g, '"').replace(/\\\\/g, '\\');
		}

		try {
			// Replace server-side written pluses with spaces.
			// If we can't decode the cookie, ignore it, it's unusable.
			// If we can't parse the cookie, ignore it, it's unusable.
			s = decodeURIComponent(s.replace(pluses, ' '));
			return config.json ? JSON.parse(s) : s;
		} catch(e) {}
	}

	function read(s, converter) {
		var value = config.raw ? s : parseCookieValue(s);
		return $.isFunction(converter) ? converter(value) : value;
	}

	var config = $.cookie = function (key, value, options) {

		// Write

		if (value !== undefined && !$.isFunction(value)) {
			options = $.extend({}, config.defaults, options);

			if (typeof options.expires === 'number') {
				var days = options.expires, t = options.expires = new Date();
				t.setTime(+t + days * 864e+5);
			}

			return (document.cookie = [
				encode(key), '=', stringifyCookieValue(value),
				options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
				options.path    ? '; path=' + options.path : '',
				options.domain  ? '; domain=' + options.domain : '',
				options.secure  ? '; secure' : ''
			].join(''));
		}

		// Read

		var result = key ? undefined : {};

		// To prevent the for loop in the first place assign an empty array
		// in case there are no cookies at all. Also prevents odd result when
		// calling $.cookie().
		var cookies = document.cookie ? document.cookie.split('; ') : [];

		for (var i = 0, l = cookies.length; i < l; i++) {
			var parts = cookies[i].split('=');
			var name = decode(parts.shift());
			var cookie = parts.join('=');

			if (key && key === name) {
				// If second argument (value) is a function it's a converter...
				result = read(cookie, value);
				break;
			}

			// Prevent storing a cookie that we couldn't decode.
			if (!key && (cookie = read(cookie)) !== undefined) {
				result[name] = cookie;
			}
		}

		return result;
	};

	config.defaults = {};

	$.removeCookie = function (key, options) {
		if ($.cookie(key) === undefined) {
			return false;
		}

		// Must not alter options, thus extending a fresh object...
		$.cookie(key, '', $.extend({}, options, { expires: -1 }));
		return !$.cookie(key);
	};

})(jQuery); 

(function(factory) {
    if (typeof define === "function" && define.amd) {
        // AMD. Register as an anonymous module.
        define(["jquery"], factory);
    } else {
        factory(jQuery);
    }
}(function($) {
    'use strict';

    /**
     * 将固定dialog居中
     * @param  {[type]} ins [传入dialog this对象]
     * @return {[type]}     [null]
     */
    var _fixCenter = function(ins){
        var d = ins.$dialog;
        var $window = $(window);
        var dl = 0;
        var dt = 0;
        var ww = $window.width();
        var wh = $window.height();
        var ow = d.width();
        var oh = d.height();
        var left = (ww - ow) / 2 + dl;
        var top = (wh - oh) * 382 / 1000 + dt; //黄金比例
        var style = d[0].style;

        style.left = Math.max(parseInt(left), dl) + 'px';
        style.top = Math.max(parseInt(top), dt) + 'px';
    }

    /**
     * 将自动dialog居中，并根据浏览器拉伸自动定位
     * @param  {[type]} ins [传入dialog this对象]
     * @return {[type]}     [null]
     */
    var _autoCenter = function(ins){

        //dialog
        var d = ins.$dialog;

        //dialog的长宽
        var ow = d.width();
        var oh = d.height();

        //dialog的style
        var style = d[0].style;

        style.left = "50%";
        style.marginLeft = "-" + ow/2 + "px";
        style.top = "50%"; //黄金比例
        style.marginTop = "-" + oh/2 + "px";

    }

    /**
     * 简易模板生成
     * @param src:字符串模板, options: 要替换的key value
     */
    var _template = function(src, options, ori){

        var curStr;
        //$.support为特征检测，checkOn IE返回false
        if(!$.support.checkOn){
            curStr = src;
        }else{
            curStr = [];
            var len = src.length;
            var i;
            for(i=0; i<len; i++){
                curStr.push(src[i]);
            }
            curStr = curStr.join("");
        }

        var formatReg = new RegExp("#{([a-z0-9_]+)}", "ig");
        curStr = curStr.replace(formatReg, function(match, f1, index, srcStr){
            return options[f1]?options[f1]:(ori?match:"");
        });
        return curStr;

    }

    /**
     * [Dialog]
     * @param {[type]} options [传入dialog属性]
     */
    var Dialog = function(options){
        this.$main;
        this.$dialog;
        this.$shadow;
        this.$closeBtn;
        this.$buttonBox;

        //初始化参数
        this.options;
        this.originalOptions;
        this.buttonTarget;

        //初始化方法
        this.onshow;

        //初始化dialog
        this.init(options);
    }

    //弹窗个数
    var count = 0;
    var wrapperHTML = ['<div class="d-dialog">',
        '<div class="d-wrapper">',
        '<div class="d-close"></div>',
        '<div class="d-main">',
        '<div class="d-title">#{title}</div>',
        '<div class="d-content">#{content}</div>',
        '<div class="d-bottom"></div>',
        '</div>',
        '</div>',
        '</div>',
        '<div class="d-shadow"></div>'].join("");

    Dialog.DEFAULTS = {
        id: (new Date() - 0) + count,
        title: "Dialog",
        content: "这是Dialog",
        width: "auto",
        height: "auto",
        okValue: "确定",
        cancelValue: "取消",
        closeButtonDisplay: true,

        //用户点击的触发按钮
        cancelDisplay: true,
        cancelTextBtn: false,

        //定义目标点击按钮
        buttonTarget: null,

        //是否固定
        fixed: false,

        //是否聚焦
        autofocus: true
    }

    $.extend(Dialog.prototype, {

        //初始化dialog
        init: function(options){

            //初始化后，this.x 会以特权方法的形式挂载在对象上
            //获取options
            this.options = this.getOptions(options);
            this.originalOptions = this.options;

            //生成模板
            var tmp = _template(wrapperHTML, this.options),
                id = this.options.id,
                that = this;

            //生成节点
            this.$main = $(tmp);
            this.$closeBtn = this.$main.find(".d-close");
            this.$dialog = this.$main.siblings(".d-dialog");
            this.$shadow = this.$main.siblings(".d-shadow");
            this.$buttonBox = this.$main.find(".d-bottom");

            //设置dialog ID
            this.$dialog.attr("id", id);

            //this.$main.width(this.options.width);
            //this.$main.height(this.options.height);

            //bind close btn
            $(document).on("click", ".d-close", function(e){

                that.remove();

                e.stopPropagation();
            });

            count ++;
        },

        create: function(){
            // button handle
            this.options = this.getOptions(this.originalOptions);

            if (!$.isArray(this.options.button)) {
                this.options.button = [];
            }
            // title设置
            if (!this.options.title) {
                this.$main.find(".d-title").remove();
            };

            // 确定按钮
            if (this.options.ok) {
                this.options.button.push({
                    id: 'ok',
                    className: 'btn',
                    value: this.options.okValue,
                    callback: this.options.ok,
                    autofocus: true
                });
            }

            // 取消按钮
            if (this.options.cancel) {
                this.options.button.push({
                    id: 'cancel',
                    className: 'btn btn-white',
                    value: this.options.cancelValue,
                    callback: this.options.cancel,
                    display: this.options.cancelDisplay,
                    cancelTextBtn: this.options.cancelTextBtn
                });
            }

            //删除按钮
            if (this.options.closeButtonDisplay){
                this.$closeBtn.show();
            }else{
                this.$closeBtn.hide();
            }

            this.setButton(this.options.button);

            if (!this.options.button.length) {
                this.$main.find(".d-bottom").remove();
            };

        },

        //get default config
        getDefaults: function(){
            return Dialog.DEFAULTS;
        },

        //get options
        getOptions: function(options){
            return $.extend(true, {}, this.getDefaults(), options);
        },

        //向dialog传值
        setData: function(data){
            this.data = data;
            return this;
        },
        //show
        show: function(){

            this.create();
            $("body").append(this.$main);

            // 显示的时候触发
            if (this.options.onshow) {
                //解法1
                //原this.options.onshow中的this只存在于this.options中的值
                //将this中的方法挂载到 this options上，以便onshow方法调用
                // this.options = $.extend({}, this, this.options);
                // 这种方式不对。。。

                //解法2
                //这样做得好处是可以更好的让开发者理解onshow方法里面所调用的this
                this.onshow = this.options.onshow;
                this.onshow();
            };

            //居中
            //fix on June 21, 2016
            //必须在执行onshow以后才进行居中，否则当dialog内部高度变化时，居中无法完成
            if(this.options.fixed){
                _fixCenter(this);
            }else{
                _autoCenter(this);
            }

            //显示
            this.$dialog.show();
            this.$shadow.show();

            //焦点控制
            //若不控制焦点，enter回车键会触发dialog弹出按钮，而出现第二次弹窗
            //并且完成焦点控制后，当窗口弹出，用户可以直接输入内容
            var $inputArr = this.$dialog.find("input, textarea, select").not("input[type='button']"),
                $buttonArr = this.$dialog.find("input[type='button'], input[type='submit'], button, a");

            //先判断是否有表单，先聚焦表单
            setTimeout(function(){
                $inputArr.length ? $inputArr[0].focus() : ($buttonArr[0] && $buttonArr[0].focus());
            }, 0);

            //返回本身
            return this;
        },

        //hide dialog
        close: function(){
            this.$main.hide();
            return this;
        },

        //remove dialog
        remove: function(){
            this.$main.remove();
            delete $.dialog.list[this.id];

            //移除后触发的事件
            if (this.options.onremove) {
                this.options.onremove();
            };

            return this;
        },

        //button定义，arg
        setButton: function(args){
            args = args || [];
            var that = this;
            var html = '';
            var number = 0;
            this.callbacks = {};

            if (typeof args === 'string') {
                html = args;
                number ++;
            } else {
                $.each(args, function (i, val) {

                    var id = val.id = val.id || val.value;
                    var style = '';
                    var btnClass = val.cancelTextBtn ? 'btn-leave' : val.className;

                    that.callbacks[id] = val.callback;

                    if (val.display === false) {
                        style = ' style="display:none"';
                    } else {
                        number ++;
                    }

                    html +=
                        '<button'
                        + ' type="button"'
                        + ' class="' + btnClass + '"'
                        + ' i-id="' + id + '"'
                        + style
                        + (val.disabled ? ' disabled' : '')
                        + (val.autofocus ? ' autofocus class="ui-dialog-autofocus"' : '')
                        + '>'
                        + val.value
                        + '</button>';

                    that.$buttonBox
                        .on('click', '[i-id=' + id +']', function (e) {
                            var $this = $(this);
                            if (!$this.attr('disabled')) {
                                // IE BUG
                                that._trigger(id);
                            }
                            e.preventDefault();
                        });

                });
            }

            this.$buttonBox.html(html);
            return this;
        },

        setTitle: function(str){
            this.$main.find(".d-title").text(str);
            return this;
        },

        setBtnTarget: function($target){
            this.buttonTarget = $target;
            return this;
        },

        focus: function(){

        },

        blur: function(){

        },

        // 触发按钮回调函数
        _trigger: function (id) {
            var fn = this.callbacks[id];

            return typeof fn !== 'function' || fn.call(this) !== false ?
                this.close().remove() : this;
        }
    });

    //将dialog的实例挂载到$上
    $.dialog = function(options){
        var id = Dialog.DEFAULTS.id;
        if (options.id) { id = options.id };
        return $.dialog.list[id] = new Dialog(options);
    }

    //通过get获取dialog
    $.dialog.list = {};
    $.dialog.get = function(id){
        return id === undefined ? $.dialog.list : $.dialog.list[id];
    };

    //extend
    $.tips = function(c, callback){
        var tip = $.dialog({
            title: "提示",
            content: c,
            cancel: function(){},
            cancelValue: "关闭",
            onremove: function(){
                if (callback) {callback()};
            }
        });

        tip.show();
    }

}));

/**
 * @description 表单验证
 * @author Young
 * @param Utility, window
 */

(function(__validation, window){

    $.extend(__validation, {
        //判断是否是邮箱,返回bool
    	isEmail: function(str) {
    	    var reg = /^(([^<>()[\]\\.,#$\^\&\%\*\-+!;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    	    return reg.test(str);
    	},
    	isAccount: function(str) {
    	    var reg = /^[a-zA-Z0-9_\u4e00-\u9fa5]{2,8}$/;
    	    return reg.test(str);
    	},
        //中英文数字下划线6-16个字符
        isLoginAccount: function (str) {
            var reg = /^[a-zA-Z0-9_\u4e00-\u9fa5]{6,16}$/;
            //var reg = /^[a-zA-Z0-9_]{6,16}$/;
            return reg.test(str);
        },
        //判断是否是邮编，判断3位数到12位数,并包含字母和空格，返回bool
        isPost: function (str) {
            var patrn = /^[a-zA-Z0-9 ]{3,12}$/;
            if (!patrn.exec(str)) return false
            return true
        },
        //校验手机号码：必须以数字开头,返回bool //00852验证香港区号
        isMobile: function (str) {
            //严格
            var patrn = /^(13[0-9]|15[012356789]|18[0-9]|14[57]|00852)[0-9]{8}$/;
            //宽松
            //var patrn = /^(1)[0-9]{10}$/;
            return patrn.test(str);
        },
        //校验普通电话、传真号码：可以“+”开头，除数字外，可含有“-”
        isTel: function (str) {
            var patrn = /^[+]{0,1}(\d){1,3}[ ]?([-]?((\d)|[ ]){1,12})+$/;
            if (!patrn.exec(str)) return false;
            return true;
        },
        //判断是否是QQ号码，返回bool
        isQQ: function (str) {
            return /^\d{5,11}$/.test(str);
        },
        //判断是否是电话号码,手机，座机通吃，返回bool
        isPhoneNum: function (str) {
            return /^\d{5,20}$/.test(str);
        },
        isBankNum: function(str){
            return /^\d{6,19}$/.test(str);
        },
        isBankName: function(str){
            return this.isChinese(str) && /^.{1,6}$/.test(str);
        },
        //判断是否日期类型(例:2005-12-12)，返回bool
        isDate: function (str) {
            var reg = /^((((1[6-9]|[2-9]\d)\d{2})-(0?[13578]|1[02])-(0?[1-9]|[12]\d|3[01]))|(((1[6-9]|[2-9]\d)\d{2})-(0?[13456789]|1[012])-(0?[1-9]|[12]\d|30))|(((1[6-9]|[2-9]\d)\d{2})-0?2-(0?[1-9]|1\d|2[0-8]))|(((1[6-9]|[2-9]\d)(0[48]|[2468][048]|[13579][26])|((16|[2468][048]|[3579][26])00))-0?2-29-))$/;
            if (reg.test(str)) {
                return true;
            }
            return false;
        },
        //判断是否是合法的身份证号，返回bool
        isIdCardNo: function (num) {
            //reg15  reg15=/^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$/;
            //reg18  reg18=/^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{4}$/;
            //reg 综合15,18位量种，并可检测尾部X
            var reg = /^(\d{6})(18|19|20)?(\d{2})([01]\d)([0123]\d)(\d{3})(\d|X)?$/;
            if (reg.test(num)) {
                return true;
            }
            return false;
        },
        //判断是否是合法IP，返回bool
        isIP: function (str) {
            var reg = /^((2[0-4]\d|25[0-5]|[01]?\d\d?)\.){3}(2[0-4]\d|25[0-5]|[01]?\d\d?)$/;
            if (reg.test(str)) {
                return true;
            }
            return false;
        },
        //校验密码：只能输入6-15个字母、数字
        isPasswd: function (s) {
            var patrn = /^[a-zA-Z0-9]{6,15}$/;
            if (!patrn.exec(s)) return false;
            return true;
        },
        //判断判断是否中文,返回bool
        isChinese: function (str) {
            var str = str.replace(/(^\s*)|(\s*$)/g, '');
            if (!(/^[\u4E00-\uFA29]*$/.test(str)
                    && (!/^[\uE7C7-\uE7F3]*$/.test(str)))) {
                return false;
            }
            return true;
        },
        //1-16个中英文，不含数字
        isChEn: function (s) {
            var patrn = /^[a-zA-Z\u4E00-\u9FA5]{2,16}$/;
            if (!patrn.exec(s)) return false;
            return true;
        },
        //判断是否是一个图片格式的文件jpg|jpeg|png|swf|gif，返回bool
        isImg: function (str) {
            var objReg = new RegExp("[.]+(jpg|jpeg|png|swf|gif)$", "gi");
            if (objReg.test(str)) {
                return true;
            }
            return false;
        },
        //判断是否是整型,返回bool
        isInteger: function (str) {
            return /^-?\d+$/.test(str);
        },
        //判断是否是一个浮点数，返回bool
        isFloat: function (str) {
            return /^(-?\d+)(\.\d+)?$/.test(str);
        },
    	//是否为正整数
    	isPositiveInteger: function (str) {
    	    return /^\d+$/.test(str);
    	}
    });

    window.Validation = __validation;

})(typeof Validation !== "undefined" ? Validation: {}, window);


/**
 * @description 实用工具
 * @author Young
 * @param Utility, window
 */

(function(__utility, window){

	$.extend(__utility, {

		/**
		 * @description console.log() 以兼容IE情况
		 * @author Young
		 * @param string: 错误信息
		 */

		log: function(str){
			window.console && console.log(str);
		},

		/**
		 * @description 简易模板引擎
		 * @author Young
		 * @param string: 字符串模板
		 */
		template: function(src, options, ori){

		    var curStr;
		    //$.support为特征检测，checkOn IE返回false
		    if(!$.support.checkOn){
		        curStr = src;
		    }else{
		        curStr = [];
		        var len = src.length;
		        var i;
		        for(i=0; i<len; i++){
		            curStr.push(src[i]);
		        }
		        curStr = curStr.join("");
		    }

		    var formatReg = new RegExp("#{([a-z0-9_]+)}", "ig");
		    curStr = curStr.replace(formatReg, function(match, f1, index, srcStr){
		        return options[f1]?options[f1]:(ori?match:"");
		    });
		    return curStr;

		},

		/**
		 * 切换域
		 * @param  {[type]} str      [需要替换的字符串]
		 * @param  {[type]} afterStr [替换后的字符串]
		 * @return {[type]}          [origin]
		 */
		switchOrigin: function(str, afterStr){
			return "http://" + location.host.replace(str, afterStr);
		},


    /**
     * @description J-tab 列表切换初始化
     * @author Peter
     * @param: null
     */
    tabSwitch: function($btn, callback){

      if(arguments.length == 0){
        $btn = $("body");
      }

      $btn.find('.J-tab-menu li:not(.close)').on("click", function() {
        var $tab = $(this).closest(".J-tab");
        $(this).parent().children('li').removeClass('active');
        $(this).addClass('active');
        var tabMain = $tab.find('.J-tab-main');
        tabMain.removeClass('active');
        tabMain.eq($(this).index()).addClass('active');
        if (callback) {callback();};
      });

    }


  });

	window.Utility = __utility;

})(typeof Utility !== "undefined" ? Utility: {}, window);
(function (name, factory) {

    if (typeof define === 'function' && define.amd) {

        // AMD
        define(["../vendor/jquery/jquery.min.js"], factory);

    } else if (typeof module === "object" && module.exports) {

        // Node, CommonJS-like
        module.exports = factory();

    } else {

        // Browser globals (this is window)
        this[name] = factory(jQuery);

    }

}("Captcha", function ($) {

	var Captcha = function(){

		/**
		 * @description 刷新验证码flashSCode
		 * @author Young
		 * @param: image jquery对象
		 */
		this.flashCaptcha = function($img){
		    var date = (new Date()).valueOf();
		    $img.attr("src", "/captcha?t=" + date);
		}

		/**
		 * @description 绑定按钮刷新验证码changeSCode
		 * @author Young
		 * @param: null
		 */
		this.bindChangeCaptcha = function(){

			var that = this;

		    $(".J-change-scode").on("click", function(){
		        var $img = $(this).siblings(".s-code-img");
		        that.flashCaptcha($img);
		    });
		}

	};

	//在amd 和 cmd中暴露给当前作用域
	return Captcha;

}));
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
(function(__Dropdown, window){

    'use strict';

    __Dropdown = function(options){

        var that = this;

        /**
         * 私有方法：展开dropdown
         * @return {[type]} [description]
         */
        var open = function(ins){
            ins.objList.show();
            ins.state = true;
        }

        /**
         * 私有方法：关闭dropdown
         */
        var close = function(ins){
            ins.objList.hide();
            ins.state = false;
        }

        /**
         * 初始化状态机
         * @type {Object}
         */
        this.initState = {
            "dropdown": function(){
                open(that);
            },
            "dropup": function(){
                close(that);
            }
        }

        /**
         * 初始化下拉状态，false 关闭，open打开
         * @type {bool}     
         */
        this.state = false;

        /**
         * 初始化item点击事件
         * @type {function}
         */
        this.handleItem;

        /**
         * 初始化程序
         * @return {[type]} [null]
         */
        this.init = function(){
            
            //初始化option
            this.setOptions();

            //初始化界面对象
            this.target = $("#"+this.options.id);
            this.objTtile = this.target.find(".dropdown-title-text");
            this.objList = this.target.find(".dropdown-list");

            //初始化item点击事件
            this.handleItem = this.options.handleItem;

            //绑定事件
            this.bindEvents();
        }

        //设置options
        this.setOptions = function(){
            return this.options = options;
        }

        //获取options
        this.getOptions = function(){
            return this.options;
        }

        /**
         * 绑定title点击事件和 item点击事件
         * @return {[type]} [description]
         */
        this.bindEvents = function(){

            this.objTtile.on("click", function(){

                if (!that.state) {
                    that.setState("dropdown");
                }else{
                    that.setState("dropup");
                };
                
            });

            this.objList.on("click", ".dropdown-item", function(e){

                var title = e.target.innerText;
                that.setTitle(title);
                that.setState("dropup");

                //关闭list后，执行回调
                that.handleItem(e.target);

            });
        }

        /**
         * 设置dropdown状态
         * @param {[type]} state [状态]
         */
        this.setState = function(state){
            this.initState[state]();
        }

        /**
         * 设置title
         * @param {[type]} title [title]
         */
        this.setTitle = function(title){
            this.objTtile.text(title);
        }

        //初始化
        this.init();
    }

    window.Dropdown = __Dropdown;

})(typeof Dropdown !== "undefined" ? Dropdown : {}, window);
(function (name, factory) {

    if (typeof define === 'function' && define.amd) {

        // AMD
        define(["../vendor/jquery/jquery.min.js"], factory);

    } else if (typeof module === "object" && module.exports) {

        // Node, CommonJS-like
        module.exports = factory();

    } else {

        // Browser globals (this is window)
        this[name] = factory(jQuery);

    }

}("UserService", function ($) {

    /**
     * @description pw编码
     * @type 私有方法
     * @author Young
     * @param string: 编码前的密码
     * @return string: 编码后的密码
     */
    var encode = function(s){
        var es = [], c='', ec='';
        s = s.split('');
        for(var i=0, length=s.length; i<length; i++){
            c = s[i];
            ec = encodeURIComponent(c);
            if(ec==c){
                ec = c.charCodeAt().toString(16);
                ec = ('00' + ec).slice(-2);
            }
            es.push(ec);
        }
        return es.join('').replace(/%/g,'').toUpperCase();
    }

    //User静态方法扩展
	var User = {

		/**
		 * 登录请求
		 * @return {[type]} [description]
		 */
		actionLogin: function(options){

            $.ajax({
                
                // url: location.origin.replace("http://v.", "http://www.") + '/login',
                // type: 'post',
                // dataType: "jsonp",
                // jsonp: "callback",
                // jsonpCallback: "cb",

                url: '/login',
                type: 'post',
                dataType: "json",

                data: {
                    "uname": options.username,
                    "password": encode(options.password),
                    "sCode": options.captcha,
                    "v_remember": options.remember,
                },

                success: function(res){
                    if(res.status == 1){
                        if (options.sucCallback) { options.sucCallback(res) };
                    } else {
                    	if (options.errCallback) { options.errCallback(res) };
                    }
                },

                error: function(res){
                    Utility.log("login server error");
                }

            });

		},

		/**
		 * 注册请求
		 * @return {[type]} [description]
		 */
		actionRegister: function(options){

            $.ajax({

                url: '/reg',
                type: 'post',
                dataType: "json",

                data:{
                    "username": options.username,
                    "nickname": options.nickname,
                    "password1": encode(options.password),
                    "password2": encode(options.repassword),
                    "captcha": options.captcha
                },

                success: function(res){
                    if(res.status == 1){
                        if (options.sucCallback) { options.sucCallback(res) };
                    } else {
                        if (options.errCallback) { options.errCallback(res) };
                    }
                },

                error: function(res){
                    Utility.log("register server error");
                }
                
            });

		}

	};

    return User;

}));
$(function(){

    //tabswitch
    Utility.tabSwitch();

    //初始化header info
    if ($("#livePage").length == 0 && $("#loginPage").length == 0) {
        loginInfoInit();
    };

    //var user = cross.make("User");
    //设置代理
    setAgent();

});

/**
 * @description 时间比较 -- start时间是否超过end时间
 * @author Young
 * @param: null
 */
var timeComparing = function(id){
    var $box = $("#" + id),
        $startTime = $box.find(".J-start"),
        $endTime = $box.find(".J-end"),
        $form = $box.find(".btn");

    var startVal = $startTime.val();
    var endVal = $endTime.val();

    var day1 = new Date(startVal.replace(/-/g,"/"));
    var day2 = new Date(endVal.replace(/-/g,"/"));
    var m = (day2.getTime() - day1.getTime())/(1000*60*60*24);

    if (m < 0) {
        $.tips("请输入正确的时间起始点。");
        return false;
    }else{
        $box.submit();
    }
}

/**
 * @description 简易字符串计数器，以后将做修改 // todo
 * @author Young
 * @param: null
 */
var wordsCount = function(input, tips, num){
    $(input).on('keydown keyup blur mousecenter mouseleave mousemove',function(){
        var len = $(this).val().length || 0,
            chrLen = num - len;
        tips && $(tips).text(chrLen > 0 ? chrLen : 0);
        if(chrLen < 0){
            $(this).val($(this).val().substring(0, num))
            return false;
        }
    });
}


/**
 * @description 用户相关验证
 * @author Peter
 * @param: null
 */
$.fn.extend({

    /*清除input同级的提示icon*/
    removeVCIcon: function(){
        return this.each(function(){
            var $this = $(this);
            $this.siblings(".i-vc").remove();
        });
    },

    /*验证后面添加icon方法*/
    afterIcon: function(tmp) {
        return this.each(function() {
            $(this).removeVCIcon();
            $(this).after(tmp);
        });
    },

    /**
     * 邮箱验证
     * tip 验证输入框
     * isReplicateCheck 重复查询
     **/
    accountInput: function (tip, isReplicateCheck){

        return this.each(function() {

            $(this).on("focus blur", function(e){

                var $that = $(this);
                var val = $that.val()
                if (val.length == 0) {
                    $(tip).html("请输入您的邮箱！").css({"color":"#29a2ff"});
                    $that.afterIcon(vcIconInfoTMP);
                };

            }).on("keyup", function(){

                var $that = $(this);
                var val = $.trim($that.val());

                if (val.length == 0) {
                    return;
                }else{

                    if(val.length < 6 ){
                        $(tip).html("您的邮箱地址过短！").css("color", "#c1111c");
                        $that.afterIcon(vcIconWarnTMP);
                    }else if(val.length > 30){
                        $(tip).html("您的邮箱地址过长！").css("color", "#c1111c");
                        $that.afterIcon(vcIconWarnTMP);
                    }else if(!Validation.isEmail(val)){
                        $(tip).html("您的邮箱格式不正确！").css("color", "#c1111c");
                        $that.afterIcon(vcIconWarnTMP);
                    }else{
                        isReplicateCheck ? isReplicateCheck : true;

                        if(!isReplicateCheck){
                            $(tip).html("");
                            $that.afterIcon(vcIconCorrectTMP);
                            return;
                        }

                        if( tip == '.rTip'){
                            $.ajax({
                                url: '/verfiyName',
                                type: 'GET',
                                dataType: 'json',
                                data:{
                                    type: 'username',
                                    username: val
                                },
                                success:function(res){
                                    if(res.data == 0){
                                        $(tip).html(res.msg).css("color", "#c1111c");
                                        $that.afterIcon(vcIconWarnTMP);
                                    }else{
                                        $(tip).html("");
                                        $that.afterIcon(vcIconCorrectTMP);
                                    }
                                }
                            });
                        }else{
                            $(tip).html("");
                            $that.afterIcon(vcIconCorrectTMP);
                        }
                    }
                }
            });
        });
    },

    /**
     * 昵称验证
     * tip 验证输入框
     * isReplicateCheck 重复查询
     */
    isNickname: function(tip, isReplicateCheck){
        return this.each(function(){

            $(this).on("focus blur", function(){
                var $that = $(this);
                var val = $that.val();
                if (val.length == 0) {
                    $(tip).html("请输入昵称！").css({"color":"#29a2ff"});
                    $that.afterIcon(vcIconInfoTMP);
                };
            });

            $(this).on("keyup", function(){

                var $that = $(this);
                var val = $.trim($that.val());

                if (val.length == 0) {
                    return;
                }else if(!Validation.isAccount($that.val())){
                    $(tip).html("注册昵称不能使用/:;\\空格,等特殊符号！(2-8位的昵称)").css("color", "#c1111c");
                    $that.afterIcon(vcIconWarnTMP);
                }else{

                    isReplicateCheck ? isReplicateCheck : true;

                    if (!isReplicateCheck){
                        $(tip).html("");
                        $that.afterIcon(vcIconCorrectTMP);
                        return;
                    }

                    $.ajax({
                        url: '/verfiyName',
                        type: 'GET',
                        dataType: 'json',
                        data: {
                            type: 'nickname',
                            username: val
                        },
                        success: function(res){
                            if(res.data == 0){
                                $(tip).html(res.msg).css("color", "#c1111c");
                                $that.afterIcon(vcIconWarnTMP);
                            }else{
                                $(tip).html("昵称格式输入正确！").css("color", "#29a2ff");
                                $that.afterIcon(vcIconCorrectTMP);
                            }
                        }
                    });
                }

            });
        });
    },

    /*密码验证*/
    passwordInput: function (tip){

        return this.each(function (){

            var $that = $(this);

            $that.on("focus blur", function (){
                var val = $.trim($that.val());
                if (val.length == 0) {
                    $(tip).html("请输入您的密码！").css("color", "#29a2ff");
                    $that.afterIcon(vcIconInfoTMP);
                };
            });

            $that.on("keyup", function (){
                var val = $that.val();
                var pwReg = /^[0-9a-zA-Z]{6,22}$/;
                if (val.length == 0){
                    $(tip).html("请输入您的密码！").css("color", "#c1111c");
                    $that.afterIcon(vcIconWarnTMP);
                }else if (!pwReg.test(val)){
                    $(tip).html("密码格式错误！").css("color", "#c1111c");
                    $that.afterIcon(vcIconWarnTMP);
                }else{
                    $(tip).html("");
                    $that.afterIcon(vcIconCorrectTMP);
                }
            });

        });

    },

    /*注册密码验证*/
    regPasswordInput: function (tip){

        return this.each(function (){

            var $that = $(this);

            $that.on("focus blur", function (){
                var val = $.trim($that.val());
                if (val.length == 0) {
                    $(tip).html("请输入新密码！").css("color", "#29a2ff");
                    $that.afterIcon(vcIconInfoTMP);
                };
            });

            $that.on("keyup", function (){
                var val = $that.val();

                //可能为数字，大小写字母
                var pwReg = /^[0-9a-zA-Z]{6,22}$/;
                //过滤数字
                var numReg = /^\d{6,22}$/;

                if (val.length == 0) {
                    $(tip).html("请输入您的密码！").css("color", "#c1111c");
                    $that.afterIcon(vcIconWarnTMP);
                }else if(numReg.test(val)){
                    $(tip).html("不能全是数字！").css("color", "#c1111c");
                    $that.afterIcon(vcIconWarnTMP);
                }else if (!pwReg.test(val)){
                    $(tip).html("密码格式错误！").css("color", "#c1111c");
                    $that.afterIcon(vcIconWarnTMP);
                }else{
                    $(tip).html("");
                    $that.afterIcon(vcIconCorrectTMP);
                }
            });

        });

    },

    //验证码验证
    sCodeInput: function (tip){

        return this.each(function (){

            var $that = $(this);

            $that.on("focus blur", function (){
                var val = $.trim($that.val());
                if (val.length == 0) {
                    $(tip).html("请输入验证码！").css("color", "#29a2ff");
                };
            });

            $that.on("keyup", function (){
                var pw = $(this).val();
                var codeReg = /^[a-zA-Z0-9]{4}$/;
                if (pw.length == 0){
                    $(tip).html("您的验证码不能为空！").css("color", "#c1111c");
                }else if(!codeReg.test(pw)){
                    $(tip).html("请输入4位由数字或字母组成的验证码！").css("color", "#c1111c");
                }else{
                    $(tip).html("");
                }
            });

        });
    },

    //重复密码验证方法
    passwordAgain: function(originalPwd, tip){
        return this.each(function(){
            var $that = $(this);

            $that.on("focus blur", function() {
                var oVal = $that.val();
                if (oVal.length == 0) {
                    $(tip).html("请输入确认密码！").css("color", "#29a2ff");
                    $that.afterIcon(vcIconInfoTMP);
                };
            });

            $that.on("keyup", function(){
                var pVal = $(originalPwd).val();
                var oVal = $that.val();

                if(pVal === oVal){
                    if (pVal == "") {
                        $(tip).html('确认密码不能为空').css("color", "#c1111c");
                        $that.afterIcon(vcIconWarnTMP);
                    }else{
                        $(tip).html('');
                        $that.afterIcon(vcIconCorrectTMP);
                    };
                }else{
                    $(tip).html('两次密码输入不同').css("color", "#c1111c");
                    $that.afterIcon(vcIconWarnTMP);
                }
            });
        });
    }

});

//验证设置icon图标
var vcIconCorrectTMP = '<span class="i-vc i-vc-correct"></span>';
var vcIconWarnTMP = '<span class="i-vc i-vc-warn"></span>';
var vcIconInfoTMP = '<span class="i-vc i-vc-info"></span>';

/**
 * @description 简易URL分析，已知key，获取value
 * @author: Young
 * @param: String value值
 */
var getLocation = function(p){
    var reg = new RegExp("(^|&)" + p + "=([^&]*)(&|$)");
    var get = location.search.substr(1).match(reg);
    return get!= null ? decodeURIComponent(get[2]) : '';
}


/**
 * @description 第二邮箱验证绑定
 * @author: Young
 * @param: null
 */
// var secMailCheck = function(){
//     var $close = $(".mail-check-close");
//     var $link = $(".mail-check-reg");

//     $close.on("click", function(){
//         $(".mail-check-wrap").hide();
//     });

//     $link.on("click", function(){
//         $(".mail-check-wrap").hide();
//     });
// }


/**
 * 获取首页数据，主播all + 排行榜rank
 * cat: 数据类型
 */
var getIndexData = function(cat, successCallback, errorCallback){
    $.ajax({
        url: '/videoList',
        data: {
            "_": (new Date()).valueOf() + randomString(),
            "type": cat
        },
        type: "GET",
        dataType: "JSON",
        error: function(ret){
            console.log("More data fetch fail");
            errorCallback(ret);
        },
        success: successCallback
    });
}

window.currentVideo = {};
/**
 * @description 首页四种类型视频列表item组装
 * @author Young
 * @param arr: 每一项的列表数组, url: 视频path(不接roomid):
 */
var renderItem = function(arr){
    var tmp = "",
        url = "/",
        len = arr.length;
        //len = 3;

    for (var i = 0; i < len; i++) {
        var data = arr[i];
        var lvType = generateLvTypeHTML(data['lv_type']);

        //数据容错过滤
        if (typeof data !== "object" || data === null) {
            continue;
        };

        if(data['live_time']){
            if ( data['live_time'].indexOf("时") > -1 ) {
                var index = data['live_time'].indexOf("钟");
                if (index > -1) {
                    data['live_time'] = data['live_time'].substring(0, index + 1);
                };

            };
            data['live_time'] = data['live_time'].replace(/([0-9]+)/g, function(s){
                return '<span>'+s+'</span>';
            });
        }

        data.tips = '';

        //限制房间图标
        if(data["enterRoomlimit"] == 1) {
            data.isLimit = '<span class="limit"></span>';
            data.tips = "该房间有条件限制";
            //鼠标移动到主播列表上显示的内容

        } else {
            data.isLimit = '';
        }

        /**
         * @ desctiption 新增置顶图标
         * @ author  Seed
         * @ date 2016-11-9
         */

        if(data["top"] == 1){
            data.isTop = "<div class='c-icon top'>置顶</div>";
        }

        //密码房间图标
        if(data["tid"] == 2) {
            data.isLock = '<span class="lock"></span>';
            data.tips = "进入该房间需要密码";
        } else {
            data.isLock = '';
        }

        //鼠标移动到主播列表上显示的内容
        data["enterRoomlimit"] == 1 ? data.tips = "该房间有限制" : data["tid"] == 2 ? data.tips = "该房间需要密码才能进入" : data.tips = "";

        //时长房间图标
        if(data["timecost_live_status"] == 1){
            data.isTimeCostIcon = '<span class="c-icon">时长</span>';
            data.isTimeCost = 'timecost';
            data.timeCost = 'data-timecost="'+ data['timecost'] + '"';
            data.tips = "该房间以观看时长计费";
        } else {
            data.isTimeCostIcon = '';
            data.isTimeCost = '';
            data.timeCost = '';
        }

        //跳转链接设置
        //tid == 2 为密码房间 timecost_live_status == 1 为时长房间
        //密码房间和时长房间不需要跳转链接，将href重置
        data["tid"] == 2 || data["timecost_live_status"] ? data.videoPath = 'href="javascript:;"' : data.videoPath = 'href="'+ (url + data['rid'] + Config.liveMode) +'" target="_blank"';
        //data["tid"] == 2 || data["timecost_live_status"] ? data.videoPath = 'href="javascript:;"' : data.videoPath = 'href="'+ (url + data['rid']) +'" target="_blank"';

        switch(data.live_status){
            case 0:
                data.status_color = "free";
                data.status_title = "未开播";
                break;
            case 1:
                data.status_color = "live";
                data.status_title = "直播";
                break;
            case 2:
                data.status_color = "hot";
                data.status_title = "热播";
                break;
            default:
                data.status_color = "free";
                data.status_title = "未开播";
        }

        var _number = (data["tid"] == 2 ? 1 : 0) + (data["enterRoomlimit"] == 1 ? 1 : 0) + (data.one_to_many_status ? 1 : 0) + (data["timecost_live_status"] == 1 ? 1 : 0);
        data.room_type = data.one_to_many_status ? '<div class="room_type">一对多</div>' : '';

        (data['new_user'] && data['new_user'] == 800000) ? data.isNewUser = '<div class="badge badge800000"></div>' : data.isNewUser = '';

data['headimg'] = data['headimg'] ? window.IMG_PATH + "/" + data['headimg'] : Config.imagePath + '/vzhubo.jpg';
        tmp += '<div class="l-list" title="'+ data.tips +'" data-tid="'+ data.tid +'" data-roomid="' + data.rid + '" data-isLimited="'+ data.enterRoomlimit +'"'+ data.isTimeCost + ' ' + data.timeCost +'>'+
                 '<a '+ data.videoPath +' class="l-block">'+
                    '<img src="'+ data['headimg']+'" alt="' + data['username'] + '"/>'+
                    '<div class="state icon-default">' +
                        '<div class="c-icon-bar ' + 'bar' + data.rid + '" style="width:' +  (29 * _number + (data.origin == 11 ? 0 : 25)) + 'px">' +
                        (data.origin == 11 ? '' : "<span class='mobile'></span>") +
                        data.isTimeCostIcon +
                        data.isLimit +
                        data.isLock +
                        '</div>' +
                    '</div>' +
                    (data['isTop'] == null ? '' : data['isTop']) +
                    '<div class="status ' + data.status_color + '">'+ data.status_title +'</div>'+ data.isNewUser +
                    '<div class="play"></div>' +
                    '<div class="content">'+
                        '<div class="c-username">' + '<div class="username">'+data['username']+'</div>' + '</div>'+
                            '<div class="thumb-bar"></div>' +
                        '</div>'+
                        '<div class="state">' +
                        '<div class="c-icon-bar ' + 'bar' + data.rid + '" style="width:' +  (44 * _number + (data.origin == 11 ? 0 : 25)) + 'px">' +
                        data.room_type +
                        '</div>' +
                        '</div>' +
                    '</div>'+
                '</a>'+
            '</div>';
    };

    return tmp;
}

/**
 * @description 一对一列表组装
 * @author Young
 * @param arr: 每一项的列表数组, url: 视频path(不接roomid):
 */
var renderOrdItem = function(arr){

    var tmp = "",
        url = "/",
        roomType = 'ordRoom'; //房间列表类型，默认一对一房间

    for (var i = 0; i < arr.length; i++) {
        var data = arr[i];
        var lvType = generateLvTypeHTML(data['lv_type']);

        //数据容错过滤
        if (typeof data !== "object" || data === null) {
            continue;
        };

        data.videoPath = 'href="javascript:;"';

        data['headimg'] = data['headimg'] ? window.IMG_PATH + "/" + data['headimg'] : Config.imagePath + '/vzhubo.jpg';


        switch(Number(data['appoint_state'])){
            case 1:
                data.btnReserve = '<span class="btn btn-s btn-around btn-reserve">立即预约</span>';
                break;
            case 2:
                data.btnReserve = '<span class="btn btn-s btn-around btn-reserve btn-disabled" >正在约会</span>';
                break;
            case 3:
                data.btnReserve = '<span class="btn btn-s btn-around btn-reserve btn-disabled" >已被预约</span>';
                break;
            default:
                data.btnReserve = '<span class="btn btn-s btn-around btn-reserve">立即预约</span>';
        }

        data.tips = '';

        //限制房间图标
        if(data["enterRoomlimit"] == 1) {
            data.isLimit = '<span class="limit"></span>';
            data.tips = "该房间有条件限制";
            //鼠标移动到主播列表上显示的内容

        }else{
            data.isLimit = '';
        }

        /**
         * @ desctiption 新增置顶图标
         * @ author  Seed
         * @ date 2016-11-9
         */

        if(data["top"] == 1){
            data.isTop = "<div class='c-icon top'>置顶</div>";
        }

        //密码房间图标
        if(data["tid"] == 2) {
            data.isLock = '<span class="lock"></span>';
            data.tips = "进入该房间需要密码";
        }else{
            data.isLock = '';
        }

        //鼠标移动到主播列表上显示的内容
        data["enterRoomlimit"] == 1 ? data.tips = "该房间有限制" : data["tid"] == 2 ? data.tips = "该房间需要密码才能进入" : data.tips = "";

        //时长房间图标
        if(data["timecost_live_status"] == 1){
            data.isTimeCostIcon = '<span class="c-icon">时长</span>';
            data.isTimeCost = 'timecost';
            data.timeCost = 'data-timecost="'+ data['timecost'] + '"';
            data.tips = "该房间以观看时长计费";
        }else {
            data.isTimeCostIcon = '';
            data.isTimeCost = '';
            data.timeCost = '';
        }

        data.room_type = data.one_to_many_status ? '<div class="room_type">一对多</div>' : '';

        // 判断我的预约
        if('undefined' != typeof data['listType'] && data['listType'] == 'myres') {
            data.videoPath = 'href="'+ (url + data['uid']) + Config.liveMode +'"';
            data.btnReserve = '<span class="btn btn-s btn-around btn-reserve">进入房间</span>';
            roomType = "";
        }

        var _number = (data["tid"] == 2 ? 1 : 0) + (data["enterRoomlimit"] == 1 ? 1 : 0) + (data.one_to_many_status ? 1 : 0) + (data["timecost_live_status"] == 1 ? 1 : 0);

        (data['new_user'] && data['new_user'] == 800000) ? data.isNewUser = '<div class="badge badge800000"></div>' : data.isNewUser = '';

        tmp += '<div class="l-list '+ roomType +'" data-appointstate="'+ data['appoint_state'] +'" data-duration="'+ data["live_duration"] +'" data-points="'+ data["points"]+'" data-starttime="'+ switchToZhTime(data["starttime"]) +'" data-roomid="'+data.id+'">'+
            '<a '+ data.videoPath +' class="l-block" target="_blank">'+
                '<img src="'+ data['headimg']+'" alt="' + data['username'] + '"/>'+
                '<div class="state icon-default">' +
                    '<div class="c-icon-bar ' + 'bar' + data.rid + '" style="width:' +  (29 * _number + (data.origin == 11 ? 0 : 25)) + 'px">' +
                        (data.origin == 11 ? '' : "<span class='mobile'></span>") +
                        data.isTimeCostIcon +
                        data.isLimit +
                        data.isLock +
                    '</div>' +
                '</div>' +
                (data['isTop'] == null ? '' : data['isTop']) +
                '<div class="play-ord">'+

                    '<span class="l-price">' + data.points + "钻 (" + data['live_duration'] + ')</span>'+

                    data.btnReserve +
                '</div>'+
                data.isNewUser +
                '<div class="play"></div>' +
                '<div class="content">'+
                    '<div class="c-username">' + '<div class="username">'+data['username']+'</div>' + '</div>'+
                    '<div class="state">' +
                    '<div class="c-icon-bar ' + 'bar' + data.rid + '" style="width:' +  (44 * _number + (data.origin == 11 ? 0 : 25)) + 'px">' +
                    data.room_type +
                    '</div>' +
                    '</div>' +
                '</div>'+
            '</a>'+
        '</div>';
    };

    return tmp;
}

/**
 * @description 将日期转为中文日期
 * @author Young
 * @param time(03-04 03:20 switch to 3月4日 03:20)
 */
var switchToZhTime = function(oriTime){
    if(oriTime.length == 0 || oriTime == null) return;
    var dateNum = oriTime.split(" ")[0];
    var date = parseInt(dateNum.split("-")[0], 10) + "月" + parseInt(dateNum.split("-")[1], 10) + "日";
    var time = oriTime.split(" ")[1];
    return date + " " + time;
}

/**
 * @description 将日期转为中文日期
 * @author Young
 * @param time(03-04 03:20 switch to 3月4日 03:20)
 */
var generateLvTypeHTML = function(lvType){

    var typeTHML = "";
    switch(lvType){
        case 1:
            typeTHML = "<span class='lvtype lvtype1'></span>";
            break;
        case 2:
            typeTHML = "<span class='lvtype lvtype2'></span>";
            break;
        case 3:
            typeTHML = "<span class='lvtype lvtype3'></span>";
            break;
        default:
            typeTHML = "<span class='lvtype lvtype1'></span>";
    }

    return typeTHML;
}

/**
 * @description 首页一对多列表（门票）
 * @author Young
 * @param arr: 每一项的列表数组, url: 视频path(不接roomid):
 */
var renderOneToMoreItem = function(arr){

    var tmp = "",
        url = "/",
        roomType = 'ticketRoom'; //房间列表类型，默认一对一房间

    for (var i = 0; i < arr.length; i++) {
        var data = arr[i];
        var duration = data["duration"];
        var roomid = data["rid"];
        var oneToManyId = data["id"];
        var startTime = switchToZhTime(data['start_time']);
        var lvType = generateLvTypeHTML(data['lv_type']);

        //数据容错过滤
        if (typeof data !== "object" || data === null) {
            continue;
        };

        data.videoPath = 'href="javascript:;"';

        data['headimg'] = data['headimg'] ? window.IMG_PATH + "/" + data['headimg'] : Config.imagePath + '/vzhubo.jpg';
        data.btnReserve = '<span class="btn btn-s btn-around btn-reserve">立即购票</span>';

        data.tips = '';

        //限制房间图标
        if(data["enterRoomlimit"] == 1) {
            data.isLimit = '<span class="limit"></span>';
            data.tips = "该房间有条件限制";
            //鼠标移动到主播列表上显示的内容

        }else{
            data.isLimit = '';
        }

        /**
         * @ desctiption 新增置顶图标
         * @ author  Seed
         * @ date 2016-11-9
         */

        if(data["top"] == 1){
            data.isTop = "<div class='c-icon top'>置顶</div>";
        }

        //密码房间图标
        if(data["tid"] == 2) {
            data.isLock = '<span class="lock"></span>';
            data.tips = "进入该房间需要密码";
        }else{
            data.isLock = '';
        }

        //鼠标移动到主播列表上显示的内容
        data["enterRoomlimit"] == 1 ? data.tips = "该房间有限制" : data["tid"] == 2 ? data.tips = "该房间需要密码才能进入" : data.tips = "";

        //时长房间图标
        if(data["timecost_live_status"] == 1){
            data.isTimeCostIcon = '<span class="c-icon">时长</span>';
            data.isTimeCost = 'timecost';
            data.timeCost = 'data-timecost="'+ data['timecost'] + '"';
            data.tips = "该房间以观看时长计费";
        }else {
            data.isTimeCostIcon = '';
            data.isTimeCost = '';
            data.timeCost = '';
        }

        data.room_type = data.one_to_many_status ? '<div class="room_type">一对多</div>' : '';

        // 判断我的预约
        if('undefined' != typeof data['listType'] && data['listType'] == 'myticket') {
            data.videoPath = 'href="'+ (url + data['uid']) +'"';
            data.btnReserve = '<span class="btn btn-s btn-around btn-reserve">进入房间</span>';
            roomType = "";
        }
        var _number = (data["tid"] == 2 ? 1 : 0) + (data["enterRoomlimit"] == 1 ? 1 : 0) + (data.one_to_many_status ? 1 : 0) + (data["timecost_live_status"] == 1 ? 1 : 0);

        (data['new_user'] && data['new_user'] == 800000) ? data.isNewUser = '<div class="badge badge800000"></div>' : data.isNewUser = '';

        tmp += '<div class="l-list '+ roomType +'" data-duration="'+ duration +'" data-points="'+ data["points"]+'" data-starttime="'+ switchToZhTime(data['start_time']) +'" data-endtime="'+ switchToZhTime(data['end_time']) +'" data-roomid="'+ roomid +'" data-onetomany="'+ oneToManyId +'" data-usercount="'+ data["user_count"] +'">'+
            '<a '+ data.videoPath +' class="l-block" target="_blank">'+
                '<img src="'+ data['headimg']+ '" alt="' + data['username'] + '"/>'+
                '<div class="state icon-default">' +
                    '<div class="c-icon-bar ' + 'bar' + data.rid + '" style="width:' +  (29 * _number + (data.origin == 11 ? 0 : 25)) + 'px">' +
                    (data.origin == 11 ? '' : "<span class='mobile'></span>") +
                    data.isTimeCostIcon +
                    data.isLimit +
                    data.isLock +
                    '</div>' +
                '</div>' +
                (data['isTop'] == null ? '' : data['isTop']) +
                '<div class="play-ord">'+
                    '<span class="l-price">' + data.points + "钻 (" + Number(duration)/60 + '分钟)</span>'+
                    data.btnReserve +
                '</div>'+
                data.isNewUser +
                '<div class="play"></div>' +
                '<div class="content">'+
                    '<div class="c-username">' + '<div class="username">'+data['username']+'</div>' + '</div>'+
                    '<div class="thumb-bar"></div>' +
                    '<div class="state">' +
                    '<div class="c-icon-bar ' + 'bar' + data.rid + '" style="width:' +  (44 * _number + (data.origin == 11 ? 0 : 25)) + 'px">' +
                    data.room_type +
                    '</div>' +
                    '</div>' +
                '</div>'+
            '</a>'+
        '</div>';
    };

    return tmp;
}

/**
 * @description 预约房间接口(一对一预约)
 * @author Young
 * @param rid 房间id
 */
var reserveRoom = function(rid){
    $.ajax({
        url: "/member/doReservation",
        dataType: "json",
        type: "GET",
        data: { duroomid: rid, flag: false },
        success: function(res){
            //预约成功
            if (res.code == 1) {
                $.tips("预约成功");
            //预约不成功
            }else if(res.code == 407){
                $.dialog({
                    title: "预约房间",
                    content: "在同时间段您已经预约了其它房间，确定预约相同时间段的本房间吗？",
                    ok: function(){
                        //重发ajax
                        $.ajax({
                            url: "/member/doReservation",
                            dataType: "json",
                            type: "GET",
                            //确定预约，将flag设置为true
                            data: { duroomid: rid, flag: true },
                            success: function(res){
                                if (res.code == 1) {
                                    $.tips("预约成功");
                                }else{
                                    $.tips(res.msg);
                                };
                            },
                            error: function(res, text){
                                $.tips("server error!");
                            }
                        });
                    },
                    okValue: "确定",
                    cancel: function(){},
                    cancelValue: "取消"
                }).show();
            //余额不足
            }else if(res.code == 405){
                $.tips(res.msg, function(){
                    location.href = "/charge/order";
                });
                //没有登录
            }else {
                $.tips(res.msg);
            }
        },
        error: function(res, text){
            $.tips("server error!");
        }
    });
}

/**
 * @description option循环列表
 * @author Young
 * @param obj: startNum开始数字 endNum结束数字 interval间隔 isPlusZero个位数前面是否加零
 */
var loopOptions = function(obj){

    var OPTIONS = {
        startNum: 0,
        endNum: 60,
        interval: 1,
        isPlusZero: true
    }

    var option = $.extend(true, OPTIONS, obj);

    var selectOptions = "";

    for (var i = option.startNum; i <= option.endNum; i = i + option.interval) {
        if (i < 10 && option.isPlusZero) {
            selectOptions = selectOptions + "<option>" + ("0" + i) + "</option>";
        }else{
            selectOptions = selectOptions + "<option>" + i + "</option>";
        };
    };

    return selectOptions;
}

/* 用户登录初始化 */
var loginInfoInit = function(){

    //初始化user
    window.user = new User();

    //设置邀请key
    var uKey = getLocation("u");
    if (uKey) {
        var ref = document.referrer;
        $.cookie("invitation_uid", uKey, 1/24);
        $.cookie("invitation_refer", ref, 1/24);
    };

    //邀请人记录
    var uAgent = getLocation("agent");
    if(uAgent){
        $.cookie("agent", uAgent, {
            expires: 1/48,
            domain: document.domain.replace(/^www/, "")
        });
    }

    //登录按钮逻辑
    $(".J-login").on("click", function(e){
        User.showLoginDialog();
    });

    //注册按钮逻辑
    $(".J-reg").on("click", function(e){
        User.showRegDialog();
    });

    User.flashUserInfo();


}

//从page-live迁移过来的
function request(paras) { //获取url参数
  var url = window.location.href.replace(/[><'"]/g, "");
  var paraString = url.substring(url.indexOf("?") + 1, url.length).split("&");
  var paraObj = {};
  for (var i = 0; j = paraString[i]; i++) {
    paraObj[j.substring(0, j.indexOf("=")).toLowerCase()] = j.substring(j.indexOf("=") + 1, j.length);
  }
  var returnValue = paraObj[paras.toLowerCase()];
  if (typeof(returnValue) == "undefined") {
    return "";
  } else {
    return returnValue;
  }
}

//代理设置，如果url和cookie同时存在，优先取url
function setAgent() {
  var agent = request("agent");
  if (agent.length > 0) {
    $.cookie("agent", agent);
  }
}