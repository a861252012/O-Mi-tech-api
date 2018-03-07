/**
 * Created by young on 2017/5/17.
 */
/*! https://mths.be/base64 v0.1.0 by @mathias | MIT license */
;(function(root) {

    // Detect free variables `exports`.
    var freeExports = typeof exports == 'object' && exports;

    // Detect free variable `module`.
    var freeModule = typeof module == 'object' && module &&
        module.exports == freeExports && module;

    // Detect free variable `global`, from Node.js or Browserified code, and use
    // it as `root`.
    var freeGlobal = typeof global == 'object' && global;
    if (freeGlobal.global === freeGlobal || freeGlobal.window === freeGlobal) {
        root = freeGlobal;
    }

    /*--------------------------------------------------------------------------*/

    var InvalidCharacterError = function(message) {
        this.message = message;
    };
    InvalidCharacterError.prototype = new Error;
    InvalidCharacterError.prototype.name = 'InvalidCharacterError';

    var error = function(message) {
        // Note: the error messages used throughout this file match those used by
        // the native `atob`/`btoa` implementation in Chromium.
        throw new InvalidCharacterError(message);
    };

    var TABLE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
    // http://whatwg.org/html/common-microsyntaxes.html#space-character
    var REGEX_SPACE_CHARACTERS = /[\t\n\f\r ]/g;

    // `decode` is designed to be fully compatible with `atob` as described in the
    // HTML Standard. http://whatwg.org/html/webappapis.html#dom-windowbase64-atob
    // The optimized base64-decoding algorithm used is based on @atk’s excellent
    // implementation. https://gist.github.com/atk/1020396
    var decode = function(input) {
        input = String(input)
            .replace(REGEX_SPACE_CHARACTERS, '');
        var length = input.length;
        if (length % 4 == 0) {
            input = input.replace(/==?$/, '');
            length = input.length;
        }
        if (
            length % 4 == 1 ||
                // http://whatwg.org/C#alphanumeric-ascii-characters
            /[^+a-zA-Z0-9/]/.test(input)
        ) {
            error(
                'Invalid character: the string to be decoded is not correctly encoded.'
            );
        }
        var bitCounter = 0;
        var bitStorage;
        var buffer;
        var output = '';
        var position = -1;
        while (++position < length) {
            buffer = TABLE.indexOf(input.charAt(position));
            bitStorage = bitCounter % 4 ? bitStorage * 64 + buffer : buffer;
            // Unless this is the first of a group of 4 characters…
            if (bitCounter++ % 4) {
                // …convert the first 8 bits to a single ASCII character.
                output += String.fromCharCode(
                    0xFF & bitStorage >> (-2 * bitCounter & 6)
                );
            }
        }
        return output;
    };

    // `encode` is designed to be fully compatible with `btoa` as described in the
    // HTML Standard: http://whatwg.org/html/webappapis.html#dom-windowbase64-btoa
    var encode = function(input) {
        input = String(input);
        if (/[^\0-\xFF]/.test(input)) {
            // Note: no need to special-case astral symbols here, as surrogates are
            // matched, and the input is supposed to only contain ASCII anyway.
            error(
                'The string to be encoded contains characters outside of the ' +
                'Latin1 range.'
            );
        }
        var padding = input.length % 3;
        var output = '';
        var position = -1;
        var a;
        var b;
        var c;
        var d;
        var buffer;
        // Make sure any padding is handled outside of the loop.
        var length = input.length - padding;

        while (++position < length) {
            // Read three bytes, i.e. 24 bits.
            a = input.charCodeAt(position) << 16;
            b = input.charCodeAt(++position) << 8;
            c = input.charCodeAt(++position);
            buffer = a + b + c;
            // Turn the 24 bits into four chunks of 6 bits each, and append the
            // matching character for each of them to the output.
            output += (
                TABLE.charAt(buffer >> 18 & 0x3F) +
                TABLE.charAt(buffer >> 12 & 0x3F) +
                TABLE.charAt(buffer >> 6 & 0x3F) +
                TABLE.charAt(buffer & 0x3F)
            );
        }

        if (padding == 2) {
            a = input.charCodeAt(position) << 8;
            b = input.charCodeAt(++position);
            buffer = a + b;
            output += (
                TABLE.charAt(buffer >> 10) +
                TABLE.charAt((buffer >> 4) & 0x3F) +
                TABLE.charAt((buffer << 2) & 0x3F) +
                '='
            );
        } else if (padding == 1) {
            buffer = input.charCodeAt(position);
            output += (
                TABLE.charAt(buffer >> 2) +
                TABLE.charAt((buffer << 4) & 0x3F) +
                '=='
            );
        }

        return output;
    };

    var base64 = {
        'encode': encode,
        'decode': decode,
        'version': '0.1.0'
    };

    // Some AMD build optimizers, like r.js, check for specific condition patterns
    // like the following:
    if (
        typeof define == 'function' &&
        typeof define.amd == 'object' &&
        define.amd
    ) {
        define(function() {
            return base64;
        });
    }	else if (freeExports && !freeExports.nodeType) {
        if (freeModule) { // in Node.js or RingoJS v0.8.0+
            freeModule.exports = base64;
        } else { // in Narwhal or RingoJS v0.7.0-
            for (var key in base64) {
                base64.hasOwnProperty(key) && (freeExports[key] = base64[key]);
            }
        }
    } else { // in Rhino or a web browser
        root.base64 = base64;
    }

}(this));
var rankPanelTmp = ['<div class="personContent-top clearfix">',
        '<img class="personImg" src="#{headimg}" alt="" />',
        '<div class="per-content">',
            '<div class="per-content-title" clearfix">',
                '<span class="per-name">#{nickname}</span>',
            '</div>',
            '<div class="per-hostid">#{hostId}</div>',
            '<div class="per-icon">',
                '#{badge}',
                '#{lvRich}',
                '#{richMark}',
            '</div>',
            '<div class="per-des">#{description}</div>',
        '</div>',
    '</div>',
    '<div class="personContent-middle clearfix">',
        '<span class="per-info">#{sex} | #{age} | #{starname} | #{procity}</span>',
        '#{isLive}',
        '<a href="#{space_url}" target="_blank" class="personLink">TA的空间</a>',
    '</div>',
    '<div class="personContent-bottom clearfix">',
        '<div class="per-handle">',
            //'<i class="per-fav"></i><a href="javascript:void(0)" class="per-fav-btn" data-fav="" title="点击关注"><span class="per-fav-btn-title">关注</span>（<i class="per-fav-btn-num">#{attens}</i>）</a>',
            '#{attention}',
        '</div>',
        // '<div class="per-handle">',
        //     '<i class="per-msg"></i><a href="javascript:void(0)" class="displayWinBtn per-msg-btn">发私信</a>',
        // '</div>',
        '<a href="#{room_url}/#{rid}/h5" target="_blank" class="btn btn-red per-video-btn">进入房间</a>',
    '</div>'].join("");

// var showMsgDialog = function(){

//     var msgDialog = $.dialog({
//         title: "发私信给",
//         content: ['<div class="msg-reply">',
//                     '<textarea class="textarea" name="" id="txtContent" rows="10"></textarea>',
//                     '<div class="tool clearfix">',
//                         '<span class="tool-tips">',
//                             '还能输入',
//                             '<span class="tool-num">200</span>',
//                             '字',
//                         '</span>',
//                         '<button class="btn">发送</button>',
//                     '</div>',
//                 '</div>'].join(""),

//         onshow: function(){

//             var that = this;

//             if (!User.isLogin()) {
//                 alert("请登录后再发送私信");
//                 that.remove();
//                 return;
//             };

//             var name = that.buttonTarget.closest(".personDiv").find(".per-name").text(),
//                 rel = that.buttonTarget.closest(".personDiv").data("rel"),
//                 $replyDialog = $(".msg-reply"),
//                 $replyTextarea = $("#txtContent");

//             $replyTextarea.val("");

//             that.setTitle('发私信给' + name);

//             wordsCount($replyTextarea, $replyDialog.find(".tool-num"), 200);

//             var focusXHR;

//             $replyDialog.off('click', ".btn");
//             $replyDialog.on('click', ".btn", function(){

//                 if ($.trim($replyTextarea.val()).length == 0) {
//                     $.tips("发送内容不能为空。");
//                     that.remove();
//                     return;
//                 };

//                 if (focusXHR && focusXHR.readyState != 4) {
//                     focusXHR.abort();
//                 };

//                 focusXHR = $.ajax({
//                     url:'/member/domsg',
//                     data:{ content: $replyTextarea.val(), tid: rel, fid: User.UID },
//                     dataType: "json",
//                     type: "POST",
//                     success: function(data){
//                         if( data.ret ){
//                             that.remove();
//                             $.tips("私信发送成功");
//                         }else{
//                             alert(data.info);
//                         }
//                     }
//                 });

//             });
//         }
//     });

//     $(document).on("click", ".per-msg-btn", function(){
//         msgDialog.setBtnTarget($(this));
//         msgDialog.show();
//     });
// }

// data: 传入所有信息
var favoriteHandle = function(data){
    var $btn = data.target.find(".per-fav-btn"),
        $btnText = $btn.find("span");

    if (parseInt(data.checkatten, 10)) {
        //如果已经关注
        $btnText.text("已关注");
        $btn.data("fav", "1");

    }else{
        //如果未关注
        $btnText.text("关注");
        $btn.data("fav", "0");
    };

    favoriteBtnSub(data);
}

//添加关注
// 参数
// pid 是被关注者的uid
// ret=1是添加关注
// ret=2是取消关注
var favoriteBtnSub = function(data){
    var $favBtn = data.target.find(".per-fav-btn"),
        $favBtnText = $favBtn.find("span");
        //$favBtnNum = $favBtn.find(".per-fav-btn-num");

    var xhr;

    $favBtn.on("click", function(){

        var state = parseInt($favBtn.data("fav"), 10);
        var ajaxRet = 1;
        //var num = parseInt($favBtnNum.text(), 10);

        //1: 关注 ， 2: 取消关注
        state == 0 ? ajaxRet = 1 : ajaxRet = 2;

        if (!User.isLogin()) {
            alert("请登录后再关注");
            return;
        }

        if (xhr && xhr.readyState != 4) {
            xhr.abort();
        };

        xhr = $.ajax({
            url: "/focus",
            type: "GET",
            dataType: "json",
            data: {
                pid: data.uid,
                ret: ajaxRet
            },
            success: function(json){
                if (json.status == 1) {

                    if (state) {
                        $favBtnText.text("关注");
                        $favBtn.data("fav", "0");
                        //$favBtnNum.text(num - 1);
                    }else{
                        $favBtnText.text("已关注");
                        $favBtn.data("fav", "1");

                        //刷新用户列表
                        User.flashUserInfo();
                        //$favBtnNum.text(num + 1);
                    };

                }else{
                    $.tips(json.msg);
                };

            },
            error: function(json){

            }
        });
    });
}

var getPanelData = function($view, callback){
    $view.find(".panel-hover").off("click");

    $view.on("mouseenter", ".panel-hover", function(){

        var that = this;
        //$(that).after(view.rankPanel);
        if ($(that).find(".personContent-top").length) { return; };
        $.ajax({
            type: 'GET',
            url: '/majax/getfidinfo',
            data: {uid: $(that).attr('rel'), atten:true},
            dataType:'json',
            success: function(data){
                if (data.ret) {
                    //解析男女
                    if(data.info.sex == 0){
                        data.info.sex = "女";
                    }else{
                        data.info.sex = "男";
                    }

                    //判断图标
                    //如果是主播，不显示财富等级
                    if (data.info.roled == 3) {
                        data.info.hostId = '(主播ID：' + data.info.uid + ')';
                        data.info.richMark = '<span class="hotListImg AnchorLevel'+ data.info.lv_exp +'"></span>';
                        data.info.lvRich = '';
                    }else{
                        data.info.hostId = '';
                        data.info.richMark = '';
                        data.info.lvRich = '';
                        //data.info.lvRich = '<span class="hotListImg basicLevel'+data.info.lv_rich+'"></span>';
                    };

                    //显示徽章
                    data.info.badge = (data.info["vip"] == 0) ? (data.info["icon_id"] == 0) ? "" : '<span class="per-badge badge badge'+ data.info["icon_id"] +'"></span>' : '<span class="hotListImg basicLevel'+data.info["vip"]+'"></span>';

                    //是否在线
                    if (data.info.roled == 3) {
                        switch(Number(data.info.live_status)){
                            case 0:
                                data.info.isLive = "<span class='per-live per-live-nl'>休息</span>";
                                break;
                            case 1:
                                data.info.isLive = "<span class='per-live per-live-ol'>直播</span>";
                                break;
                            default:
                                data.info.isLive = "";
                        }
                    };
                    
                    //是否显示关注按钮
                    data.info.roled == 3 ? data.info.attention = '<i class="per-fav"></i><a href="javascript:void(0)" class="per-fav-btn" data-fav="" title="点击关注"><span class="per-fav-btn-title">关注</span></a>' : data.info.attention = '';
                    data.info.uid == User.UID ? data.info.attention = '': data.info.attention;

                    // 设置房间统一域名
                    data.info.room_url = "";

                    //生成模板
                    var tmp = Utility.template(rankPanelTmp, data.info);
                    $(that).find(".personContent").html(tmp);

                    if (data.info.roled != 3) {
                        $(that).find(".per-video-btn").remove();
                        $(that).find(".personLink").remove();
                    };

                    $(that).find(".personLoading").remove();

                    //callback
                    data.info.target = $(that);

                    //bind favorite event
                    favoriteHandle(data.info);

                    if (callback) { callback(data) };
                };
            },
            error: function(){
                if (window.console) {console.log("ajax request error")};
            }
        });
    });

    //bind msg event
    //showMsgDialog();
}
/**
 * each是一个集合迭代函数，它接受一个函数作为参数和一组可选的参数<br/>
 * 这个迭代函数依次将集合的每一个元素和可选参数用函数进行计算，并将计算得的结果集返回
 {%example
 <script>
      var a = [1,2,3,4].each(function(x){return x > 2 ? x : null});
      var b = [1,2,3,4].each(function(x){return x < 0 ? x : null});
      alert(a);
      alert(b);
 </script>
 %}
 * @param {Function} fn 进行迭代判定的函数
 * @param more ... 零个或多个可选的用户自定义参数
 * @returns {Array} 结果集，如果没有结果，返回空集
 */
Array.prototype.each = function(fn){
    fn = fn || Function.K;
    var a = [];
    var args = Array.prototype.slice.call(arguments, 1);
    for(var i = 0; i < this.length; i++){
        var res = fn.apply(this,[this[i],i].concat(args));
        if(res != null) a.push(res);
    }
    return a;
};

/**
 * 得到一个数组不重复的元素集合<br/>
 * 唯一化一个数组
 * @returns {Array} 由不重复元素构成的数组
 */
Array.prototype.uniquelize = function(){
    var ra = new Array();
    for(var i = 0; i < this.length; i ++){
        if(!ra.contains(this[i])){
            ra.push(this[i]);
        }
    }
    return ra;
};

/**
 * 数组中是否包含指定的元素
 * @param obj 指定元素
 * @returns Bool
 */
Array.prototype.contains = function(obj) {
    for (var i = 0; i < this.length; i++) {
        if (this[i] === obj) {
            return true;
        }
    }
    return false;
};

/**
 * 求两个集合的不重复项
 {%example
 <script>
      var a = [1,2,3,4];
      var b = [3,4,5,6];
      alert(Array.complement(a,b));
      //[1,2,5,6]
 </script>
 %}
 * @param {Array} a 集合A
 * @param {Array} b 集合B
 * @returns {Array} 两个集合的去重
 */
Array.complement = function(a, b){
    return Array.minus(Array.union(a, b), Array.intersect(a, b));
};

/**
 * 求两个集合的交集
 {%example
 <script>
      var a = [1,2,3,4];
      var b = [3,4,5,6];
      alert(Array.intersect(a,b));
      //[3, 4]
 </script>
 %}
 * @param {Array} a 集合A
 * @param {Array} b 集合B
 * @returns {Array} 两个集合的交集
 */
Array.intersect = function(a, b){
    return a.uniquelize().each(function(o){return b.contains(o) ? o : null});
};

/**
 * 求两个集合的差集
 {%example
 <script>
      var a = [1,2,3,4];
      var b = [3,4,5,6];
      alert(Array.minus(a,b));
      //[1,2]
 </script>
 %}
 * @param {Array} a 集合A
 * @param {Array} b 集合B
 * @returns {Array} 两个集合的差集
 */
Array.minus = function(a, b){
    return a.uniquelize().each(function(o){return b.contains(o) ? null : o});
};

/**
 * 求两个集合的并集
 {%example
 <script>
      var a = [1,2,3,4];
      var b = [3,4,5,6];
      alert(Array.union(a,b));
      //[1, 2, 3, 4, 5, 6]
 </script>
 %}
 * @param {Array} a 集合A
 * @param {Array} b 集合B
 * @returns {Array} 两个集合的并集
 */
Array.union = function(a, b){
    return a.concat(b).uniquelize();
};

/**
 * Created by young on 2017/5/17.
 */
/**
 * @description 一对多房间交互
 * @author Young
 * @param [{string}] selector ：被委托监听的(外层)选择器
 */
var RoomTicket = function () {

  //初始化
  this.init = function () {
    this.bindTicketRoomEvent();
  }

  //绑定弹窗事件
  this.bindTicketRoomEvent = function () {

    var that = this;

    $(document).on('click', ".ticketRoom", function (e) {
      //阻止默认事件
      e.preventDefault();

      var $that = $(this);

      var data = {
        ordTitle: $that.find('.username').text(),
        ordDuration: $that.data("duration"),
        ordPoints: $that.data("points"),
        ordStartTime: $that.data("starttime"),
        ordEndTime: $that.data("endtime"),
        ordRoomId: $that.data("roomid"),
        ordOneToManyId: $that.data("onetomany")
      }

      that.showBuyTicketDialog(data);
    })
  }

  //显示购买窗口
  this.showBuyTicketDialog = function (data) {

    var ordTitle = data.ordTitle,
        ordDuration = Number(data.ordDuration) / 60,
        ordPoints = data.ordPoints,
        ordStartTime = data.ordStartTime,
        ordEndTime = data.ordEndTime,
        ordRoomId = data.ordRoomId,
        ordOneToManyId = data.ordOneToManyId;

    //是否已经购票标记
    var isPurchase = false;
    //判断是否已经预约
    for (var i = 0; i < User.MY_TICKET.length; i++) {
      if (User.MY_TICKET[i].id == ordOneToManyId) {
        isPurchase = true;
        break;
      }
    }

    //是否已经购票弹窗
    if (isPurchase) {
      $.dialog({
        title: "温馨提示",
        content: "您已经有该房间门票",
        ok: function () {
          location.href = "/" + ordRoomId + "/h5";
        },
        okValue: "立即进入",
        cancel: function () {

        },
        cancelValue: "稍后再去"

      }).show();

      return;
    }

    var tmp = "<div class='ordDialog'>" +
        "<div class='ordDialogContent'>" +
        "<h4>精彩表演，房间爆满</h4>" +
        "<h4>客官快快买票进入吧！</h4>" +
        "<p>主播昵称：" + ordTitle + "<br/>直播时长：" + ordDuration + "分钟</br>入场费用：" + ordPoints + "钻</br>开播时间：" + ordStartTime + "<br/>结束时间：" + ordEndTime + "</p>" +
        "</div>" +
        "<div class='ordDialogBottom'>"+
          '<button type="button" class="btn btnTicketEnter" autofocus="" >立即进入</button>' +
          '<button type="button" class="btn-leave btnTicketLeave" >狠心离开</button>'+
        "</div>"+
        "</div>";

    //去付款
    var gotoPay = function () {
      if(window.OpenAPI){
        window.open(window.OpenAPI.link.pay);
      }else{
        location.href = "/charge/order";
      }
    }

    //狠心离开
    var goOut = function(){
      if(window.OpenAPI){
        window.parent.close();
      }else{
        ticketDialog.remove();
      }
    }

    //是否出现弹窗关闭按钮
    var closeButtonDisplay = window.OpenAPI ? false : true;

    //门票房间确认框
    var ticketDialog = $.dialog({
      title: "精彩一对多",
      content: tmp,
      closeButtonDisplay: closeButtonDisplay,
      cancelTextBtn: true
    });

    //购买确认框
    var makeSureDialog = $.dialog({
      title: '一对多房间购票',
      content: '正在通讯，请稍等片刻...',
      cancel: function () {
      },
      cancelValue: '关闭',
      cancelDisplay: false
    })

    //门票房间弹窗显示
    ticketDialog.show();

    //进入
    $(document).off("click", ".btnTicketEnter");
    $(document).on("click", ".btnTicketEnter", function(){

      var enterBtn = $(this);
      makeSureDialog.show();

      if(!window.OpenAPI) {
        ticketDialog.remove();
      }

      enterBtn.prop('disabled', true);

      $.ajax({
        url: '/member/makeUpOneToMore',
        type: 'POST',
        dataType: 'json',
        data: {
          rid: ordRoomId,
          onetomore: ordOneToManyId
        },
        success: function (data) {
          //关闭原窗口
          makeSureDialog.remove();

          // 提示登录
          if (data.status == 1) {

            $.dialog({
              title: "通知",
              content: "购买成功，立即观看！",
              closeButtonDisplay: false,
              ok: function(){
                 location.href = "/" + ordRoomId + "/h5";
              },
              okValue: "立即观看"

            }).show();

          } else {

            if (data.cmd && data.cmd == "topupTip") {

              //合作平台
              $.dialog({
                title: "提示",
                content: data.msg,
                closeButtonDisplay: false,
                okValue: "立即充值",
                ok: function () {
                  gotoPay();
                },
                cancelValue: "狠心离开",
                cancel: function () {}
              }).show();

            } else {
              enterBtn.prop('disabled', false);
              $.tips(data.msg);
            }
          }
        },

        error: function () {
          enterBtn.prop('disabled', false);
          makeSureDialog.remove();
          $.tips('购买失败，请联系客服。');
        }
      });
    });

    //离开
    $(document).off("click", ".btnTicketLeave");
    $(document).on("click", ".btnTicketLeave", function(){
      goOut();
    });
  }

  this.init();
}
/**
 * @description 密码房间模块
 * @author Young
 */

var RoomPwd = function(){
    //this cache
    var that = this;

    var roomPwdAjax;

    var roomid = 0;

    //构造
    this.init = function(){
        //绑定click事件到movie list列表上，动态绑定

        var rid = window.currentVideo.roomId;

        //如果是密码房间
        if (window.currentVideo.isPassword) {

            //如果是本人，不弹窗，并直接进入页面
            if (rid == User.UID) {
                location.href = "/" + User.UID + Config.liveMode;
                //location.href = "/" + User.UID;
                //如果是本人，则跳出并停止ajax请求
                return;
            };

            //弹出窗设置
            that.showPwdDialog(rid);

        };

    }

    this.setRoomId = function(rid){
        roomid = rid;
    }

    this.getRoomId = function(){
        return roomid;
    }

    //显示dialog
    this.showPwdDialog = function(roomid){
        //输错5次密码后出现验证码

        var tmp = ['<div class="m-form">',
            '<div class="m-form-item">',
                '<input type="text" style="display:none;">',
                '<input type="password" style="display:none;">',
                '<label for="pwdRoom">房间密码：</label>',
                '<input type="password" class="txt" id="pwdRoom" />',
            '</div>',
            '<div class="m-form-item">',
                '<label for="pwdRoomCode">验证码：</label>',
                '<input type="text" class="txt txt-short" id="pwdRoomCode" />',
                '<img src="" alt="验证码" id="pwdRoomCodeImg" class="s-code-img" />',
                '<a href="javascript:void(0);" class="change-roomcode m-form-tip J-change-scode">换一换</a>',
            '</div>',
            '<div class="m-form-item">',
                '<span id="pwdRoomTips"></span>',
            '</div>',
        '</div>'].join("");

        var pwdDialog = $.dialog({
            title: "密码房间",
            content: tmp,
            width: 400,
            onshow: function(){

                var that = this;

                //验证码
                var cap = new Captcha();

                //输入密码错误5次以上刷新验证码
                cap.flashCaptcha($("#pwdRoomCodeImg"));
                $(".change-roomcode").on("click", function(){
                    cap.flashCaptcha($("#pwdRoomCodeImg"));
                });

                //密码验证
                $('#pwdRoom').passwordInput('#pwdRoomTips');

                //回车键触发密码验证
                $('#pwdRoom, #pwdRoomCode').on("keyup", function(e){
                    if (e.keyCode == 13) {
                        if ($("#pwdRoomCode").length == 1 && $("#pwdRoomCode").val().length <= 0) { 
                            $("#pwdRoomTips").text("请输入验证码。");
                            return;
                        };
                        //触发ok按键
                        that.ok();
                        that.remove();
                    };
                });

            },

            ok: function(){

                //触发密码验证
                $("#pwdRoom").trigger("keyup");

                //检查密码验证错误
                if ($.trim($("#pwdRoomTips").text()).length != 0) {
                    //跳出ok方法，但是点ok不关闭弹窗
                    return false;
                };

                //验证密码
                that.roomPwdAjaxSet({
                    data: { 
                        roomid: roomid, 
                        password: $("#pwdRoom").val(), 
                        captcha: $("#pwdRoomCode").val()
                    },
                    successCallback: function(res){
                        if(res.code == 1){
                            //判断是否是时长房间，如果是，则进入时长房间流程判断
                            /**
                             * 现已经改版，时长房间和一对一房间不能同时存在
                             * 2017.5.18
                             */
                            //if(window.currentVideo.isTimeCost){
                                //that.afterPwdSuccess();
                            //}else{
                                //密码验证成功，跳转
                                location.href = "/" + roomid + Config.liveMode;
                                //location.href = "/" + roomid;
                            //}
                        }else{
                            //密码验证失败
                            $.tips(res.msg);
                        }
                    }
                });

            },
            okValue: "确定"
        });

        pwdDialog.show();
    }

    /**
     * @description 密码房间ajax请求
     * @author Young
     * @param obj (查阅OPTIONS)
     */
    this.roomPwdAjaxSet = function(obj){

        var OPTIONS = {
            data: {},
            successCallback: function(){}
        }

        var option = $.extend(true, OPTIONS, obj);

        if (roomPwdAjax && roomPwdAjax.readyState != 4) {
            roomPwdAjax.abort();
        };

        $.ajax({
            url: "/checkroompwd",
            data: option.data,
            dataType: "json",
            type: "POST",
            success: function(res){
                if (option.successCallback) { option.successCallback(res); };
            },
            error: function(){
                $.tips("请求超时。");
            }
        });
    }

    //密码设置成功后的操作
    this.afterPwdSuccess = function(){

    }

    //构造函数
    this.init();

}
/**
 * Created by young on 2017/5/18.
 */
/**
 * @description 一对一房间首页时长房间交互
 * @author Young
 */
var RoomTimeCount = function(){
    //$(document).on('click', '.l-list[timecost]', function(){
    //时长房间每分钟单价
    this.timeCost = parseInt(window.currentVideo.timeCost, 10);
    this.roomId = window.currentVideo.roomId;

    //钻石数不足弹窗
    var pointsDialog = function(option){

        return $.dialog({
            title: '温馨提示',
            content: '您的余额不足'+ option.timeCostLimited +'钻石，需要充值才可以进入喔。',
            ok: function(){
                location.href = '/charge/order';
            },
            cancel: function(){},
            okValue: '充值',
            cancelValue: '狠心离开'
        });

    }

    //确认进入弹窗
    var makeSureDialog = function(option){

        /**
         * @ description 时长房间增加一个 vip/非vip 提示不同信息的判断
         * @ author Seed
         * @ date 2016-11-11
         */
        //单价
        var timeCostPrice = option.timeCost;
        //显示内容
        var showContent = '<p>您即将进入的是时长房间！</p><p>收费标准：'+ timeCostPrice +'钻石/分钟</p>';
        //贵族显示内容
        if(option.vip > 0 && option.discount < 10){
            timeCostPrice = Math.ceil(option.timeCost * option.discount / 10);
            showContent = showContent + '<p style="color:red">您是'+ option.vipName + ' , 享受折扣：'+option.discount+'折， '+ timeCostPrice +'钻石/分钟。贵族专享更高折扣哦。</p>';
        }
        /***************************************************************/

        //最低进入房间价格数
        var timeCostLimited = timeCostPrice * 3;

        return $.dialog({
            title: '温馨提示',
            content: showContent,
            ok: function(){

                var that = this;
                if(User.POINTS < timeCostLimited){
                    pointsDialog({ timeCostLimited: timeCostLimited }).show();
                }else{
                    $.ajax({
                        url: '/setinroomstat',
                        type: 'get',
                        dataType: 'json',
                        data: {
                            roomid: option.roomId
                        },
                        success: function(ret){
                            console.log(ret);
                            if(ret.code == 1){
                                location.href = "/" + option.roomId + Config.liveMode;
                            }else{
                                that.close();
                                $.tips(ret.msg + '<p>进入时长房间失败，请联系客服。</p>');
                            }
                        },
                        error: function(ret){
                            console.log(ret.responseText);
                        }
                    });

                }
            },
            cancel: function(){},
            okValue: '确定进入',
            cancelValue: '狠心离开'
        });
    }

    //显示确认框弹窗
    this.showComfirm = function(){
        //显示提示弹窗
        this.timeCost = parseInt(window.currentVideo.timeCost, 10);
        this.roomId = window.currentVideo.roomId;

        //判断是否是本人，如果是本人直接进入
        if(this.roomId == User.UID){
            location.href = "/" + this.roomId + Config.liveMode;
            return;
        }

        var timeCost = this.timeCost;
        var roomId = this.roomId;

        $.ajax({
            url:'/getTimeCountRoomDiscountInfo',
            type:'get',
            dataType:'json',
            success:function(data){
                //显示dialog
                if(data.code == 1){
                    makeSureDialog({
                        timeCost: timeCost,
                        roomId: roomId,
                        vip:data.info.vip,
                        vipName:data.info.vipName,
                        discount:data.info.discount
                    }).show();
                }else{
                    $.tips(data.msg);
                }
            },
            error: function(ret){
                console.log(ret.responseText);
            }
        })
    }

}

/*
 * jQuery FlexSlider v2.6.2
 * Copyright 2012 WooThemes
 * Contributing Author: Tyler Smith
 */
;(function($) {

        var focused = true;

        //FlexSlider: Object Instance
        $.flexslider = function(el, options) {
            var slider = $(el);

            // making variables public
            slider.vars = $.extend({}, $.flexslider.defaults, options);

            var namespace = slider.vars.namespace, msGesture = window.navigator && window.navigator.msPointerEnabled && window.MSGesture, touch = (("ontouchstart"in window) || msGesture || window.DocumentTouch && document instanceof DocumentTouch) && slider.vars.touch, // depricating this idea, as devices are being released with both of these events
                eventType = "click touchend MSPointerUp keyup", watchedEvent = "", watchedEventClearTimer, vertical = slider.vars.direction === "vertical", reverse = slider.vars.reverse, carousel = (slider.vars.itemWidth > 0), fade = slider.vars.animation === "fade", asNav = slider.vars.asNavFor !== "", methods = {};

            // Store a reference to the slider object
            $.data(el, "flexslider", slider);

            // Private slider methods
            methods = {
                init: function() {
                    slider.animating = false;
                    // Get current slide and make sure it is a number
                    slider.currentSlide = parseInt((slider.vars.startAt ? slider.vars.startAt : 0), 10);
                    if (isNaN(slider.currentSlide)) {
                        slider.currentSlide = 0;
                    }
                    slider.animatingTo = slider.currentSlide;
                    slider.atEnd = (slider.currentSlide === 0 || slider.currentSlide === slider.last);
                    slider.containerSelector = slider.vars.selector.substr(0, slider.vars.selector.search(' '));
                    slider.slides = $(slider.vars.selector, slider);
                    slider.container = $(slider.containerSelector, slider);
                    slider.count = slider.slides.length;
                    // SYNC:
                    slider.syncExists = $(slider.vars.sync).length > 0;
                    // SLIDE:
                    if (slider.vars.animation === "slide") {
                        slider.vars.animation = "swing";
                    }
                    slider.prop = (vertical) ? "top" : "marginLeft";
                    slider.args = {};
                    // SLIDESHOW:
                    slider.manualPause = false;
                    slider.stopped = false;
                    //PAUSE WHEN INVISIBLE
                    slider.started = false;
                    slider.startTimeout = null;
                    // TOUCH/USECSS:
                    slider.transitions = !slider.vars.video && !fade && slider.vars.useCSS && (function() {
                        var obj = document.createElement('div')
                            , props = ['perspectiveProperty', 'WebkitPerspective', 'MozPerspective', 'OPerspective', 'msPerspective'];
                        for (var i in props) {
                            if (obj.style[props[i]] !== undefined) {
                                slider.pfx = props[i].replace('Perspective', '').toLowerCase();
                                slider.prop = "-" + slider.pfx + "-transform";
                                return true;
                            }
                        }
                        return false;
                    }());
                    slider.ensureAnimationEnd = '';
                    // CONTROLSCONTAINER:
                    if (slider.vars.controlsContainer !== "")
                        slider.controlsContainer = $(slider.vars.controlsContainer).length > 0 && $(slider.vars.controlsContainer);
                    // MANUAL:
                    if (slider.vars.manualControls !== "")
                        slider.manualControls = $(slider.vars.manualControls).length > 0 && $(slider.vars.manualControls);

                    // CUSTOM DIRECTION NAV:
                    if (slider.vars.customDirectionNav !== "")
                        slider.customDirectionNav = $(slider.vars.customDirectionNav).length === 2 && $(slider.vars.customDirectionNav);

                    // RANDOMIZE:
                    if (slider.vars.randomize) {
                        slider.slides.sort(function() {
                            return (Math.round(Math.random()) - 0.5);
                        });
                        slider.container.empty().append(slider.slides);
                    }

                    slider.doMath();

                    // INIT
                    slider.setup("init");

                    // CONTROLNAV:
                    if (slider.vars.controlNav) {
                        methods.controlNav.setup();
                    }

                    // DIRECTIONNAV:
                    if (slider.vars.directionNav) {
                        methods.directionNav.setup();
                    }

                    // KEYBOARD:
                    if (slider.vars.keyboard && ($(slider.containerSelector).length === 1 || slider.vars.multipleKeyboard)) {
                        $(document).bind('keyup', function(event) {
                            var keycode = event.keyCode;
                            if (!slider.animating && (keycode === 39 || keycode === 37)) {
                                var target = (keycode === 39) ? slider.getTarget('next') : (keycode === 37) ? slider.getTarget('prev') : false;
                                slider.flexAnimate(target, slider.vars.pauseOnAction);
                            }
                        });
                    }
                    // MOUSEWHEEL:
                    if (slider.vars.mousewheel) {
                        slider.bind('mousewheel', function(event, delta, deltaX, deltaY) {
                            event.preventDefault();
                            var target = (delta < 0) ? slider.getTarget('next') : slider.getTarget('prev');
                            slider.flexAnimate(target, slider.vars.pauseOnAction);
                        });
                    }

                    // PAUSEPLAY
                    if (slider.vars.pausePlay) {
                        methods.pausePlay.setup();
                    }

                    //PAUSE WHEN INVISIBLE
                    if (slider.vars.slideshow && slider.vars.pauseInvisible) {
                        methods.pauseInvisible.init();
                    }

                    // SLIDSESHOW
                    if (slider.vars.slideshow) {
                        if (slider.vars.pauseOnHover) {
                            slider.hover(function() {
                                if (!slider.manualPlay && !slider.manualPause) {
                                    slider.pause();
                                }
                            }, function() {
                                if (!slider.manualPause && !slider.manualPlay && !slider.stopped) {
                                    slider.play();
                                }
                            });
                        }
                        // initialize animation
                        //If we're visible, or we don't use PageVisibility API
                        if (!slider.vars.pauseInvisible || !methods.pauseInvisible.isHidden()) {
                            (slider.vars.initDelay > 0) ? slider.startTimeout = setTimeout(slider.play, slider.vars.initDelay) : slider.play();
                        }
                    }

                    // ASNAV:
                    if (asNav) {
                        methods.asNav.setup();
                    }

                    // TOUCH
                    if (touch && slider.vars.touch) {
                        methods.touch();
                    }

                    // FADE&&SMOOTHHEIGHT || SLIDE:
                    if (!fade || (fade && slider.vars.smoothHeight)) {
                        $(window).bind("resize orientationchange focus", methods.resize);
                    }

                    slider.find("img").attr("draggable", "false");

                    // API: start() Callback
                    setTimeout(function() {
                        slider.vars.start(slider);
                    }, 200);
                },
                asNav: {
                    setup: function() {
                        slider.asNav = true;
                        slider.animatingTo = Math.floor(slider.currentSlide / slider.move);
                        slider.currentItem = slider.currentSlide;
                        slider.slides.removeClass(namespace + "active-slide").eq(slider.currentItem).addClass(namespace + "active-slide");
                        if (!msGesture) {
                            slider.slides.on(eventType, function(e) {
                                e.preventDefault();
                                var $slide = $(this)
                                    , target = $slide.index();
                                var posFromLeft = $slide.offset().left - $(slider).scrollLeft();
                                // Find position of slide relative to left of slider container
                                if (posFromLeft <= 0 && $slide.hasClass(namespace + 'active-slide')) {
                                    slider.flexAnimate(slider.getTarget("prev"), true);
                                } else if (!$(slider.vars.asNavFor).data('flexslider').animating && !$slide.hasClass(namespace + "active-slide")) {
                                    slider.direction = (slider.currentItem < target) ? "next" : "prev";
                                    slider.flexAnimate(target, slider.vars.pauseOnAction, false, true, true);
                                }
                            });
                        } else {
                            el._slider = slider;
                            slider.slides.each(function() {
                                var that = this;
                                that._gesture = new MSGesture();
                                that._gesture.target = that;
                                that.addEventListener("MSPointerDown", function(e) {
                                    e.preventDefault();
                                    if (e.currentTarget._gesture) {
                                        e.currentTarget._gesture.addPointer(e.pointerId);
                                    }
                                }, false);
                                that.addEventListener("MSGestureTap", function(e) {
                                    e.preventDefault();
                                    var $slide = $(this)
                                        , target = $slide.index();
                                    if (!$(slider.vars.asNavFor).data('flexslider').animating && !$slide.hasClass('active')) {
                                        slider.direction = (slider.currentItem < target) ? "next" : "prev";
                                        slider.flexAnimate(target, slider.vars.pauseOnAction, false, true, true);
                                    }
                                });
                            });
                        }
                    }
                },
                controlNav: {
                    setup: function() {
                        if (!slider.manualControls) {
                            methods.controlNav.setupPaging();
                        } else {
                            // MANUALCONTROLS:
                            methods.controlNav.setupManual();
                        }
                    },
                    setupPaging: function() {
                        var type = (slider.vars.controlNav === "thumbnails") ? 'control-thumbs' : 'control-paging', j = 1, item, slide;

                        slider.controlNavScaffold = $('<ol class="' + namespace + 'control-nav ' + namespace + type + '"></ol>');

                        if (slider.pagingCount > 1) {
                            for (var i = 0; i < slider.pagingCount; i++) {
                                slide = slider.slides.eq(i);
                                if (undefined === slide.attr('data-thumb-alt')) {
                                    slide.attr('data-thumb-alt', '');
                                }
                                var altText = ('' !== slide.attr('data-thumb-alt')) ? altText = ' alt="' + slide.attr('data-thumb-alt') + '"' : '';
                                item = (slider.vars.controlNav === "thumbnails") ? '<img src="' + slide.attr('data-thumb') + '"' + altText + '/>' : '<a href="#">' + j + '</a>';
                                if ('thumbnails' === slider.vars.controlNav && true === slider.vars.thumbCaptions) {
                                    var captn = slide.attr('data-thumbcaption');
                                    if ('' !== captn && undefined !== captn) {
                                        item += '<span class="' + namespace + 'caption">' + captn + '</span>';
                                    }
                                }
                                slider.controlNavScaffold.append('<li>' + item + '</li>');
                                j++;
                            }
                        }

                        // CONTROLSCONTAINER:
                        (slider.controlsContainer) ? $(slider.controlsContainer).append(slider.controlNavScaffold) : slider.append(slider.controlNavScaffold);
                        methods.controlNav.set();

                        methods.controlNav.active();

                        slider.controlNavScaffold.delegate('a, img', eventType, function(event) {
                            event.preventDefault();

                            if (watchedEvent === "" || watchedEvent === event.type) {
                                var $this = $(this)
                                    , target = slider.controlNav.index($this);

                                if (!$this.hasClass(namespace + 'active')) {
                                    slider.direction = (target > slider.currentSlide) ? "next" : "prev";
                                    slider.flexAnimate(target, slider.vars.pauseOnAction);
                                }
                            }

                            // setup flags to prevent event duplication
                            if (watchedEvent === "") {
                                watchedEvent = event.type;
                            }
                            methods.setToClearWatchedEvent();

                        });
                    },
                    setupManual: function() {
                        slider.controlNav = slider.manualControls;
                        methods.controlNav.active();

                        slider.controlNav.bind(eventType, function(event) {
                            event.preventDefault();

                            if (watchedEvent === "" || watchedEvent === event.type) {
                                var $this = $(this)
                                    , target = slider.controlNav.index($this);

                                if (!$this.hasClass(namespace + 'active')) {
                                    (target > slider.currentSlide) ? slider.direction = "next" : slider.direction = "prev";
                                    slider.flexAnimate(target, slider.vars.pauseOnAction);
                                }
                            }

                            // setup flags to prevent event duplication
                            if (watchedEvent === "") {
                                watchedEvent = event.type;
                            }
                            methods.setToClearWatchedEvent();
                        });
                    },
                    set: function() {
                        var selector = (slider.vars.controlNav === "thumbnails") ? 'img' : 'a';
                        slider.controlNav = $('.' + namespace + 'control-nav li ' + selector, (slider.controlsContainer) ? slider.controlsContainer : slider);
                    },
                    active: function() {
                        slider.controlNav.removeClass(namespace + "active").eq(slider.animatingTo).addClass(namespace + "active");
                    },
                    update: function(action, pos) {
                        if (slider.pagingCount > 1 && action === "add") {
                            slider.controlNavScaffold.append($('<li><a href="#">' + slider.count + '</a></li>'));
                        } else if (slider.pagingCount === 1) {
                            slider.controlNavScaffold.find('li').remove();
                        } else {
                            slider.controlNav.eq(pos).closest('li').remove();
                        }
                        methods.controlNav.set();
                        (slider.pagingCount > 1 && slider.pagingCount !== slider.controlNav.length) ? slider.update(pos, action) : methods.controlNav.active();
                    }
                },
                directionNav: {
                    setup: function() {
                        var directionNavScaffold = $('<ul class="' + namespace + 'direction-nav"><li class="' + namespace + 'nav-prev"><a class="' + namespace + 'prev" href="#">' + slider.vars.prevText + '</a></li><li class="' + namespace + 'nav-next"><a class="' + namespace + 'next" href="#">' + slider.vars.nextText + '</a></li></ul>');

                        // CUSTOM DIRECTION NAV:
                        if (slider.customDirectionNav) {
                            slider.directionNav = slider.customDirectionNav;
                            // CONTROLSCONTAINER:
                        } else if (slider.controlsContainer) {
                            $(slider.controlsContainer).append(directionNavScaffold);
                            slider.directionNav = $('.' + namespace + 'direction-nav li a', slider.controlsContainer);
                        } else {
                            slider.append(directionNavScaffold);
                            slider.directionNav = $('.' + namespace + 'direction-nav li a', slider);
                        }

                        methods.directionNav.update();

                        slider.directionNav.bind(eventType, function(event) {
                            event.preventDefault();
                            var target;

                            if (watchedEvent === "" || watchedEvent === event.type) {
                                target = ($(this).hasClass(namespace + 'next')) ? slider.getTarget('next') : slider.getTarget('prev');
                                slider.flexAnimate(target, slider.vars.pauseOnAction);
                            }

                            // setup flags to prevent event duplication
                            if (watchedEvent === "") {
                                watchedEvent = event.type;
                            }
                            methods.setToClearWatchedEvent();
                        });
                    },
                    update: function() {
                        var disabledClass = namespace + 'disabled';
                        if (slider.pagingCount === 1) {
                            slider.directionNav.addClass(disabledClass).attr('tabindex', '-1');
                        } else if (!slider.vars.animationLoop) {
                            if (slider.animatingTo === 0) {
                                slider.directionNav.removeClass(disabledClass).filter('.' + namespace + "prev").addClass(disabledClass).attr('tabindex', '-1');
                            } else if (slider.animatingTo === slider.last) {
                                slider.directionNav.removeClass(disabledClass).filter('.' + namespace + "next").addClass(disabledClass).attr('tabindex', '-1');
                            } else {
                                slider.directionNav.removeClass(disabledClass).removeAttr('tabindex');
                            }
                        } else {
                            slider.directionNav.removeClass(disabledClass).removeAttr('tabindex');
                        }
                    }
                },
                pausePlay: {
                    setup: function() {
                        var pausePlayScaffold = $('<div class="' + namespace + 'pauseplay"><a href="#"></a></div>');

                        // CONTROLSCONTAINER:
                        if (slider.controlsContainer) {
                            slider.controlsContainer.append(pausePlayScaffold);
                            slider.pausePlay = $('.' + namespace + 'pauseplay a', slider.controlsContainer);
                        } else {
                            slider.append(pausePlayScaffold);
                            slider.pausePlay = $('.' + namespace + 'pauseplay a', slider);
                        }

                        methods.pausePlay.update((slider.vars.slideshow) ? namespace + 'pause' : namespace + 'play');

                        slider.pausePlay.bind(eventType, function(event) {
                            event.preventDefault();

                            if (watchedEvent === "" || watchedEvent === event.type) {
                                if ($(this).hasClass(namespace + 'pause')) {
                                    slider.manualPause = true;
                                    slider.manualPlay = false;
                                    slider.pause();
                                } else {
                                    slider.manualPause = false;
                                    slider.manualPlay = true;
                                    slider.play();
                                }
                            }

                            // setup flags to prevent event duplication
                            if (watchedEvent === "") {
                                watchedEvent = event.type;
                            }
                            methods.setToClearWatchedEvent();
                        });
                    },
                    update: function(state) {
                        (state === "play") ? slider.pausePlay.removeClass(namespace + 'pause').addClass(namespace + 'play').html(slider.vars.playText) : slider.pausePlay.removeClass(namespace + 'play').addClass(namespace + 'pause').html(slider.vars.pauseText);
                    }
                },
                touch: function() {
                    var startX, startY, offset, cwidth, dx, startT, onTouchStart, onTouchMove, onTouchEnd, scrolling = false, localX = 0, localY = 0, accDx = 0;

                    if (!msGesture) {
                        onTouchStart = function(e) {
                            if (slider.animating) {
                                e.preventDefault();
                            } else if ((window.navigator.msPointerEnabled) || e.touches.length === 1) {
                                slider.pause();
                                // CAROUSEL:
                                cwidth = (vertical) ? slider.h : slider.w;
                                startT = Number(new Date());
                                // CAROUSEL:

                                // Local vars for X and Y points.
                                localX = e.touches[0].pageX;
                                localY = e.touches[0].pageY;

                                offset = (carousel && reverse && slider.animatingTo === slider.last) ? 0 : (carousel && reverse) ? slider.limit - (((slider.itemW + slider.vars.itemMargin) * slider.move) * slider.animatingTo) : (carousel && slider.currentSlide === slider.last) ? slider.limit : (carousel) ? ((slider.itemW + slider.vars.itemMargin) * slider.move) * slider.currentSlide : (reverse) ? (slider.last - slider.currentSlide + slider.cloneOffset) * cwidth : (slider.currentSlide + slider.cloneOffset) * cwidth;
                                startX = (vertical) ? localY : localX;
                                startY = (vertical) ? localX : localY;

                                el.addEventListener('touchmove', onTouchMove, false);
                                el.addEventListener('touchend', onTouchEnd, false);
                            }
                        }
                        ;

                        onTouchMove = function(e) {
                            // Local vars for X and Y points.

                            localX = e.touches[0].pageX;
                            localY = e.touches[0].pageY;

                            dx = (vertical) ? startX - localY : startX - localX;
                            scrolling = (vertical) ? (Math.abs(dx) < Math.abs(localX - startY)) : (Math.abs(dx) < Math.abs(localY - startY));

                            var fxms = 500;

                            if (!scrolling || Number(new Date()) - startT > fxms) {
                                e.preventDefault();
                                if (!fade && slider.transitions) {
                                    if (!slider.vars.animationLoop) {
                                        dx = dx / ((slider.currentSlide === 0 && dx < 0 || slider.currentSlide === slider.last && dx > 0) ? (Math.abs(dx) / cwidth + 2) : 1);
                                    }
                                    slider.setProps(offset + dx, "setTouch");
                                }
                            }
                        }
                        ;

                        onTouchEnd = function(e) {
                            // finish the touch by undoing the touch session
                            el.removeEventListener('touchmove', onTouchMove, false);

                            if (slider.animatingTo === slider.currentSlide && !scrolling && !(dx === null)) {
                                var updateDx = (reverse) ? -dx : dx
                                    , target = (updateDx > 0) ? slider.getTarget('next') : slider.getTarget('prev');

                                if (slider.canAdvance(target) && (Number(new Date()) - startT < 550 && Math.abs(updateDx) > 50 || Math.abs(updateDx) > cwidth / 2)) {
                                    slider.flexAnimate(target, slider.vars.pauseOnAction);
                                } else {
                                    if (!fade) {
                                        slider.flexAnimate(slider.currentSlide, slider.vars.pauseOnAction, true);
                                    }
                                }
                            }
                            el.removeEventListener('touchend', onTouchEnd, false);

                            startX = null;
                            startY = null;
                            dx = null;
                            offset = null;
                        }
                        ;

                        el.addEventListener('touchstart', onTouchStart, false);
                    } else {
                        el.style.msTouchAction = "none";
                        el._gesture = new MSGesture();
                        el._gesture.target = el;
                        el.addEventListener("MSPointerDown", onMSPointerDown, false);
                        el._slider = slider;
                        el.addEventListener("MSGestureChange", onMSGestureChange, false);
                        el.addEventListener("MSGestureEnd", onMSGestureEnd, false);

                        function onMSPointerDown(e) {
                            e.stopPropagation();
                            if (slider.animating) {
                                e.preventDefault();
                            } else {
                                slider.pause();
                                el._gesture.addPointer(e.pointerId);
                                accDx = 0;
                                cwidth = (vertical) ? slider.h : slider.w;
                                startT = Number(new Date());
                                // CAROUSEL:

                                offset = (carousel && reverse && slider.animatingTo === slider.last) ? 0 : (carousel && reverse) ? slider.limit - (((slider.itemW + slider.vars.itemMargin) * slider.move) * slider.animatingTo) : (carousel && slider.currentSlide === slider.last) ? slider.limit : (carousel) ? ((slider.itemW + slider.vars.itemMargin) * slider.move) * slider.currentSlide : (reverse) ? (slider.last - slider.currentSlide + slider.cloneOffset) * cwidth : (slider.currentSlide + slider.cloneOffset) * cwidth;
                            }
                        }

                        function onMSGestureChange(e) {
                            e.stopPropagation();
                            var slider = e.target._slider;
                            if (!slider) {
                                return;
                            }
                            var transX = -e.translationX
                                , transY = -e.translationY;

                            //Accumulate translations.
                            accDx = accDx + ((vertical) ? transY : transX);
                            dx = accDx;
                            scrolling = (vertical) ? (Math.abs(accDx) < Math.abs(-transX)) : (Math.abs(accDx) < Math.abs(-transY));

                            if (e.detail === e.MSGESTURE_FLAG_INERTIA) {
                                setImmediate(function() {
                                    el._gesture.stop();
                                });

                                return;
                            }

                            if (!scrolling || Number(new Date()) - startT > 500) {
                                e.preventDefault();
                                if (!fade && slider.transitions) {
                                    if (!slider.vars.animationLoop) {
                                        dx = accDx / ((slider.currentSlide === 0 && accDx < 0 || slider.currentSlide === slider.last && accDx > 0) ? (Math.abs(accDx) / cwidth + 2) : 1);
                                    }
                                    slider.setProps(offset + dx, "setTouch");
                                }
                            }
                        }

                        function onMSGestureEnd(e) {
                            e.stopPropagation();
                            var slider = e.target._slider;
                            if (!slider) {
                                return;
                            }
                            if (slider.animatingTo === slider.currentSlide && !scrolling && !(dx === null)) {
                                var updateDx = (reverse) ? -dx : dx
                                    , target = (updateDx > 0) ? slider.getTarget('next') : slider.getTarget('prev');

                                if (slider.canAdvance(target) && (Number(new Date()) - startT < 550 && Math.abs(updateDx) > 50 || Math.abs(updateDx) > cwidth / 2)) {
                                    slider.flexAnimate(target, slider.vars.pauseOnAction);
                                } else {
                                    if (!fade) {
                                        slider.flexAnimate(slider.currentSlide, slider.vars.pauseOnAction, true);
                                    }
                                }
                            }

                            startX = null;
                            startY = null;
                            dx = null;
                            offset = null;
                            accDx = 0;
                        }
                    }
                },
                resize: function() {
                    if (!slider.animating && slider.is(':visible')) {
                        if (!carousel) {
                            slider.doMath();
                        }

                        if (fade) {
                            // SMOOTH HEIGHT:
                            methods.smoothHeight();
                        } else if (carousel) {
                            //CAROUSEL:
                            slider.slides.width(slider.computedW);
                            slider.update(slider.pagingCount);
                            slider.setProps();
                        } else if (vertical) {
                            //VERTICAL:
                            slider.viewport.height(slider.h);
                            slider.setProps(slider.h, "setTotal");
                        } else {
                            // SMOOTH HEIGHT:
                            if (slider.vars.smoothHeight) {
                                methods.smoothHeight();
                            }
                            slider.newSlides.width(slider.computedW);
                            slider.setProps(slider.computedW, "setTotal");
                        }
                    }
                },
                smoothHeight: function(dur) {
                    if (!vertical || fade) {
                        var $obj = (fade) ? slider : slider.viewport;
                        (dur) ? $obj.animate({
                            "height": slider.slides.eq(slider.animatingTo).innerHeight()
                        }, dur).css('overflow', 'visible') : $obj.innerHeight(slider.slides.eq(slider.animatingTo).innerHeight());
                    }
                },
                sync: function(action) {
                    var $obj = $(slider.vars.sync).data("flexslider")
                        , target = slider.animatingTo;

                    switch (action) {
                        case "animate":
                            $obj.flexAnimate(target, slider.vars.pauseOnAction, false, true);
                            break;
                        case "play":
                            if (!$obj.playing && !$obj.asNav) {
                                $obj.play();
                            }
                            break;
                        case "pause":
                            $obj.pause();
                            break;
                    }
                },
                uniqueID: function($clone) {
                    // Append _clone to current level and children elements with id attributes
                    $clone.filter('[id]').add($clone.find('[id]')).each(function() {
                        var $this = $(this);
                        $this.attr('id', $this.attr('id') + '_clone');
                    });
                    return $clone;
                },
                pauseInvisible: {
                    visProp: null,
                    init: function() {
                        var visProp = methods.pauseInvisible.getHiddenProp();
                        if (visProp) {
                            var evtname = visProp.replace(/[H|h]idden/, '') + 'visibilitychange';
                            document.addEventListener(evtname, function() {
                                if (methods.pauseInvisible.isHidden()) {
                                    if (slider.startTimeout) {
                                        clearTimeout(slider.startTimeout);
                                        //If clock is ticking, stop timer and prevent from starting while invisible
                                    } else {
                                        slider.pause();
                                        //Or just pause
                                    }
                                } else {
                                    if (slider.started) {
                                        slider.play();
                                        //Initiated before, just play
                                    } else {
                                        if (slider.vars.initDelay > 0) {
                                            setTimeout(slider.play, slider.vars.initDelay);
                                        } else {
                                            slider.play();
                                            //Didn't init before: simply init or wait for it
                                        }
                                    }
                                }
                            });
                        }
                    },
                    isHidden: function() {
                        var prop = methods.pauseInvisible.getHiddenProp();
                        if (!prop) {
                            return false;
                        }
                        return document[prop];
                    },
                    getHiddenProp: function() {
                        var prefixes = ['webkit', 'moz', 'ms', 'o'];
                        // if 'hidden' is natively supported just return it
                        if ('hidden'in document) {
                            return 'hidden';
                        }
                        // otherwise loop over all the known prefixes until we find one
                        for (var i = 0; i < prefixes.length; i++) {
                            if ((prefixes[i] + 'Hidden')in document) {
                                return prefixes[i] + 'Hidden';
                            }
                        }
                        // otherwise it's not supported
                        return null;
                    }
                },
                setToClearWatchedEvent: function() {
                    clearTimeout(watchedEventClearTimer);
                    watchedEventClearTimer = setTimeout(function() {
                        watchedEvent = "";
                    }, 3000);
                }
            };

            // public methods
            slider.flexAnimate = function(target, pause, override, withSync, fromNav) {
                if (!slider.vars.animationLoop && target !== slider.currentSlide) {
                    slider.direction = (target > slider.currentSlide) ? "next" : "prev";
                }

                if (asNav && slider.pagingCount === 1)
                    slider.direction = (slider.currentItem < target) ? "next" : "prev";

                if (!slider.animating && (slider.canAdvance(target, fromNav) || override) && slider.is(":visible")) {
                    if (asNav && withSync) {
                        var master = $(slider.vars.asNavFor).data('flexslider');
                        slider.atEnd = target === 0 || target === slider.count - 1;
                        master.flexAnimate(target, true, false, true, fromNav);
                        slider.direction = (slider.currentItem < target) ? "next" : "prev";
                        master.direction = slider.direction;

                        if (Math.ceil((target + 1) / slider.visible) - 1 !== slider.currentSlide && target !== 0) {
                            slider.currentItem = target;
                            slider.slides.removeClass(namespace + "active-slide").eq(target).addClass(namespace + "active-slide");
                            target = Math.floor(target / slider.visible);
                        } else {
                            slider.currentItem = target;
                            slider.slides.removeClass(namespace + "active-slide").eq(target).addClass(namespace + "active-slide");
                            return false;
                        }
                    }

                    slider.animating = true;
                    slider.animatingTo = target;

                    // SLIDESHOW:
                    if (pause) {
                        slider.pause();
                    }

                    // API: before() animation Callback
                    slider.vars.before(slider);

                    // SYNC:
                    if (slider.syncExists && !fromNav) {
                        methods.sync("animate");
                    }

                    // CONTROLNAV
                    if (slider.vars.controlNav) {
                        methods.controlNav.active();
                    }

                    // !CAROUSEL:
                    // CANDIDATE: slide active class (for add/remove slide)
                    if (!carousel) {
                        slider.slides.removeClass(namespace + 'active-slide').eq(target).addClass(namespace + 'active-slide');
                    }

                    // INFINITE LOOP:
                    // CANDIDATE: atEnd
                    slider.atEnd = target === 0 || target === slider.last;

                    // DIRECTIONNAV:
                    if (slider.vars.directionNav) {
                        methods.directionNav.update();
                    }

                    if (target === slider.last) {
                        // API: end() of cycle Callback
                        slider.vars.end(slider);
                        // SLIDESHOW && !INFINITE LOOP:
                        if (!slider.vars.animationLoop) {
                            slider.pause();
                        }
                    }

                    // SLIDE:
                    if (!fade) {
                        var dimension = (vertical) ? slider.slides.filter(':first').height() : slider.computedW, margin, slideString, calcNext;

                        // INFINITE LOOP / REVERSE:
                        if (carousel) {
                            margin = slider.vars.itemMargin;
                            calcNext = ((slider.itemW + margin) * slider.move) * slider.animatingTo;
                            slideString = (calcNext > slider.limit && slider.visible !== 1) ? slider.limit : calcNext;
                        } else if (slider.currentSlide === 0 && target === slider.count - 1 && slider.vars.animationLoop && slider.direction !== "next") {
                            slideString = (reverse) ? (slider.count + slider.cloneOffset) * dimension : 0;
                        } else if (slider.currentSlide === slider.last && target === 0 && slider.vars.animationLoop && slider.direction !== "prev") {
                            slideString = (reverse) ? 0 : (slider.count + 1) * dimension;
                        } else {
                            slideString = (reverse) ? ((slider.count - 1) - target + slider.cloneOffset) * dimension : (target + slider.cloneOffset) * dimension;
                        }
                        slider.setProps(slideString, "", slider.vars.animationSpeed);
                        if (slider.transitions) {
                            if (!slider.vars.animationLoop || !slider.atEnd) {
                                slider.animating = false;
                                slider.currentSlide = slider.animatingTo;
                            }

                            // Unbind previous transitionEnd events and re-bind new transitionEnd event
                            slider.container.unbind("webkitTransitionEnd transitionend");
                            slider.container.bind("webkitTransitionEnd transitionend", function() {
                                clearTimeout(slider.ensureAnimationEnd);
                                slider.wrapup(dimension);
                            });

                            // Insurance for the ever-so-fickle transitionEnd event
                            clearTimeout(slider.ensureAnimationEnd);
                            slider.ensureAnimationEnd = setTimeout(function() {
                                slider.wrapup(dimension);
                            }, slider.vars.animationSpeed + 100);

                        } else {
                            slider.container.animate(slider.args, slider.vars.animationSpeed, slider.vars.easing, function() {
                                slider.wrapup(dimension);
                            });
                        }
                    } else {
                        // FADE:
                        if (!touch) {
                            //slider.slides.eq(slider.currentSlide).fadeOut(slider.vars.animationSpeed, slider.vars.easing);
                            //slider.slides.eq(target).fadeIn(slider.vars.animationSpeed, slider.vars.easing, slider.wrapup);

                            slider.slides.eq(slider.currentSlide).css({
                                "zIndex": 1,
                                "display": "none"
                            }).animate({
                                "opacity": 0
                            }, slider.vars.animationSpeed, slider.vars.easing);
                            slider.slides.eq(target).css({
                                "zIndex": 2,
                                "display": "block"
                            }).animate({
                                "opacity": 1
                            }, slider.vars.animationSpeed, slider.vars.easing, slider.wrapup);

                        } else {
                            slider.slides.eq(slider.currentSlide).css({
                                "opacity": 0,
                                "zIndex": 1,
                                "display": "none"
                            });
                            slider.slides.eq(target).css({
                                "opacity": 1,
                                "zIndex": 2,
                                "display": "block"
                            });
                            slider.wrapup(dimension);
                        }
                    }
                    // SMOOTH HEIGHT:
                    if (slider.vars.smoothHeight) {
                        methods.smoothHeight(slider.vars.animationSpeed);
                    }
                }
            }
            ;
            slider.wrapup = function(dimension) {
                // SLIDE:
                if (!fade && !carousel) {
                    if (slider.currentSlide === 0 && slider.animatingTo === slider.last && slider.vars.animationLoop) {
                        slider.setProps(dimension, "jumpEnd");
                    } else if (slider.currentSlide === slider.last && slider.animatingTo === 0 && slider.vars.animationLoop) {
                        slider.setProps(dimension, "jumpStart");
                    }
                }
                slider.animating = false;
                slider.currentSlide = slider.animatingTo;
                // API: after() animation Callback
                slider.vars.after(slider);
            }
            ;

            // SLIDESHOW:
            slider.animateSlides = function() {
                if (!slider.animating && focused) {
                    slider.flexAnimate(slider.getTarget("next"));
                }
            }
            ;
            // SLIDESHOW:
            slider.pause = function() {
                clearInterval(slider.animatedSlides);
                slider.animatedSlides = null;
                slider.playing = false;
                // PAUSEPLAY:
                if (slider.vars.pausePlay) {
                    methods.pausePlay.update("play");
                }
                // SYNC:
                if (slider.syncExists) {
                    methods.sync("pause");
                }
            }
            ;
            // SLIDESHOW:
            slider.play = function() {
                if (slider.playing) {
                    clearInterval(slider.animatedSlides);
                }
                slider.animatedSlides = slider.animatedSlides || setInterval(slider.animateSlides, slider.vars.slideshowSpeed);
                slider.started = slider.playing = true;
                // PAUSEPLAY:
                if (slider.vars.pausePlay) {
                    methods.pausePlay.update("pause");
                }
                // SYNC:
                if (slider.syncExists) {
                    methods.sync("play");
                }
            }
            ;
            // STOP:
            slider.stop = function() {
                slider.pause();
                slider.stopped = true;
            }
            ;
            slider.canAdvance = function(target, fromNav) {
                // ASNAV:
                var last = (asNav) ? slider.pagingCount - 1 : slider.last;
                return (fromNav) ? true : (asNav && slider.currentItem === slider.count - 1 && target === 0 && slider.direction === "prev") ? true : (asNav && slider.currentItem === 0 && target === slider.pagingCount - 1 && slider.direction !== "next") ? false : (target === slider.currentSlide && !asNav) ? false : (slider.vars.animationLoop) ? true : (slider.atEnd && slider.currentSlide === 0 && target === last && slider.direction !== "next") ? false : (slider.atEnd && slider.currentSlide === last && target === 0 && slider.direction === "next") ? false : true;
            }
            ;
            slider.getTarget = function(dir) {
                slider.direction = dir;
                if (dir === "next") {
                    return (slider.currentSlide === slider.last) ? 0 : slider.currentSlide + 1;
                } else {
                    return (slider.currentSlide === 0) ? slider.last : slider.currentSlide - 1;
                }
            }
            ;

            // SLIDE:
            slider.setProps = function(pos, special, dur) {
                var target = (function() {
                    var posCheck = (pos) ? pos : ((slider.itemW + slider.vars.itemMargin) * slider.move) * slider.animatingTo
                        , posCalc = (function() {
                        if (carousel) {
                            return (special === "setTouch") ? pos : (reverse && slider.animatingTo === slider.last) ? 0 : (reverse) ? slider.limit - (((slider.itemW + slider.vars.itemMargin) * slider.move) * slider.animatingTo) : (slider.animatingTo === slider.last) ? slider.limit : posCheck;
                        } else {
                            switch (special) {
                                case "setTotal":
                                    return (reverse) ? ((slider.count - 1) - slider.currentSlide + slider.cloneOffset) * pos : (slider.currentSlide + slider.cloneOffset) * pos;
                                case "setTouch":
                                    return (reverse) ? pos : pos;
                                case "jumpEnd":
                                    return (reverse) ? pos : slider.count * pos;
                                case "jumpStart":
                                    return (reverse) ? slider.count * pos : pos;
                                default:
                                    return pos;
                            }
                        }
                    }());

                    return (posCalc * -1) + "px";
                }());

                if (slider.transitions) {
                    target = (vertical) ? "translate3d(0," + target + ",0)" : "translate3d(" + target + ",0,0)";
                    dur = (dur !== undefined) ? (dur / 1000) + "s" : "0s";
                    slider.container.css("-" + slider.pfx + "-transition-duration", dur);
                    slider.container.css("transition-duration", dur);
                }

                slider.args[slider.prop] = target;
                if (slider.transitions || dur === undefined) {
                    slider.container.css(slider.args);
                }

                slider.container.css('transform', target);
            }
            ;

            slider.setup = function(type) {
                // SLIDE:
                if (!fade) {
                    var sliderOffset, arr;

                    if (type === "init") {
                        slider.viewport = $('<div class="' + namespace + 'viewport"></div>').css({
                            "overflow": "hidden",
                            "position": "relative"
                        }).appendTo(slider).append(slider.container);
                        // INFINITE LOOP:
                        slider.cloneCount = 0;
                        slider.cloneOffset = 0;
                        // REVERSE:
                        if (reverse) {
                            arr = $.makeArray(slider.slides).reverse();
                            slider.slides = $(arr);
                            slider.container.empty().append(slider.slides);
                        }
                    }
                    // INFINITE LOOP && !CAROUSEL:
                    if (slider.vars.animationLoop && !carousel) {
                        slider.cloneCount = 2;
                        slider.cloneOffset = 1;
                        // clear out old clones
                        if (type !== "init") {
                            slider.container.find('.clone').remove();
                        }
                        slider.container.append(methods.uniqueID(slider.slides.first().clone().addClass('clone')).attr('aria-hidden', 'true')).prepend(methods.uniqueID(slider.slides.last().clone().addClass('clone')).attr('aria-hidden', 'true'));
                    }
                    slider.newSlides = $(slider.vars.selector, slider);

                    sliderOffset = (reverse) ? slider.count - 1 - slider.currentSlide + slider.cloneOffset : slider.currentSlide + slider.cloneOffset;
                    // VERTICAL:
                    if (vertical && !carousel) {
                        slider.container.height((slider.count + slider.cloneCount) * 200 + "%").css("position", "absolute").width("100%");
                        setTimeout(function() {
                            slider.newSlides.css({
                                "display": "block"
                            });
                            slider.doMath();
                            slider.viewport.height(slider.h);
                            slider.setProps(sliderOffset * slider.h, "init");
                        }, (type === "init") ? 100 : 0);
                    } else {
                        slider.container.width((slider.count + slider.cloneCount) * 200 + "%");
                        slider.setProps(sliderOffset * slider.computedW, "init");
                        setTimeout(function() {
                            slider.doMath();
                            slider.newSlides.css({
                                "width": slider.computedW,
                                "marginRight": slider.computedM,
                                "float": "left",
                                "display": "block"
                            });
                            // SMOOTH HEIGHT:
                            if (slider.vars.smoothHeight) {
                                methods.smoothHeight();
                            }
                        }, (type === "init") ? 100 : 0);
                    }
                } else {
                    // FADE:
                    slider.slides.css({
                        "width": "100%",
                        "float": "left",
                        "marginRight": "-100%",
                        "position": "relative"
                    });
                    if (type === "init") {
                        if (!touch) {
                            //slider.slides.eq(slider.currentSlide).fadeIn(slider.vars.animationSpeed, slider.vars.easing);
                            if (slider.vars.fadeFirstSlide == false) {
                                slider.slides.css({
                                    "opacity": 0,
                                    "display": "none",
                                    "zIndex": 1
                                }).eq(slider.currentSlide).css({
                                    "zIndex": 2,
                                    "display": "block"
                                }).css({
                                    "opacity": 1
                                });
                            } else {
                                slider.slides.css({
                                    "opacity": 0,
                                    "display": "none",
                                    "zIndex": 1
                                }).eq(slider.currentSlide).css({
                                    "zIndex": 2,
                                    "display": "block"
                                }).animate({
                                    "opacity": 1
                                }, slider.vars.animationSpeed, slider.vars.easing);
                            }
                        } else {
                            slider.slides.css({
                                "opacity": 0,
                                "display": "none",
                                "webkitTransition": "opacity " + slider.vars.animationSpeed / 1000 + "s ease",
                                "zIndex": 1
                            }).eq(slider.currentSlide).css({
                                "opacity": 1,
                                "zIndex": 2,
                                "display": "block"
                            });
                        }
                    }
                    // SMOOTH HEIGHT:
                    if (slider.vars.smoothHeight) {
                        methods.smoothHeight();
                    }
                }
                // !CAROUSEL:
                // CANDIDATE: active slide
                if (!carousel) {
                    slider.slides.removeClass(namespace + "active-slide").eq(slider.currentSlide).addClass(namespace + "active-slide");
                }

                //FlexSlider: init() Callback
                slider.vars.init(slider);
            }
            ;

            slider.doMath = function() {
                var slide = slider.slides.first()
                    , slideMargin = slider.vars.itemMargin
                    , minItems = slider.vars.minItems
                    , maxItems = slider.vars.maxItems;

                slider.w = (slider.viewport === undefined) ? slider.width() : slider.viewport.width();
                slider.h = slide.height();
                slider.boxPadding = slide.outerWidth() - slide.width();

                // CAROUSEL:
                if (carousel) {
                    slider.itemT = slider.vars.itemWidth + slideMargin;
                    slider.itemM = slideMargin;
                    slider.minW = (minItems) ? minItems * slider.itemT : slider.w;
                    slider.maxW = (maxItems) ? (maxItems * slider.itemT) - slideMargin : slider.w;
                    slider.itemW = (slider.minW > slider.w) ? (slider.w - (slideMargin * (minItems - 1))) / minItems : (slider.maxW < slider.w) ? (slider.w - (slideMargin * (maxItems - 1))) / maxItems : (slider.vars.itemWidth > slider.w) ? slider.w : slider.vars.itemWidth;

                    slider.visible = Math.floor(slider.w / (slider.itemW));
                    slider.move = (slider.vars.move > 0 && slider.vars.move < slider.visible) ? slider.vars.move : slider.visible;
                    slider.pagingCount = Math.ceil(((slider.count - slider.visible) / slider.move) + 1);
                    slider.last = slider.pagingCount - 1;
                    slider.limit = (slider.pagingCount === 1) ? 0 : (slider.vars.itemWidth > slider.w) ? (slider.itemW * (slider.count - 1)) + (slideMargin * (slider.count - 1)) : ((slider.itemW + slideMargin) * slider.count) - slider.w - slideMargin;
                } else {
                    slider.itemW = slider.w;
                    slider.itemM = slideMargin;
                    slider.pagingCount = slider.count;
                    slider.last = slider.count - 1;
                }
                slider.computedW = slider.itemW - slider.boxPadding;
                slider.computedM = slider.itemM;
            }
            ;

            slider.update = function(pos, action) {
                slider.doMath();

                // update currentSlide and slider.animatingTo if necessary
                if (!carousel) {
                    if (pos < slider.currentSlide) {
                        slider.currentSlide += 1;
                    } else if (pos <= slider.currentSlide && pos !== 0) {
                        slider.currentSlide -= 1;
                    }
                    slider.animatingTo = slider.currentSlide;
                }

                // update controlNav
                if (slider.vars.controlNav && !slider.manualControls) {
                    if ((action === "add" && !carousel) || slider.pagingCount > slider.controlNav.length) {
                        methods.controlNav.update("add");
                    } else if ((action === "remove" && !carousel) || slider.pagingCount < slider.controlNav.length) {
                        if (carousel && slider.currentSlide > slider.last) {
                            slider.currentSlide -= 1;
                            slider.animatingTo -= 1;
                        }
                        methods.controlNav.update("remove", slider.last);
                    }
                }
                // update directionNav
                if (slider.vars.directionNav) {
                    methods.directionNav.update();
                }

            }
            ;

            slider.addSlide = function(obj, pos) {
                var $obj = $(obj);

                slider.count += 1;
                slider.last = slider.count - 1;

                // append new slide
                if (vertical && reverse) {
                    (pos !== undefined) ? slider.slides.eq(slider.count - pos).after($obj) : slider.container.prepend($obj);
                } else {
                    (pos !== undefined) ? slider.slides.eq(pos).before($obj) : slider.container.append($obj);
                }

                // update currentSlide, animatingTo, controlNav, and directionNav
                slider.update(pos, "add");

                // update slider.slides
                slider.slides = $(slider.vars.selector + ':not(.clone)', slider);
                // re-setup the slider to accomdate new slide
                slider.setup();

                //FlexSlider: added() Callback
                slider.vars.added(slider);
            }
            ;
            slider.removeSlide = function(obj) {
                var pos = (isNaN(obj)) ? slider.slides.index($(obj)) : obj;

                // update count
                slider.count -= 1;
                slider.last = slider.count - 1;

                // remove slide
                if (isNaN(obj)) {
                    $(obj, slider.slides).remove();
                } else {
                    (vertical && reverse) ? slider.slides.eq(slider.last).remove() : slider.slides.eq(obj).remove();
                }

                // update currentSlide, animatingTo, controlNav, and directionNav
                slider.doMath();
                slider.update(pos, "remove");

                // update slider.slides
                slider.slides = $(slider.vars.selector + ':not(.clone)', slider);
                // re-setup the slider to accomdate new slide
                slider.setup();

                // FlexSlider: removed() Callback
                slider.vars.removed(slider);
            }
            ;

            //FlexSlider: Initialize
            methods.init();
        }
        ;

        // Ensure the slider isn't focussed if the window loses focus.
        $(window).blur(function(e) {
            focused = false;
        }).focus(function(e) {
            focused = true;
        });

        //FlexSlider: Default Settings
        $.flexslider.defaults = {
            namespace: "flex-",
            //{NEW} String: Prefix string attached to the class of every element generated by the plugin
            selector: ".slides > li",
            //{NEW} Selector: Must match a simple pattern. '{container} > {slide}' -- Ignore pattern at your own peril
            animation: "fade",
            //String: Select your animation type, "fade" or "slide"
            easing: "swing",
            //{NEW} String: Determines the easing method used in jQuery transitions. jQuery easing plugin is supported!
            direction: "horizontal",
            //String: Select the sliding direction, "horizontal" or "vertical"
            reverse: false,
            //{NEW} Boolean: Reverse the animation direction
            animationLoop: true,
            //Boolean: Should the animation loop? If false, directionNav will received "disable" classes at either end
            smoothHeight: false,
            //{NEW} Boolean: Allow height of the slider to animate smoothly in horizontal mode
            startAt: 0,
            //Integer: The slide that the slider should start on. Array notation (0 = first slide)
            slideshow: true,
            //Boolean: Animate slider automatically
            slideshowSpeed: 7000,
            //Integer: Set the speed of the slideshow cycling, in milliseconds
            animationSpeed: 600,
            //Integer: Set the speed of animations, in milliseconds
            initDelay: 0,
            //{NEW} Integer: Set an initialization delay, in milliseconds
            randomize: false,
            //Boolean: Randomize slide order
            fadeFirstSlide: true,
            //Boolean: Fade in the first slide when animation type is "fade"
            thumbCaptions: false,
            //Boolean: Whether or not to put captions on thumbnails when using the "thumbnails" controlNav.

            // Usability features
            pauseOnAction: true,
            //Boolean: Pause the slideshow when interacting with control elements, highly recommended.
            pauseOnHover: false,
            //Boolean: Pause the slideshow when hovering over slider, then resume when no longer hovering
            pauseInvisible: true,
            //{NEW} Boolean: Pause the slideshow when tab is invisible, resume when visible. Provides better UX, lower CPU usage.
            useCSS: true,
            //{NEW} Boolean: Slider will use CSS3 transitions if available
            touch: true,
            //{NEW} Boolean: Allow touch swipe navigation of the slider on touch-enabled devices
            video: false,
            //{NEW} Boolean: If using video in the slider, will prevent CSS3 3D Transforms to avoid graphical glitches

            // Primary Controls
            controlNav: true,
            //Boolean: Create navigation for paging control of each slide? Note: Leave true for manualControls usage
            directionNav: true,
            //Boolean: Create navigation for previous/next navigation? (true/false)
            prevText: "Previous",
            //String: Set the text for the "previous" directionNav item
            nextText: "Next",
            //String: Set the text for the "next" directionNav item

            // Secondary Navigation
            keyboard: true,
            //Boolean: Allow slider navigating via keyboard left/right keys
            multipleKeyboard: false,
            //{NEW} Boolean: Allow keyboard navigation to affect multiple sliders. Default behavior cuts out keyboard navigation with more than one slider present.
            mousewheel: false,
            //{UPDATED} Boolean: Requires jquery.mousewheel.js (https://github.com/brandonaaron/jquery-mousewheel) - Allows slider navigating via mousewheel
            pausePlay: false,
            //Boolean: Create pause/play dynamic element
            pauseText: "Pause",
            //String: Set the text for the "pause" pausePlay item
            playText: "Play",
            //String: Set the text for the "play" pausePlay item

            // Special properties
            controlsContainer: "",
            //{UPDATED} jQuery Object/Selector: Declare which container the navigation elements should be appended too. Default container is the FlexSlider element. Example use would be $(".flexslider-container"). Property is ignored if given element is not found.
            manualControls: "",
            //{UPDATED} jQuery Object/Selector: Declare custom control navigation. Examples would be $(".flex-control-nav li") or "#tabs-nav li img", etc. The number of elements in your controlNav should match the number of slides/tabs.
            customDirectionNav: "",
            //{NEW} jQuery Object/Selector: Custom prev / next button. Must be two jQuery elements. In order to make the events work they have to have the classes "prev" and "next" (plus namespace)
            sync: "",
            //{NEW} Selector: Mirror the actions performed on this slider with another slider. Use with care.
            asNavFor: "",
            //{NEW} Selector: Internal property exposed for turning the slider into a thumbnail navigation for another slider

            // Carousel Options
            itemWidth: 0,
            //{NEW} Integer: Box-model width of individual carousel items, including horizontal borders and padding.
            itemMargin: 0,
            //{NEW} Integer: Margin between carousel items.
            minItems: 1,
            //{NEW} Integer: Minimum number of carousel items that should be visible. Items will resize fluidly when below this.
            maxItems: 0,
            //{NEW} Integer: Maxmimum number of carousel items that should be visible. Items will resize fluidly when above this limit.
            move: 0,
            //{NEW} Integer: Number of carousel items that should move on animation. If 0, slider will move all visible items.
            allowOneSlide: true,
            //{NEW} Boolean: Whether or not to allow a slider comprised of a single slide

            // Callback API
            start: function() {},
            //Callback: function(slider) - Fires when the slider loads the first slide
            before: function() {},
            //Callback: function(slider) - Fires asynchronously with each slider animation
            after: function() {},
            //Callback: function(slider) - Fires after each slider animation completes
            end: function() {},
            //Callback: function(slider) - Fires when the slider reaches the last slide (asynchronous)
            added: function() {},
            //{NEW} Callback: function(slider) - Fires after a slide is added
            removed: function() {},
            //{NEW} Callback: function(slider) - Fires after a slide is removed
            init: function() {}//{NEW} Callback: function(slider) - Fires after the slider is initially setup
        };

        //FlexSlider: Plugin Function
        $.fn.flexslider = function(options) {
            if (options === undefined) {
                options = {};
            }

            if (typeof options === "object") {
                return this.each(function() {
                    var $this = $(this)
                        , selector = (options.selector) ? options.selector : ".slides > li"
                        , $slides = $this.find(selector);

                    if (($slides.length === 1 && options.allowOneSlide === false) || $slides.length === 0) {
                        $slides.fadeIn(400);
                        if (options.start) {
                            options.start($this);
                        }
                    } else if ($this.data('flexslider') === undefined) {
                        new $.flexslider(this,options);
                    }
                });
            } else {
                // Helper strings to quickly perform functions on the slider
                var $slider = $(this).data('flexslider');
                switch (options) {
                    case "play":
                        $slider.play();
                        break;
                    case "pause":
                        $slider.pause();
                        break;
                    case "stop":
                        $slider.stop();
                        break;
                    case "next":
                        $slider.flexAnimate($slider.getTarget("next"), true);
                        break;
                    case "prev":
                    case "previous":
                        $slider.flexAnimate($slider.getTarget("prev"), true);
                        break;
                    default:
                        if (typeof options === "number") {
                            $slider.flexAnimate(options, true);
                        }
                }
            }
        }
        ;
    }
)(jQuery);

!function(e, t) {
    "object" == typeof exports && "object" == typeof module ? module.exports = t() : "function" == typeof define && define.amd ? define("DPlayer", [], t) : "object" == typeof exports ? exports.DPlayer = t() : e.DPlayer = t()
}(this, function() {
    return function(e) {
        function t(i) {
            if (n[i])
                return n[i].exports;
            var a = n[i] = {
                i: i,
                l: !1,
                exports: {}
            };
            return e[i].call(a.exports, a, a.exports, t),
                a.l = !0,
                a.exports
        }
        var n = {};
        return t.m = e,
            t.c = n,
            t.d = function(e, n, i) {
                t.o(e, n) || Object.defineProperty(e, n, {
                    configurable: !1,
                    enumerable: !0,
                    get: i
                })
            }
            ,
            t.n = function(e) {
                var n = e && e.__esModule ? function() {
                        return e.default
                    }
                    : function() {
                        return e
                    }
                ;
                return t.d(n, "a", n),
                    n
            }
            ,
            t.o = function(e, t) {
                return Object.prototype.hasOwnProperty.call(e, t)
            }
            ,
            t.p = "/",
            t(t.s = 1)
    }([function(e, t, n) {
        "use strict";
        e.exports = {
            secondToTime: function(e) {
                var t = function(e) {
                    return e < 10 ? "0" + e : "" + e
                }
                    , n = parseInt(e / 60)
                    , i = parseInt(e - 60 * n);
                return t(n) + ":" + t(i)
            },
            getElementViewLeft: function(e) {
                var t = e.offsetLeft
                    , n = e.offsetParent
                    , i = document.body.scrollLeft + document.documentElement.scrollLeft;
                if (document.fullscreenElement || document.mozFullScreenElement || document.webkitFullscreenElement)
                    for (; null !== n && n !== e; )
                        t += n.offsetLeft,
                            n = n.offsetParent;
                else
                    for (; null !== n; )
                        t += n.offsetLeft,
                            n = n.offsetParent;
                return t - i
            },
            getScrollPosition: function() {
                return {
                    left: window.pageXOffset || document.documentElement.scrollLeft || document.body.scrollLeft || 0,
                    top: window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0
                }
            },
            setScrollPosition: function(e) {
                var t = e.left
                    , n = void 0 === t ? 0 : t
                    , i = e.top
                    , a = void 0 === i ? 0 : i;
                this.isFirefox ? (document.documentElement.scrollLeft = n,
                    document.documentElement.scrollTop = a) : window.scrollTo(n, a)
            },
            isMobile: /mobile/i.test(window.navigator.userAgent),
            isFirefox: /firefox/i.test(window.navigator.userAgent),
            isChrome: /chrome/i.test(window.navigator.userAgent),
            storage: {
                set: function(e, t) {
                    localStorage.setItem(e, t)
                },
                get: function(e) {
                    return localStorage.getItem(e)
                }
            }
        }
    }
        , function(e, t, n) {
            "use strict";
            e.exports = n(2)
        }
        , function(e, t, n) {
            "use strict";
            function i(e) {
                return e && e.__esModule ? e : {
                    default: e
                }
            }
            function a(e, t) {
                if (!(e instanceof t))
                    throw new TypeError("Cannot call a class as a function")
            }
            var s = function() {
                function e(e, t) {
                    for (var n = 0; n < t.length; n++) {
                        var i = t[n];
                        i.enumerable = i.enumerable || !1,
                            i.configurable = !0,
                        "value"in i && (i.writable = !0),
                            Object.defineProperty(e, i.key, i)
                    }
                }
                return function(t, n, i) {
                    return n && e(t.prototype, n),
                    i && e(t, i),
                        t
                }
            }();
            n(3);
            var o = n(0)
                , l = i(o)
                , r = n(4)
                , d = i(r)
                , c = n(6)
                , u = i(c)
                , p = n(7)
                , h = i(p)
                , m = n(8)
                , y = i(m)
                , v = n(9)
                , f = i(v)
                , g = n(10)
                , b = i(g)
                , k = n(11)
                , w = i(k)
                , E = n(12)
                , L = i(E)
                , x = n(13)
                , T = i(x)
                , C = n(14)
                , B = i(C)
                , q = 0
                , M = []
                , S = function() {
                function e(t) {
                    var n = this;
                    a(this, e),
                        this.options = (0,
                            d.default)(t),
                        this.options.container.classList.add("dplayer"),
                    this.options.video.quality && (this.qualityIndex = this.options.video.defaultQuality,
                        this.quality = this.options.video.quality[this.options.video.defaultQuality]),
                        this.tran = new u.default(this.options.lang).tran,
                        this.icons = new y.default(this.options),
                        this.events = new w.default,
                        this.user = new T.default(this),
                        this.container = this.options.container,
                    this.options.danmaku || this.container.classList.add("dplayer-no-danmaku"),
                    o.isMobile && this.container.classList.add("dplayer-mobile"),
                        this.container.innerHTML = h.default.main(this.options, q, this.tran, this.icons);
                    var i = {};
                    i.volumeBar = this.container.getElementsByClassName("dplayer-volume-bar-inner")[0],
                        i.playedBar = this.container.getElementsByClassName("dplayer-played")[0],
                        i.loadedBar = this.container.getElementsByClassName("dplayer-loaded")[0];
                    var s = this.container.getElementsByClassName("dplayer-bar-wrap")[0]
                        , r = this.container.getElementsByClassName("dplayer-bar-time")[0]
                        , c = void 0;
                    if (this.updateBar = function(e, t, n) {
                            t = t > 0 ? t : 0,
                                t = t < 1 ? t : 1,
                                i[e + "Bar"].style[n] = 100 * t + "%"
                        }
                            ,
                            document.addEventListener("click", function() {
                                n.focus = !1
                            }, !0),
                            this.container.addEventListener("click", function() {
                                n.focus = !0
                            }, !0),
                        this.options.danmaku && (this.danmaku = new f.default({
                            container: this.container.getElementsByClassName("dplayer-danmaku")[0],
                            opacity: this.user.get("opacity"),
                            callback: function() {
                                n.container.getElementsByClassName("dplayer-danloading")[0].style.display = "none",
                                    n.options.autoplay && !o.isMobile ? n.play() : o.isMobile && n.pause()
                            },
                            error: function(e) {
                                n.notice(e)
                            },
                            apiBackend: this.options.apiBackend,
                            borderColor: this.options.theme,
                            height: this.arrow ? 24 : 30,
                            time: function() {
                                return n.video.currentTime
                            },
                            unlimited: this.user.get("unlimited"),
                            api: {
                                id: this.options.danmaku.id,
                                address: this.options.danmaku.api,
                                token: this.options.danmaku.token,
                                maximum: this.options.danmaku.maximum,
                                addition: this.options.danmaku.addition,
                                user: this.options.danmaku.user
                            },
                            events: this.events
                        })),
                            this.arrow = this.container.offsetWidth <= 500,
                            this.arrow) {
                        var p = document.createElement("style");
                        p.innerHTML = ".dplayer .dplayer-danmaku{font-size:18px}",
                            document.head.appendChild(p)
                    }
                    this.video = this.container.getElementsByClassName("dplayer-video-current")[0],
                        this.bezel = this.container.getElementsByClassName("dplayer-bezel-icon")[0],
                        this.bezel.addEventListener("animationend", function() {
                            n.bezel.classList.remove("dplayer-bezel-transition")
                        }),
                        this.playButton = this.container.getElementsByClassName("dplayer-play-icon")[0],
                        this.paused = !0,
                        this.playButton.addEventListener("click", function() {
                            n.toggle()
                        });
                    var m = this.container.getElementsByClassName("dplayer-video-wrap")[0]
                        , v = this.container.getElementsByClassName("dplayer-controller-mask")[0];
                    if (o.isMobile) {
                        var g = function() {
                            n.container.classList.contains("dplayer-hide-controller") ? n.container.classList.remove("dplayer-hide-controller") : n.container.classList.add("dplayer-hide-controller")
                        };
                        m.addEventListener("click", g),
                            v.addEventListener("click", g)
                    } else
                        m.addEventListener("click", function() {
                            n.toggle()
                        }),
                            v.addEventListener("click", function() {
                                n.toggle()
                            });
                    var b = 0
                        , k = 0
                        , E = !1;
                    window.requestAnimationFrame = function() {
                        return window.requestAnimationFrame || window.webkitRequestAnimationFrame || window.mozRequestAnimationFrame || window.oRequestAnimationFrame || window.msRequestAnimationFrame || function(e) {
                            window.setTimeout(e, 1e3 / 60)
                        }
                    }();
                    var x = function() {
                        n.checkLoading = setInterval(function() {
                            k = n.video.currentTime,
                            E || k !== b || n.video.paused || (n.container.classList.add("dplayer-loading"),
                                E = !0),
                            E && k > b && !n.video.paused && (n.container.classList.remove("dplayer-loading"),
                                E = !1),
                                b = k
                        }, 100)
                    }
                        , C = function() {
                        clearInterval(n.checkLoading)
                    };
                    this.playedTime = !1,
                        this.animationFrame = function() {
                            n.playedTime && (n.updateBar("played", n.video.currentTime / n.video.duration, "width"),
                                n.container.getElementsByClassName("dplayer-ptime")[0].innerHTML = l.default.secondToTime(n.video.currentTime)),
                                window.requestAnimationFrame(n.animationFrame)
                        }
                        ,
                        window.requestAnimationFrame(this.animationFrame),
                        this.setTime = function(e) {
                            e ? (n[e + "Time"] = !0,
                            "played" === e && x()) : (n.playedTime = !0,
                                x())
                        }
                        ,
                        this.clearTime = function(e) {
                            e ? (n[e + "Time"] = !1,
                            "played" === e && C()) : (n.playedTime = !1,
                                C())
                        }
                        ,
                    this.options.video.thumbnails && this.initThumbnails(),
                        this.isTimeTipsShow = !0,
                        this.mouseHandler = this.mouseHandler(s, r).bind(this),
                        s.addEventListener("mousemove", this.mouseHandler),
                        s.addEventListener("mouseenter", this.mouseHandler),
                        s.addEventListener("mouseleave", this.mouseHandler);
                    var B = function(e) {
                        var t = (e.clientX - l.default.getElementViewLeft(s)) / c;
                        t = t > 0 ? t : 0,
                            t = t < 1 ? t : 1,
                            n.updateBar("played", t, "width"),
                            n.container.getElementsByClassName("dplayer-ptime")[0].innerHTML = l.default.secondToTime(t * n.video.duration)
                    }
                        , S = function e(t) {
                        document.removeEventListener("mouseup", e),
                            document.removeEventListener("mousemove", B);
                        var a = (t.clientX - l.default.getElementViewLeft(s)) / c;
                        a = a > 0 ? a : 0,
                            a = a < 1 ? a : 1,
                            n.updateBar("played", a, "width"),
                            n.seek(parseFloat(i.playedBar.style.width) / 100 * n.video.duration),
                            n.setTime()
                    };
                    s.addEventListener("mousedown", function() {
                        c = s.clientWidth,
                            n.clearTime(),
                            document.addEventListener("mousemove", B),
                            document.addEventListener("mouseup", S)
                    });
                    var z = this.container.getElementsByClassName("dplayer-volume")[0]
                        , N = this.container.getElementsByClassName("dplayer-volume-bar-wrap")[0]
                        , _ = this.container.getElementsByClassName("dplayer-volume-bar")[0]
                        , F = this.container.getElementsByClassName("dplayer-volume-icon")[0].getElementsByClassName("dplayer-icon-content")[0];
                    this.switchVolumeIcon = function() {
                        n.volume() >= .95 ? F.innerHTML = n.icons.get("volume-up") : n.volume() > 0 ? F.innerHTML = n.icons.get("volume-down") : F.innerHTML = n.icons.get("volume-off")
                    }
                    ;
                    var P = function(e) {
                        var t = e || window.event
                            , i = (t.clientX - l.default.getElementViewLeft(_) - 5.5) / 35;
                        n.volume(i)
                    }
                        , H = function e() {
                        document.removeEventListener("mouseup", e),
                            document.removeEventListener("mousemove", P),
                            z.classList.remove("dplayer-volume-active")
                    };
                    N.addEventListener("click", function(e) {
                        var t = e || window.event
                            , i = (t.clientX - l.default.getElementViewLeft(_) - 5.5) / 35;
                        n.volume(i)
                    }),
                        N.addEventListener("mousedown", function() {
                            document.addEventListener("mousemove", P),
                                document.addEventListener("mouseup", H),
                                z.classList.add("dplayer-volume-active")
                        }),
                        F.addEventListener("click", function() {
                            n.video.muted ? (n.video.muted = !1,
                                n.switchVolumeIcon(),
                                n.updateBar("volume", n.volume(), "width")) : (n.video.muted = !0,
                                F.innerHTML = n.icons.get("volume-off"),
                                n.updateBar("volume", 0, "width"))
                        }),
                        this.hideTime = 0;
                    var D = function() {
                        n.container.classList.remove("dplayer-hide-controller"),
                            clearTimeout(n.hideTime),
                            n.hideTime = setTimeout(function() {
                                n.video.played.length && !n.disableHideController && (n.container.classList.add("dplayer-hide-controller"),
                                    V(),
                                    te())
                            }, 2e3)
                    };
                    o.isMobile || (this.container.addEventListener("mousemove", D),
                        this.container.addEventListener("click", D));
                    var I = h.default.setting(this.tran, this.icons)
                        , O = this.container.getElementsByClassName("dplayer-setting-icon")[0]
                        , A = this.container.getElementsByClassName("dplayer-setting-box")[0]
                        , R = this.container.getElementsByClassName("dplayer-mask")[0];
                    A.innerHTML = I.original;
                    var V = function() {
                        A.classList.contains("dplayer-setting-box-open") && (A.classList.remove("dplayer-setting-box-open"),
                            R.classList.remove("dplayer-mask-show"),
                            setTimeout(function() {
                                A.classList.remove("dplayer-setting-box-narrow"),
                                    A.innerHTML = I.original,
                                    Q()
                            }, 300)),
                            n.disableHideController = !1
                    }
                        , j = function() {
                        n.disableHideController = !0,
                            A.classList.add("dplayer-setting-box-open"),
                            R.classList.add("dplayer-mask-show")
                    };
                    R.addEventListener("click", function() {
                        V()
                    }),
                        O.addEventListener("click", function() {
                            j()
                        }),
                        this.loop = this.options.loop;
                    var W = this.user.get("danmaku");
                    W || this.danmaku && this.danmaku.hide();
                    var X = this.user.get("unlimited")
                        , Q = function() {
                        var e = n.container.getElementsByClassName("dplayer-setting-loop")[0]
                            , t = e.getElementsByClassName("dplayer-toggle-setting-input")[0];
                        t.checked = n.loop,
                            e.addEventListener("click", function() {
                                t.checked = !t.checked,
                                    t.checked ? n.loop = !0 : n.loop = !1,
                                    V()
                            });
                        var a = n.container.getElementsByClassName("dplayer-setting-showdan")[0]
                            , s = a.getElementsByClassName("dplayer-showdan-setting-input")[0];
                        s.checked = W,
                            a.addEventListener("click", function() {
                                s.checked = !s.checked,
                                    s.checked ? (W = !0,
                                    n.paused || n.danmaku.show()) : (W = !1,
                                        n.danmaku.hide()),
                                    n.user.set("danmaku", W ? 1 : 0),
                                    V()
                            });
                        var o = n.container.getElementsByClassName("dplayer-setting-danunlimit")[0]
                            , r = o.getElementsByClassName("dplayer-danunlimit-setting-input")[0];
                        if (r.checked = X,
                                o.addEventListener("click", function() {
                                    r.checked = !r.checked,
                                        r.checked ? (X = !0,
                                            n.danmaku.unlimit(!0)) : (X = !1,
                                            n.danmaku.unlimit(!1)),
                                        n.user.set("unlimited", X ? 1 : 0),
                                        V()
                                }),
                                n.container.getElementsByClassName("dplayer-setting-speed")[0].addEventListener("click", function() {
                                    A.classList.add("dplayer-setting-box-narrow"),
                                        A.innerHTML = I.speed;
                                    for (var e = A.getElementsByClassName("dplayer-setting-speed-item"), t = 0; t < e.length; t++)
                                        !function(t) {
                                            e[t].addEventListener("click", function() {
                                                n.video.playbackRate = e[t].dataset.speed,
                                                    V()
                                            })
                                        }(t)
                                }),
                                n.danmaku) {
                            i.danmakuBar = n.container.getElementsByClassName("dplayer-danmaku-bar-inner")[0];
                            var d = n.container.getElementsByClassName("dplayer-danmaku-bar-wrap")[0]
                                , c = n.container.getElementsByClassName("dplayer-danmaku-bar")[0]
                                , u = n.container.getElementsByClassName("dplayer-setting-danmaku")[0];
                            n.on("danmaku_opacity", function(e) {
                                n.updateBar("danmaku", e, "width"),
                                    n.user.set("opacity", e)
                            }),
                                n.danmaku.opacity(n.user.get("opacity"));
                            var p = function(e) {
                                var t = e || window.event
                                    , i = (t.clientX - l.default.getElementViewLeft(c)) / 130;
                                i = i > 0 ? i : 0,
                                    i = i < 1 ? i : 1,
                                    n.danmaku.opacity(i)
                            }
                                , h = function e() {
                                document.removeEventListener("mouseup", e),
                                    document.removeEventListener("mousemove", p),
                                    u.classList.remove("dplayer-setting-danmaku-active")
                            };
                            d.addEventListener("click", function(e) {
                                var t = e || window.event
                                    , i = (t.clientX - l.default.getElementViewLeft(c)) / 130;
                                i = i > 0 ? i : 0,
                                    i = i < 1 ? i : 1,
                                    n.danmaku.opacity(i)
                            }),
                                d.addEventListener("mousedown", function() {
                                    document.addEventListener("mousemove", p),
                                        document.addEventListener("mouseup", h),
                                        u.classList.add("dplayer-setting-danmaku-active")
                                })
                        }
                    };
                    Q(),
                    1 !== this.video.duration && (this.container.getElementsByClassName("dplayer-dtime")[0].innerHTML = this.video.duration ? l.default.secondToTime(this.video.duration) : "00:00"),
                    this.danmaku || (this.options.autoplay && !o.isMobile ? this.play() : o.isMobile && this.pause());
                    var U = this.container.getElementsByClassName("dplayer-controller")[0]
                        , Y = this.container.getElementsByClassName("dplayer-comment-input")[0]
                        , $ = this.container.getElementsByClassName("dplayer-comment-icon")[0]
                        , J = this.container.getElementsByClassName("dplayer-comment-setting-icon")[0]
                        , G = this.container.getElementsByClassName("dplayer-comment-setting-box")[0]
                        , K = this.container.getElementsByClassName("dplayer-send-icon")[0]
                        , Z = function() {
                        G.classList.contains("dplayer-comment-setting-open") && G.classList.remove("dplayer-comment-setting-open")
                    }
                        , ee = function() {
                        G.classList.contains("dplayer-comment-setting-open") ? G.classList.remove("dplayer-comment-setting-open") : G.classList.add("dplayer-comment-setting-open")
                    }
                        , te = function() {
                        U.classList.contains("dplayer-controller-comment") && (U.classList.remove("dplayer-controller-comment"),
                            R.classList.remove("dplayer-mask-show"),
                            n.container.classList.remove("dplayer-show-controller"),
                            Z(),
                            n.disableHideController = !1)
                    }
                        , ne = function() {
                        n.disableHideController = !0,
                        U.classList.contains("dplayer-controller-comment") || (U.classList.add("dplayer-controller-comment"),
                            R.classList.add("dplayer-mask-show"),
                            n.container.classList.add("dplayer-show-controller"),
                            Y.focus())
                    };
                    R.addEventListener("click", function() {
                        te()
                    }),
                        $.addEventListener("click", function() {
                            ne()
                        }),
                        J.addEventListener("click", function() {
                            ee()
                        });
                    var ie = this.container.getElementsByClassName("dplayer-comment-setting-color")[0];
                    ie.addEventListener("click", function() {
                        if (ie.querySelector("input:checked+span")) {
                            var e = ie.querySelector("input:checked").value;
                            J.getElementsByClassName("dplayer-fill")[0].style.fill = e,
                                Y.style.color = e,
                                K.getElementsByClassName("dplayer-fill")[0].style.fill = e
                        }
                    });
                    var ae = function() {
                        if (Y.blur(),
                                !Y.value.replace(/^\s+|\s+$/g, ""))
                            return void n.notice(n.tran("Please input danmaku content!"));
                        n.danmaku.send({
                            text: Y.value,
                            color: n.container.querySelector(".dplayer-comment-setting-color input:checked").value,
                            type: n.container.querySelector(".dplayer-comment-setting-type input:checked").value
                        }, function() {
                            Y.value = "",
                                te()
                        })
                    };
                    Y.addEventListener("click", function() {
                        Z()
                    }),
                        Y.addEventListener("keydown", function(e) {
                            13 === (e || window.event).keyCode && ae()
                        }),
                        K.addEventListener("click", ae),
                        this.fullScreen = new L.default(this),
                        this.container.getElementsByClassName("dplayer-full-icon")[0].addEventListener("click", function() {
                            n.fullScreen.toggle("browser")
                        }),
                        this.container.getElementsByClassName("dplayer-full-in-icon")[0].addEventListener("click", function() {
                            n.fullScreen.toggle("web")
                        });
                    var se = function(e) {
                        if (n.focus) {
                            var t = document.activeElement.tagName.toUpperCase()
                                , i = document.activeElement.getAttribute("contenteditable");
                            if ("INPUT" !== t && "TEXTAREA" !== t && "" !== i && "true" !== i) {
                                var a = e || window.event
                                    , s = void 0;
                                switch (a.keyCode) {
                                    case 32:
                                        a.preventDefault(),
                                            n.toggle();
                                        break;
                                    case 37:
                                        a.preventDefault(),
                                            n.seek(n.video.currentTime - 5),
                                            D();
                                        break;
                                    case 39:
                                        a.preventDefault(),
                                            n.seek(n.video.currentTime + 5),
                                            D();
                                        break;
                                    case 38:
                                        a.preventDefault(),
                                            s = n.volume() + .1,
                                            n.volume(s);
                                        break;
                                    case 40:
                                        a.preventDefault(),
                                            s = n.volume() - .1,
                                            n.volume(s)
                                }
                            }
                        }
                    };
                    this.options.hotkey && document.addEventListener("keydown", se),
                        document.addEventListener("keydown", function(e) {
                            switch ((e || window.event).keyCode) {
                                case 27:
                                    n.fullScreen.isFullScreen("web") && n.fullScreen.cancel("web")
                            }
                        });
                    var oe = this.container.getElementsByClassName("dplayer-menu")[0];
                    if (this.container.addEventListener("contextmenu", function(e) {
                            var t = e || window.event;
                            t.preventDefault(),
                                oe.classList.add("dplayer-menu-show");
                            var i = n.container.getBoundingClientRect()
                                , a = t.clientX - i.left
                                , s = t.clientY - i.top;
                            a + oe.offsetWidth >= i.width ? (oe.style.right = i.width - a + "px",
                                oe.style.left = "initial") : (oe.style.left = t.clientX - n.container.getBoundingClientRect().left + "px",
                                oe.style.right = "initial"),
                                s + oe.offsetHeight >= i.height ? (oe.style.bottom = i.height - s + "px",
                                    oe.style.top = "initial") : (oe.style.top = t.clientY - n.container.getBoundingClientRect().top + "px",
                                    oe.style.bottom = "initial"),
                                R.classList.add("dplayer-mask-show"),
                                n.events.trigger("contextmenu_show"),
                                R.addEventListener("click", function() {
                                    R.classList.remove("dplayer-mask-show"),
                                        oe.classList.remove("dplayer-menu-show"),
                                        n.events.trigger("contextmenu_hide")
                                })
                        }),
                        this.options.video.quality && this.container.getElementsByClassName("dplayer-quality-list")[0].addEventListener("click", function(e) {
                            e.target.classList.contains("dplayer-quality-item") && n.switchQuality(e.target.dataset.index)
                        }),
                            this.options.screenshot) {
                        var le = this.container.getElementsByClassName("dplayer-camera-icon")[0];
                        le.addEventListener("click", function() {
                            var e = document.createElement("canvas");
                            e.width = n.video.videoWidth,
                                e.height = n.video.videoHeight,
                                e.getContext("2d").drawImage(n.video, 0, 0, e.width, e.height);
                            var t = e.toDataURL();
                            le.href = t,
                                le.download = "DPlayer.png",
                                n.events.trigger("screenshot", t)
                        })
                    }
                    if (this.options.subtitle) {
                        var re = this.container.getElementsByClassName("dplayer-subtitle-icon")[0]
                            , de = re.getElementsByClassName("dplayer-icon-content")[0];
                        this.events.on("subtitle_show", function() {
                            re.dataset.balloon = n.tran("Hide subtitle"),
                                de.style.opacity = "",
                                n.user.set("subtitle", 1)
                        }),
                            this.events.on("subtitle_hide", function() {
                                re.dataset.balloon = n.tran("Show subtitle"),
                                    de.style.opacity = "0.4",
                                    n.user.set("subtitle", 0)
                            }),
                            re.addEventListener("click", function() {
                                n.subtitle.toggle()
                            })
                    }
                    this.initVideo(this.video, this.quality && this.quality.type || this.options.video.type),
                        q++,
                        M.push(this)
                }
                return s(e, [{
                    key: "seek",
                    value: function(e) {
                        e = Math.max(e, 0),
                        this.video.duration && (e = Math.min(e, this.video.duration)),
                            this.video.currentTime < e ? this.notice(this.tran("FF") + " " + (e - this.video.currentTime).toFixed(0) + " " + this.tran("s")) : this.video.currentTime > e && this.notice(this.tran("REW") + " " + (this.video.currentTime - e).toFixed(0) + " " + this.tran("s")),
                            this.video.currentTime = e,
                        this.danmaku && this.danmaku.seek(),
                            this.updateBar("played", e / this.video.duration, "width")
                    }
                }, {
                    key: "play",
                    value: function() {
                        if (this.paused = !1,
                            this.video.paused && (this.bezel.innerHTML = this.icons.get("play"),
                                this.bezel.classList.add("dplayer-bezel-transition")),
                                this.playButton.innerHTML = this.icons.get("pause"),
                                this.video.play(),
                                this.setTime(),
                                this.container.classList.add("dplayer-playing"),
                            this.danmaku && this.danmaku.play(),
                                this.options.mutex)
                            for (var e = 0; e < M.length; e++)
                                this !== M[e] && M[e].pause()
                    }
                }, {
                    key: "pause",
                    value: function() {
                        this.paused = !0,
                            this.container.classList.remove("dplayer-loading"),
                        this.video.paused || (this.bezel.innerHTML = this.icons.get("pause"),
                            this.bezel.classList.add("dplayer-bezel-transition")),
                            this.ended = !1,
                            this.playButton.innerHTML = this.icons.get("play"),
                            this.video.pause(),
                            this.clearTime(),
                            this.container.classList.remove("dplayer-playing"),
                        this.danmaku && this.danmaku.pause()
                    }
                }, {
                    key: "volume",
                    value: function(e, t, n) {
                        if (e = parseFloat(e),
                                !isNaN(e)) {
                            e = e > 0 ? e : 0,
                                e = e < 1 ? e : 1,
                                this.updateBar("volume", e, "width");
                            var i = (100 * e).toFixed(0) + "%";
                            this.container.getElementsByClassName("dplayer-volume-bar-wrap")[0].dataset.balloon = i,
                            t || this.user.set("volume", e),
                            n || this.notice(this.tran("Volume") + " " + (100 * e).toFixed(0) + "%"),
                                this.video.volume = e,
                            this.video.muted && (this.video.muted = !1),
                                this.switchVolumeIcon()
                        }
                        return this.video.volume
                    }
                }, {
                    key: "toggle",
                    value: function() {
                        this.video.paused ? this.play() : this.pause()
                    }
                }, {
                    key: "on",
                    value: function(e, t) {
                        this.events.on(e, t)
                    }
                }, {
                    key: "switchVideo",
                    value: function(e, t) {
                        this.pause(),
                            this.video.poster = e.pic ? e.pic : "",
                            this.video.src = e.url,
                            this.initMSE(this.video, e.type || "auto"),
                        t && (this.container.getElementsByClassName("dplayer-danloading")[0].style.display = "block",
                            this.updateBar("played", 0, "width"),
                            this.updateBar("loaded", 0, "width"),
                            this.container.getElementsByClassName("dplayer-ptime")[0].innerHTML = "00:00",
                            this.container.getElementsByClassName("dplayer-danmaku")[0].innerHTML = "",
                        this.danmaku && this.danmaku.reload({
                            id: t.id,
                            address: t.api,
                            token: t.token,
                            maximum: t.maximum,
                            addition: t.addition,
                            user: t.user
                        }))
                    }
                }, {
                    key: "initMSE",
                    value: function(e, t) {
                        if (this.type = t,
                            "auto" === this.type && (/m3u8(#|\?|$)/i.exec(e.src) ? this.type = "hls" : /.flv(#|\?|$)/i.exec(e.src) ? this.type = "flv" : /.mpd(#|\?|$)/i.exec(e.src) ? this.type = "dash" : this.type = "normal"),
                            "hls" === this.type && Hls && Hls.isSupported()) {
                            var n = new Hls;
                            n.loadSource(e.src),
                                n.attachMedia(e)
                        }
                        if ("flv" === this.type && flvjs && flvjs.isSupported()) {
                            var i = flvjs.createPlayer({
                                type: "flv",
                                url: e.src
                            });
                            i.attachMediaElement(e),
                                i.load()
                        }
                        "dash" === this.type && dashjs && dashjs.MediaPlayer().create().initialize(e, e.src, !1)
                    }
                }, {
                    key: "initVideo",
                    value: function(e, t) {
                        var n = this;
                        this.initMSE(e, t),
                            this.on("durationchange", function() {
                                1 !== e.duration && (n.container.getElementsByClassName("dplayer-dtime")[0].innerHTML = l.default.secondToTime(e.duration))
                            }),
                            this.on("progress", function() {
                                var t = e.buffered.length ? e.buffered.end(e.buffered.length - 1) / e.duration : 0;
                                n.updateBar("loaded", t, "width")
                            }),
                            this.on("error", function() {
                                n.tran && n.notice && n.notice(n.tran("This video fails to load"), -1)
                            }),
                            this.ended = !1,
                            this.on("ended", function() {
                                n.updateBar("played", 1, "width"),
                                    n.loop ? (n.seek(0),
                                        e.play()) : (n.ended = !0,
                                        n.pause()),
                                n.danmaku && (n.danmaku.danIndex = 0)
                            }),
                            this.on("play", function() {
                                n.paused && n.play()
                            }),
                            this.on("pause", function() {
                                n.paused || n.pause()
                            });
                        for (var i = 0; i < this.events.videoEvents.length; i++)
                            !function(t) {
                                e.addEventListener(n.events.videoEvents[t], function() {
                                    n.events.trigger(n.events.videoEvents[t])
                                })
                            }(i);
                        this.volume(this.user.get("volume"), !0, !0),
                        this.options.subtitle && (this.subtitle = new B.default(this.container.getElementsByClassName("dplayer-subtitle")[0],this.video,this.options.subtitle,this.events),
                        this.user.get("subtitle") || this.subtitle.hide())
                    }
                }, {
                    key: "switchQuality",
                    value: function(e) {
                        var t = this;
                        if (this.qualityIndex !== e && !this.switchingQuality) {
                            this.qualityIndex = e,
                                this.switchingQuality = !0,
                                this.quality = this.options.video.quality[e],
                                this.container.getElementsByClassName("dplayer-quality-icon")[0].innerHTML = this.quality.name;
                            var n = this.video.paused;
                            this.video.pause();
                            var i = h.default.video(!1, null, this.options.screenshot, "auto", this.quality.url, this.options.subtitle)
                                , a = (new DOMParser).parseFromString(i, "text/html").body.firstChild
                                , s = this.container.getElementsByClassName("dplayer-video-wrap")[0];
                            s.insertBefore(a, s.getElementsByTagName("div")[0]),
                                this.prevVideo = this.video,
                                this.video = a,
                                this.initVideo(this.video, this.quality.type || this.options.video.type),
                                this.seek(this.prevVideo.currentTime),
                                this.notice(this.tran("Switching to") + " " + this.quality.name + " " + this.tran("quality"), -1),
                                this.events.trigger("quality_start", this.quality),
                                this.on("canplay", function() {
                                    if (t.prevVideo) {
                                        if (t.video.currentTime !== t.prevVideo.currentTime)
                                            return void t.seek(t.prevVideo.currentTime);
                                        s.removeChild(t.prevVideo),
                                            t.video.classList.add("dplayer-video-current"),
                                        n || t.video.play(),
                                            t.prevVideo = null,
                                            t.notice(t.tran("Switched to") + " " + t.quality.name + " " + t.tran("quality")),
                                            t.switchingQuality = !1,
                                            t.events.trigger("quality_end")
                                    }
                                })
                        }
                    }
                }, {
                    key: "mouseHandler",
                    value: function(e, t) {
                        var n = this
                            , i = function(e) {
                            var t = 0
                                , n = 0;
                            do {
                                t += e.offsetTop || 0,
                                    n += e.offsetLeft || 0,
                                    e = e.offsetParent
                            } while (e);return {
                                top: t,
                                left: n
                            }
                        };
                        return function(a) {
                            if (n.video.duration) {
                                var s = a.clientX
                                    , o = i(e).left
                                    , r = s - o;
                                if (!(r < 0 || r > e.offsetWidth)) {
                                    var d = n.video.duration * (r / e.offsetWidth);
                                    switch (t.style.left = r - 20 + "px",
                                        a.type) {
                                        case "mouseenter":
                                            n.thumbnails && n.thumbnails.show();
                                            break;
                                        case "mousemove":
                                            n.thumbnails && n.thumbnails.move(r),
                                                t.innerText = l.default.secondToTime(d),
                                                n.timeTipsDisplay(!0, t);
                                            break;
                                        case "mouseleave":
                                            n.thumbnails && n.thumbnails.hide(),
                                                n.timeTipsDisplay(!1, t)
                                    }
                                }
                            }
                        }
                    }
                }, {
                    key: "timeTipsDisplay",
                    value: function(e, t) {
                        if (e) {
                            if (this.isTimeTipsShow)
                                return;
                            t.classList.remove("hidden"),
                                this.isTimeTipsShow = !0
                        } else {
                            if (!this.isTimeTipsShow)
                                return;
                            t.classList.add("hidden"),
                                this.isTimeTipsShow = !1
                        }
                    }
                }, {
                    key: "initThumbnails",
                    value: function() {
                        var e = this;
                        this.thumbnails = new b.default(this.container.getElementsByClassName("dplayer-bar-preview")[0],this.container.getElementsByClassName("dplayer-bar-wrap")[0].offsetWidth,this.options.video.thumbnails,this.events),
                            this.on("loadedmetadata", function() {
                                e.thumbnails.resize(160, 90)
                            })
                    }
                }, {
                    key: "notice",
                    value: function(e) {
                        var t = this
                            , n = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : 2e3
                            , i = arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : .8
                            , a = this.container.getElementsByClassName("dplayer-notice")[0];
                        a.innerHTML = e,
                            a.style.opacity = i,
                        this.noticeTime && clearTimeout(this.noticeTime),
                            this.events.trigger("notice_show", e),
                            this.noticeTime = setTimeout(function() {
                                a.style.opacity = 0,
                                    t.events.trigger("notice_hide")
                            }, n)
                    }
                }, {
                    key: "resize",
                    value: function() {
                        this.danmaku && this.danmaku.resize(),
                            this.events.trigger("resize")
                    }
                }, {
                    key: "destroy",
                    value: function() {
                        M.splice(M.indexOf(this), 1),
                            this.pause(),
                            clearTimeout(this.hideTime),
                            this.video.src = "",
                            this.container.innerHTML = "",
                            this.events.trigger("destroy");
                        for (var e in this)
                            this.hasOwnProperty(e) && "paused" !== e && delete this[e]
                    }
                }]),
                    e
            }();
            e.exports = S
        }
        , function(e, t) {}
        , function(e, t, n) {
            "use strict";
            var i = n(5);
            e.exports = function(e) {
                /mobile/i.test(window.navigator.userAgent) && (e.autoplay = !1);
                var t = {
                    container: e.element || document.getElementsByClassName("dplayer")[0],
                    autoplay: !1,
                    theme: "#fff",
                    loop: !1,
                    lang: (navigator.language || navigator.browserLanguage).toLowerCase(),
                    screenshot: !1,
                    hotkey: !0,
                    preload: "auto",
                    volume: .7,
                    apiBackend: i,
                    video: {},
                    icons: {
                        play: ["0 0 16 32", "M15.552 15.168q0.448 0.32 0.448 0.832 0 0.448-0.448 0.768l-13.696 8.512q-0.768 0.512-1.312 0.192t-0.544-1.28v-16.448q0-0.96 0.544-1.28t1.312 0.192z"],
                        pause: ["0 0 17 32", "M14.080 4.8q2.88 0 2.88 2.048v18.24q0 2.112-2.88 2.112t-2.88-2.112v-18.24q0-2.048 2.88-2.048zM2.88 4.8q2.88 0 2.88 2.048v18.24q0 2.112-2.88 2.112t-2.88-2.112v-18.24q0-2.048 2.88-2.048z"],
                        "volume-up": ["0 0 21 32", "M13.728 6.272v19.456q0 0.448-0.352 0.8t-0.8 0.32-0.8-0.32l-5.952-5.952h-4.672q-0.48 0-0.8-0.352t-0.352-0.8v-6.848q0-0.48 0.352-0.8t0.8-0.352h4.672l5.952-5.952q0.32-0.32 0.8-0.32t0.8 0.32 0.352 0.8zM20.576 16q0 1.344-0.768 2.528t-2.016 1.664q-0.16 0.096-0.448 0.096-0.448 0-0.8-0.32t-0.32-0.832q0-0.384 0.192-0.64t0.544-0.448 0.608-0.384 0.512-0.64 0.192-1.024-0.192-1.024-0.512-0.64-0.608-0.384-0.544-0.448-0.192-0.64q0-0.48 0.32-0.832t0.8-0.32q0.288 0 0.448 0.096 1.248 0.48 2.016 1.664t0.768 2.528zM25.152 16q0 2.72-1.536 5.056t-4 3.36q-0.256 0.096-0.448 0.096-0.48 0-0.832-0.352t-0.32-0.8q0-0.704 0.672-1.056 1.024-0.512 1.376-0.8 1.312-0.96 2.048-2.4t0.736-3.104-0.736-3.104-2.048-2.4q-0.352-0.288-1.376-0.8-0.672-0.352-0.672-1.056 0-0.448 0.32-0.8t0.8-0.352q0.224 0 0.48 0.096 2.496 1.056 4 3.36t1.536 5.056z"],
                        "volume-down": ["0 0 21 32", "M13.728 6.272v19.456q0 0.448-0.352 0.8t-0.8 0.32-0.8-0.32l-5.952-5.952h-4.672q-0.48 0-0.8-0.352t-0.352-0.8v-6.848q0-0.48 0.352-0.8t0.8-0.352h4.672l5.952-5.952q0.32-0.32 0.8-0.32t0.8 0.32 0.352 0.8zM20.576 16q0 1.344-0.768 2.528t-2.016 1.664q-0.16 0.096-0.448 0.096-0.448 0-0.8-0.32t-0.32-0.832q0-0.384 0.192-0.64t0.544-0.448 0.608-0.384 0.512-0.64 0.192-1.024-0.192-1.024-0.512-0.64-0.608-0.384-0.544-0.448-0.192-0.64q0-0.48 0.32-0.832t0.8-0.32q0.288 0 0.448 0.096 1.248 0.48 2.016 1.664t0.768 2.528z"],
                        "volume-off": ["0 0 21 32", "M13.728 6.272v19.456q0 0.448-0.352 0.8t-0.8 0.32-0.8-0.32l-5.952-5.952h-4.672q-0.48 0-0.8-0.352t-0.352-0.8v-6.848q0-0.48 0.352-0.8t0.8-0.352h4.672l5.952-5.952q0.32-0.32 0.8-0.32t0.8 0.32 0.352 0.8z"],
                        loop: ["0 0 32 32", "M1.882 16.941c0 4.152 3.221 7.529 7.177 7.529v1.882c-4.996 0-9.060-4.222-9.060-9.412s4.064-9.412 9.060-9.412h7.96l-3.098-3.098 1.331-1.331 5.372 5.37-5.37 5.372-1.333-1.333 3.1-3.098h-7.962c-3.957 0-7.177 3.377-7.177 7.529zM22.94 7.529v1.882c3.957 0 7.177 3.377 7.177 7.529s-3.221 7.529-7.177 7.529h-7.962l3.098-3.098-1.331-1.331-5.37 5.37 5.372 5.372 1.331-1.331-3.1-3.1h7.96c4.998 0 9.062-4.222 9.062-9.412s-4.064-9.412-9.060-9.412z"],
                        full: ["0 0 32 33", "M6.667 28h-5.333c-0.8 0-1.333-0.533-1.333-1.333v-5.333c0-0.8 0.533-1.333 1.333-1.333s1.333 0.533 1.333 1.333v4h4c0.8 0 1.333 0.533 1.333 1.333s-0.533 1.333-1.333 1.333zM30.667 28h-5.333c-0.8 0-1.333-0.533-1.333-1.333s0.533-1.333 1.333-1.333h4v-4c0-0.8 0.533-1.333 1.333-1.333s1.333 0.533 1.333 1.333v5.333c0 0.8-0.533 1.333-1.333 1.333zM30.667 12c-0.8 0-1.333-0.533-1.333-1.333v-4h-4c-0.8 0-1.333-0.533-1.333-1.333s0.533-1.333 1.333-1.333h5.333c0.8 0 1.333 0.533 1.333 1.333v5.333c0 0.8-0.533 1.333-1.333 1.333zM1.333 12c-0.8 0-1.333-0.533-1.333-1.333v-5.333c0-0.8 0.533-1.333 1.333-1.333h5.333c0.8 0 1.333 0.533 1.333 1.333s-0.533 1.333-1.333 1.333h-4v4c0 0.8-0.533 1.333-1.333 1.333z"],
                        "full-in": ["0 0 32 33", "M24.965 24.38h-18.132c-1.366 0-2.478-1.113-2.478-2.478v-11.806c0-1.364 1.111-2.478 2.478-2.478h18.132c1.366 0 2.478 1.113 2.478 2.478v11.806c0 1.364-1.11 2.478-2.478 2.478zM6.833 10.097v11.806h18.134l-0.002-11.806h-18.132zM2.478 28.928h5.952c0.684 0 1.238-0.554 1.238-1.239 0-0.684-0.554-1.238-1.238-1.238h-5.952v-5.802c0-0.684-0.554-1.239-1.238-1.239s-1.239 0.556-1.239 1.239v5.802c0 1.365 1.111 2.478 2.478 2.478zM30.761 19.412c-0.684 0-1.238 0.554-1.238 1.238v5.801h-5.951c-0.686 0-1.239 0.554-1.239 1.238 0 0.686 0.554 1.239 1.239 1.239h5.951c1.366 0 2.478-1.111 2.478-2.478v-5.801c0-0.683-0.554-1.238-1.239-1.238zM0 5.55v5.802c0 0.683 0.554 1.238 1.238 1.238s1.238-0.555 1.238-1.238v-5.802h5.952c0.684 0 1.238-0.554 1.238-1.238s-0.554-1.238-1.238-1.238h-5.951c-1.366-0.001-2.478 1.111-2.478 2.476zM32 11.35v-5.801c0-1.365-1.11-2.478-2.478-2.478h-5.951c-0.686 0-1.239 0.554-1.239 1.238s0.554 1.238 1.239 1.238h5.951v5.801c0 0.683 0.554 1.237 1.238 1.237 0.686 0.002 1.239-0.553 1.239-1.236z"],
                        setting: ["0 0 32 28", "M28.633 17.104c0.035 0.21 0.026 0.463-0.026 0.76s-0.14 0.598-0.262 0.904c-0.122 0.306-0.271 0.581-0.445 0.825s-0.367 0.419-0.576 0.524c-0.209 0.105-0.393 0.157-0.55 0.157s-0.332-0.035-0.524-0.105c-0.175-0.052-0.393-0.1-0.655-0.144s-0.528-0.052-0.799-0.026c-0.271 0.026-0.541 0.083-0.812 0.17s-0.502 0.236-0.694 0.445c-0.419 0.437-0.664 0.934-0.734 1.493s0.009 1.092 0.236 1.598c0.175 0.349 0.148 0.699-0.079 1.048-0.105 0.14-0.271 0.284-0.498 0.432s-0.476 0.284-0.747 0.406-0.555 0.218-0.851 0.288c-0.297 0.070-0.559 0.105-0.786 0.105-0.157 0-0.306-0.061-0.445-0.183s-0.236-0.253-0.288-0.393h-0.026c-0.192-0.541-0.52-1.009-0.982-1.402s-1-0.589-1.611-0.589c-0.594 0-1.131 0.197-1.611 0.589s-0.816 0.851-1.009 1.375c-0.087 0.21-0.218 0.362-0.393 0.458s-0.367 0.144-0.576 0.144c-0.244 0-0.52-0.044-0.825-0.131s-0.611-0.197-0.917-0.327c-0.306-0.131-0.581-0.284-0.825-0.458s-0.428-0.349-0.55-0.524c-0.087-0.122-0.135-0.266-0.144-0.432s0.057-0.397 0.197-0.694c0.192-0.402 0.266-0.86 0.223-1.375s-0.266-0.991-0.668-1.428c-0.244-0.262-0.541-0.432-0.891-0.511s-0.681-0.109-0.995-0.092c-0.367 0.017-0.742 0.087-1.127 0.21-0.244 0.070-0.489 0.052-0.734-0.052-0.192-0.070-0.371-0.231-0.537-0.485s-0.314-0.533-0.445-0.838c-0.131-0.306-0.231-0.62-0.301-0.943s-0.087-0.59-0.052-0.799c0.052-0.384 0.227-0.629 0.524-0.734 0.524-0.21 0.995-0.555 1.415-1.035s0.629-1.017 0.629-1.611c0-0.611-0.21-1.144-0.629-1.598s-0.891-0.786-1.415-0.996c-0.157-0.052-0.288-0.179-0.393-0.38s-0.157-0.406-0.157-0.616c0-0.227 0.035-0.48 0.105-0.76s0.162-0.55 0.275-0.812 0.244-0.502 0.393-0.72c0.148-0.218 0.31-0.38 0.485-0.485 0.14-0.087 0.275-0.122 0.406-0.105s0.275 0.052 0.432 0.105c0.524 0.21 1.070 0.275 1.637 0.197s1.070-0.327 1.506-0.747c0.21-0.209 0.362-0.467 0.458-0.773s0.157-0.607 0.183-0.904c0.026-0.297 0.026-0.568 0-0.812s-0.048-0.419-0.065-0.524c-0.035-0.105-0.066-0.227-0.092-0.367s-0.013-0.262 0.039-0.367c0.105-0.244 0.293-0.458 0.563-0.642s0.563-0.336 0.878-0.458c0.314-0.122 0.62-0.214 0.917-0.275s0.533-0.092 0.707-0.092c0.227 0 0.406 0.074 0.537 0.223s0.223 0.301 0.275 0.458c0.192 0.471 0.507 0.886 0.943 1.244s0.952 0.537 1.546 0.537c0.611 0 1.153-0.17 1.624-0.511s0.803-0.773 0.996-1.297c0.070-0.14 0.179-0.284 0.327-0.432s0.301-0.223 0.458-0.223c0.244 0 0.511 0.035 0.799 0.105s0.572 0.166 0.851 0.288c0.279 0.122 0.537 0.279 0.773 0.472s0.423 0.402 0.563 0.629c0.087 0.14 0.113 0.293 0.079 0.458s-0.070 0.284-0.105 0.354c-0.227 0.506-0.297 1.039-0.21 1.598s0.341 1.048 0.76 1.467c0.419 0.419 0.934 0.651 1.546 0.694s1.179-0.057 1.703-0.301c0.14-0.087 0.31-0.122 0.511-0.105s0.371 0.096 0.511 0.236c0.262 0.244 0.493 0.616 0.694 1.113s0.336 1 0.406 1.506c0.035 0.297-0.013 0.528-0.144 0.694s-0.266 0.275-0.406 0.327c-0.542 0.192-1.004 0.528-1.388 1.009s-0.576 1.026-0.576 1.637c0 0.594 0.162 1.113 0.485 1.559s0.747 0.764 1.27 0.956c0.122 0.070 0.227 0.14 0.314 0.21 0.192 0.157 0.323 0.358 0.393 0.602v0zM16.451 19.462c0.786 0 1.528-0.149 2.227-0.445s1.305-0.707 1.821-1.231c0.515-0.524 0.921-1.131 1.218-1.821s0.445-1.428 0.445-2.214c0-0.786-0.148-1.524-0.445-2.214s-0.703-1.292-1.218-1.808c-0.515-0.515-1.122-0.921-1.821-1.218s-1.441-0.445-2.227-0.445c-0.786 0-1.524 0.148-2.214 0.445s-1.292 0.703-1.808 1.218c-0.515 0.515-0.921 1.118-1.218 1.808s-0.445 1.428-0.445 2.214c0 0.786 0.149 1.524 0.445 2.214s0.703 1.297 1.218 1.821c0.515 0.524 1.118 0.934 1.808 1.231s1.428 0.445 2.214 0.445v0z"],
                        right: ["0 0 32 32", "M22 16l-10.105-10.6-1.895 1.987 8.211 8.613-8.211 8.612 1.895 1.988 8.211-8.613z"],
                        comment: ["0 0 32 32", "M27.128 0.38h-22.553c-2.336 0-4.229 1.825-4.229 4.076v16.273c0 2.251 1.893 4.076 4.229 4.076h4.229v-2.685h8.403l-8.784 8.072 1.566 1.44 7.429-6.827h9.71c2.335 0 4.229-1.825 4.229-4.076v-16.273c0-2.252-1.894-4.076-4.229-4.076zM28.538 19.403c0 1.5-1.262 2.717-2.819 2.717h-8.36l-0.076-0.070-0.076 0.070h-11.223c-1.557 0-2.819-1.217-2.819-2.717v-13.589c0-1.501 1.262-2.718 2.819-2.718h19.734c1.557 0 2.819-0.141 2.819 1.359v14.947zM9.206 10.557c-1.222 0-2.215 0.911-2.215 2.036s0.992 2.035 2.215 2.035c1.224 0 2.216-0.911 2.216-2.035s-0.992-2.036-2.216-2.036zM22.496 10.557c-1.224 0-2.215 0.911-2.215 2.036s0.991 2.035 2.215 2.035c1.224 0 2.215-0.911 2.215-2.035s-0.991-2.036-2.215-2.036zM15.852 10.557c-1.224 0-2.215 0.911-2.215 2.036s0.991 2.035 2.215 2.035c1.222 0 2.215-0.911 2.215-2.035s-0.992-2.036-2.215-2.036z"],
                        "comment-off": ["0 0 32 32", "M27.090 0.131h-22.731c-2.354 0-4.262 1.839-4.262 4.109v16.401c0 2.269 1.908 4.109 4.262 4.109h4.262v-2.706h8.469l-8.853 8.135 1.579 1.451 7.487-6.88h9.787c2.353 0 4.262-1.84 4.262-4.109v-16.401c0-2.27-1.909-4.109-4.262-4.109v0zM28.511 19.304c0 1.512-1.272 2.738-2.841 2.738h-8.425l-0.076-0.070-0.076 0.070h-11.311c-1.569 0-2.841-1.226-2.841-2.738v-13.696c0-1.513 1.272-2.739 2.841-2.739h19.889c1.569 0 2.841-0.142 2.841 1.37v15.064z"],
                        send: ["0 0 32 32", "M13.725 30l3.9-5.325-3.9-1.125v6.45zM0 17.5l11.050 3.35 13.6-11.55-10.55 12.425 11.8 3.65 6.1-23.375-32 15.5z"],
                        pallette: ["0 0 32 32", "M19.357 2.88c1.749 0 3.366 0.316 4.851 0.946 1.485 0.632 2.768 1.474 3.845 2.533s1.922 2.279 2.532 3.661c0.611 1.383 0.915 2.829 0.915 4.334 0 1.425-0.304 2.847-0.915 4.271-0.611 1.425-1.587 2.767-2.928 4.028-0.855 0.813-1.811 1.607-2.869 2.38s-2.136 1.465-3.233 2.075c-1.099 0.61-2.198 1.098-3.296 1.465-1.098 0.366-2.115 0.549-3.051 0.549-1.343 0-2.441-0.438-3.296-1.311-0.854-0.876-1.281-2.41-1.281-4.608 0-0.366 0.020-0.773 0.060-1.221s0.062-0.895 0.062-1.343c0-0.773-0.183-1.353-0.55-1.738-0.366-0.387-0.793-0.58-1.281-0.58-0.652 0-1.21 0.295-1.678 0.886s-0.926 1.23-1.373 1.921c-0.447 0.693-0.905 1.334-1.372 1.923s-1.028 0.886-1.679 0.886c-0.529 0-1.048-0.427-1.556-1.282s-0.763-2.259-0.763-4.212c0-2.197 0.529-4.241 1.587-6.133s2.462-3.529 4.21-4.912c1.75-1.383 3.762-2.471 6.041-3.264 2.277-0.796 4.617-1.212 7.018-1.253zM7.334 15.817c0.569 0 1.047-0.204 1.434-0.611s0.579-0.875 0.579-1.404c0-0.569-0.193-1.047-0.579-1.434s-0.864-0.579-1.434-0.579c-0.529 0-0.987 0.193-1.373 0.579s-0.58 0.864-0.58 1.434c0 0.53 0.194 0.998 0.58 1.404 0.388 0.407 0.845 0.611 1.373 0.611zM12.216 11.79c0.691 0 1.292-0.254 1.8-0.763s0.762-1.107 0.762-1.8c0-0.732-0.255-1.343-0.762-1.831-0.509-0.489-1.109-0.732-1.8-0.732-0.732 0-1.342 0.244-1.831 0.732-0.488 0.488-0.732 1.098-0.732 1.831 0 0.693 0.244 1.292 0.732 1.8s1.099 0.763 1.831 0.763zM16.366 25.947c0.692 0 1.282-0.214 1.77-0.64s0.732-0.987 0.732-1.678-0.244-1.261-0.732-1.709c-0.489-0.448-1.078-0.671-1.77-0.671-0.65 0-1.21 0.223-1.678 0.671s-0.702 1.018-0.702 1.709c0 0.692 0.234 1.25 0.702 1.678s1.027 0.64 1.678 0.64zM19.113 9.592c0.651 0 1.129-0.203 1.433-0.611 0.305-0.406 0.459-0.874 0.459-1.404 0-0.488-0.154-0.947-0.459-1.373-0.304-0.427-0.782-0.641-1.433-0.641-0.529 0-1.008 0.193-1.434 0.58s-0.64 0.865-0.64 1.434c0 0.571 0.213 1.049 0.64 1.434 0.427 0.389 0.905 0.581 1.434 0.581zM24.848 12.826c0.57 0 1.067-0.213 1.495-0.64 0.427-0.427 0.64-0.947 0.64-1.556 0-0.57-0.214-1.068-0.64-1.495-0.428-0.427-0.927-0.64-1.495-0.64-0.611 0-1.129 0.213-1.555 0.64-0.428 0.427-0.642 0.926-0.642 1.495 0 0.611 0.213 1.129 0.642 1.556s0.947 0.64 1.555 0.64z"],
                        camera: ["0 0 32 32", "M16 23c-3.309 0-6-2.691-6-6s2.691-6 6-6 6 2.691 6 6-2.691 6-6 6zM16 13c-2.206 0-4 1.794-4 4s1.794 4 4 4c2.206 0 4-1.794 4-4s-1.794-4-4-4zM27 28h-22c-1.654 0-3-1.346-3-3v-16c0-1.654 1.346-3 3-3h3c0.552 0 1 0.448 1 1s-0.448 1-1 1h-3c-0.551 0-1 0.449-1 1v16c0 0.552 0.449 1 1 1h22c0.552 0 1-0.448 1-1v-16c0-0.551-0.448-1-1-1h-11c-0.552 0-1-0.448-1-1s0.448-1 1-1h11c1.654 0 3 1.346 3 3v16c0 1.654-1.346 3-3 3zM24 10.5c0 0.828 0.672 1.5 1.5 1.5s1.5-0.672 1.5-1.5c0-0.828-0.672-1.5-1.5-1.5s-1.5 0.672-1.5 1.5zM15 4c0 0.552-0.448 1-1 1h-4c-0.552 0-1-0.448-1-1v0c0-0.552 0.448-1 1-1h4c0.552 0 1 0.448 1 1v0z"],
                        subtitle: ["0 0 32 32", "M26.667 5.333h-21.333c-0 0-0.001 0-0.001 0-1.472 0-2.666 1.194-2.666 2.666 0 0 0 0.001 0 0.001v-0 16c0 0 0 0.001 0 0.001 0 1.472 1.194 2.666 2.666 2.666 0 0 0.001 0 0.001 0h21.333c0 0 0.001 0 0.001 0 1.472 0 2.666-1.194 2.666-2.666 0-0 0-0.001 0-0.001v0-16c0-0 0-0.001 0-0.001 0-1.472-1.194-2.666-2.666-2.666-0 0-0.001 0-0.001 0h0zM5.333 16h5.333v2.667h-5.333v-2.667zM18.667 24h-13.333v-2.667h13.333v2.667zM26.667 24h-5.333v-2.667h5.333v2.667zM26.667 18.667h-13.333v-2.667h13.333v2.667z"]
                    },
                    iconsColor: "#ffffff",
                    contextmenu: [],
                    jumpUrl: '',
                    mutex: !0
                };
                for (var n in t)
                    t.hasOwnProperty(n) && !e.hasOwnProperty(n) && (e[n] = t[n]);
                return e.video && !e.video.type && (e.video.type = "auto"),
                e.danmaku && !e.danmaku.user && (e.danmaku.user = "DIYgod"),
                e.subtitle && (!e.subtitle.type && (e.subtitle.type = "webvtt"),
                !e.subtitle.fontSize && (e.subtitle.fontSize = "20px"),
                !e.subtitle.bottom && (e.subtitle.bottom = "40px"),
                !e.subtitle.color && (e.subtitle.color = "#fff")),
                e.video.quality && (e.video.url = [e.video.quality[e.video.defaultQuality].url]),
                e.lang && (e.lang = e.lang.toLowerCase()),
                e.icons && (e.icons = Object.assign({}, t.icons, e.icons)),
                e.jumpUrl && (e.jumpUrl = e.jumpUrl.toLowerCase()),
                    // e.contextmenu = e.contextmenu.concat([{
                    //     text: "About author",
                    //     link: "https://www.anotherhome.net/"
                    // }, {
                    //     text: "About DPlayer",
                    //     link: "https://github.com/MoePlayer/DPlayer"
                    // }, {
                    //     text: "DPlayer feedback",
                    //     link: "https://github.com/DIYgod/DPlayer/issues"
                    // }, {
                    //     text: "DPlayer 1.16.0 d8cfa12",
                    //     link: "https://github.com/MoePlayer/DPlayer/releases"
                    // }]),
                    e
            }
        }
        , function(e, t, n) {
            "use strict";
            var i = function(e, t, n, i, a) {
                var s = new XMLHttpRequest;
                s.onreadystatechange = function() {
                    if (4 === s.readyState) {
                        if (s.status >= 200 && s.status < 300 || 304 === s.status) {
                            var e = JSON.parse(s.responseText);
                            return 1 !== e.code ? i(s, e) : n(s, e)
                        }
                        a(s)
                    }
                }
                    ,
                    s.open(null !== t ? "POST" : "GET", e, !0),
                    s.send(null !== t ? JSON.stringify(t) : null)
            };
            e.exports = {
                send: function(e, t, n) {
                    i(e, t, function(e, t) {
                        console.log("Post danmaku: ", t),
                        n && n()
                    }, function(e, t) {
                        alert(t.msg)
                    }, function(e) {
                        console.log("Request was unsuccessful: " + e.status)
                    })
                },
                read: function(e, t) {
                    i(e, null, function(e, n) {
                        t(null, n.danmaku)
                    }, function(e, n) {
                        t({
                            status: e.status,
                            response: n
                        })
                    }, function(e) {
                        t({
                            status: e.status,
                            response: null
                        })
                    })
                }
            }
        }
        , function(e, t, n) {
            "use strict";
            e.exports = function(e) {
                var t = this;
                this.lang = e,
                    this.tran = function(e) {
                        return i[t.lang] && i[t.lang][e] ? i[t.lang][e] : e
                    }
            }
            ;
            var i = {
                "zh-cn": {
                    "Danmaku is loading": "\u5f39\u5e55\u52a0\u8f7d\u4e2d",
                    Top: "\u9876\u90e8",
                    Bottom: "\u5e95\u90e8",
                    Rolling: "\u6eda\u52a8",
                    "Input danmaku, hit Enter": "\u8f93\u5165\u5f39\u5e55\uff0c\u56de\u8f66\u53d1\u9001",
                    "About author": "\u5173\u4e8e\u4f5c\u8005",
                    "DPlayer feedback": "\u64ad\u653e\u5668\u610f\u89c1\u53cd\u9988",
                    "About DPlayer": "\u5173\u4e8e DPlayer \u64ad\u653e\u5668",
                    Loop: "\u6d17\u8111\u5faa\u73af",
                    Speed: "\u901f\u5ea6",
                    "Opacity for danmaku": "\u5f39\u5e55\u900f\u660e\u5ea6",
                    Normal: "\u6b63\u5e38",
                    "Please input danmaku content!": "\u8981\u8f93\u5165\u5f39\u5e55\u5185\u5bb9\u554a\u5582\uff01",
                    "Set danmaku color": "\u8bbe\u7f6e\u5f39\u5e55\u989c\u8272",
                    "Set danmaku type": "\u8bbe\u7f6e\u5f39\u5e55\u7c7b\u578b",
                    "Show danmaku": "\u663e\u793a\u5f39\u5e55",
                    "This video fails to load": "\u89c6\u9891\u52a0\u8f7d\u5931\u8d25",
                    "Switching to": "\u6b63\u5728\u5207\u6362\u81f3",
                    "Switched to": "\u5df2\u7ecf\u5207\u6362\u81f3",
                    quality: "\u753b\u8d28",
                    FF: "\u5feb\u8fdb",
                    REW: "\u5feb\u9000",
                    "Unlimited danmaku": "\u6d77\u91cf\u5f39\u5e55",
                    "Send danmaku": "\u53d1\u9001\u5f39\u5e55",
                    Setting: "\u8bbe\u7f6e",
                    "Full screen": "\u5168\u5c4f",
                    "Web full screen": "\u9875\u9762\u5168\u5c4f",
                    Send: "\u53d1\u9001",
                    Screenshot: "\u622a\u56fe",
                    s: "\u79d2",
                    "Show subtitle": "\u663e\u793a\u5b57\u5e55",
                    "Hide subtitle": "\u9690\u85cf\u5b57\u5e55",
                    Volume: "\u97f3\u91cf"
                },
                "zh-tw": {
                    "Danmaku is loading": "\u5f48\u5e55\u52a0\u8f09\u4e2d",
                    Top: "\u9802\u90e8",
                    Bottom: "\u5e95\u90e8",
                    Rolling: "\u6efe\u52d5",
                    "Input danmaku, hit Enter": "\u8f38\u5165\u5f48\u5e55\uff0cEnter \u767c\u9001",
                    "About author": "\u95dc\u65bc\u4f5c\u8005",
                    "DPlayer feedback": "\u64ad\u653e\u5668\u610f\u898b\u53cd\u994b",
                    "About DPlayer": "\u95dc\u65bc DPlayer \u64ad\u653e\u5668",
                    Loop: "\u5faa\u74b0\u64ad\u653e",
                    Speed: "\u901f\u5ea6",
                    "Opacity for danmaku": "\u5f48\u5e55\u900f\u660e\u5ea6",
                    Normal: "\u6b63\u5e38",
                    "Please input danmaku content!": "\u8acb\u8f38\u5165\u5f48\u5e55\u5185\u5bb9\u554a\uff01",
                    "Set danmaku color": "\u8a2d\u7f6e\u5f48\u5e55\u984f\u8272",
                    "Set danmaku type": "\u8a2d\u7f6e\u5f48\u5e55\u985e\u578b",
                    "Show danmaku": "\u986f\u793a\u5f48\u5e55",
                    "This video fails to load": "\u8996\u983b\u52a0\u8f09\u5931\u6557",
                    "Switching to": "\u6b63\u5728\u5207\u63db\u81f3",
                    "Switched to": "\u5df2\u7d93\u5207\u63db\u81f3",
                    quality: "\u756b\u8cea",
                    FF: "\u5feb\u9032",
                    REW: "\u5feb\u9000",
                    "Unlimited danmaku": "\u6d77\u91cf\u5f48\u5e55",
                    "Send danmaku": "\u767c\u9001\u5f48\u5e55",
                    Setting: "\u8a2d\u7f6e",
                    "Full screen": "\u5168\u5c4f",
                    "Web full screen": "\u9801\u9762\u5168\u5c4f",
                    Send: "\u767c\u9001",
                    Screenshot: "\u622a\u5716",
                    s: "\u79d2",
                    "Show subtitle": "\u986f\u793a\u5b57\u5e55",
                    "Hide subtitle": "\u96b1\u85cf\u5b57\u5e55",
                    Volume: "\u97f3\u91cf"
                }
            }
        }
        , function(e, t, n) {
            "use strict";
            var i = {
                main: function(e, t, n, a) {
                    return '<div class="dplayer-mask"></div><div class="dplayer-video-wrap">' + i.video(!0, e.video.pic, e.screenshot, e.preload, e.video.url, e.subtitle) + (e.logo ? '<div class="dplayer-logo"><img src="' + e.logo + '"></div>' : "") + '<div class="dplayer-danmaku" style="' + (e.danmaku ? i.danmakumargin(e.danmaku.margin) : "") + '"><div class="dplayer-danmaku-item dplayer-danmaku-item--demo"></div></div><div class="dplayer-subtitle"></div><div class="dplayer-bezel"><span class="dplayer-bezel-icon"></span>' + (e.danmaku ? '<span class="dplayer-danloading">' + n("Danmaku is loading") + "</span>" : "") + '<span class="diplayer-loading-icon"><svg height="100%" version="1.1" viewBox="0 0 22 22" width="100%"><svg x="7" y="1"><circle class="diplayer-loading-dot diplayer-loading-dot-0" cx="4" cy="4" r="2"></circle></svg><svg x="11" y="3"><circle class="diplayer-loading-dot diplayer-loading-dot-1" cx="4" cy="4" r="2"></circle></svg><svg x="13" y="7"><circle class="diplayer-loading-dot diplayer-loading-dot-2" cx="4" cy="4" r="2"></circle></svg><svg x="11" y="11"><circle class="diplayer-loading-dot diplayer-loading-dot-3" cx="4" cy="4" r="2"></circle></svg><svg x="7" y="13"><circle class="diplayer-loading-dot diplayer-loading-dot-4" cx="4" cy="4" r="2"></circle></svg><svg x="3" y="11"><circle class="diplayer-loading-dot diplayer-loading-dot-5" cx="4" cy="4" r="2"></circle></svg><svg x="1" y="7"><circle class="diplayer-loading-dot diplayer-loading-dot-6" cx="4" cy="4" r="2"></circle></svg><svg x="3" y="3"><circle class="diplayer-loading-dot diplayer-loading-dot-7" cx="4" cy="4" r="2"></circle></svg></svg></span></div></div><div class="dplayer-controller-mask"></div><div class="dplayer-controller"><div class="dplayer-icons dplayer-comment-box"><button class="dplayer-icon dplayer-comment-setting-icon" data-balloon="' + n("Setting") + '" data-balloon-pos="up"><span class="dplayer-icon-content">' + a.get("pallette") + '</span></button><div class="dplayer-comment-setting-box"><div class="dplayer-comment-setting-color"><div class="dplayer-comment-setting-title">' + n("Set danmaku color") + '</div><label><input type="radio" name="dplayer-danmaku-color-' + t + '" value="#fff" checked><span style="background: #fff;"></span></label><label><input type="radio" name="dplayer-danmaku-color-' + t + '" value="#e54256"><span style="background: #e54256"></span></label><label><input type="radio" name="dplayer-danmaku-color-' + t + '" value="#ffe133"><span style="background: #ffe133"></span></label><label><input type="radio" name="dplayer-danmaku-color-' + t + '" value="#64DD17"><span style="background: #64DD17"></span></label><label><input type="radio" name="dplayer-danmaku-color-' + t + '" value="#39ccff"><span style="background: #39ccff"></span></label><label><input type="radio" name="dplayer-danmaku-color-' + t + '" value="#D500F9"><span style="background: #D500F9"></span></label></div><div class="dplayer-comment-setting-type"><div class="dplayer-comment-setting-title">' + n("Set danmaku type") + '</div><label><input type="radio" name="dplayer-danmaku-type-' + t + '" value="top"><span>' + n("Top") + '</span></label><label><input type="radio" name="dplayer-danmaku-type-' + t + '" value="right" checked><span>' + n("Rolling") + '</span></label><label><input type="radio" name="dplayer-danmaku-type-' + t + '" value="bottom"><span>' + n("Bottom") + '</span></label></div></div><input class="dplayer-comment-input" type="text" placeholder="' + n("Input danmaku, hit Enter") + '" maxlength="30"><button class="dplayer-icon dplayer-send-icon" data-balloon="' + n("Send") + '" data-balloon-pos="up"><span class="dplayer-icon-content">' + a.get("send") + '</span></button></div><div class="dplayer-icons dplayer-icons-left"><button class="dplayer-icon dplayer-play-icon"><span class="dplayer-icon-content">' + a.get("play") + '</span></button><div class="dplayer-volume"><button class="dplayer-icon dplayer-volume-icon"><span class="dplayer-icon-content">' + a.get("volume-down") + '</span></button><div class="dplayer-volume-bar-wrap" data-balloon-pos="up"><div class="dplayer-volume-bar"><div class="dplayer-volume-bar-inner" style="background: ' + e.theme + ';"><span class="dplayer-thumb" style="background: ' + e.theme + '"></span></div></div></div></div><span class="dplayer-time"><span class="dplayer-ptime">0:00</span> / <span class="dplayer-dtime">0:00</span></span></div><div class="dplayer-icons dplayer-icons-right">' + (e.video.quality ? '<div class="dplayer-quality"><button class="dplayer-icon dplayer-quality-icon">' + e.video.quality[e.video.defaultQuality].name + '</button><div class="dplayer-quality-mask">' + i.qualityList(e.video.quality) + "</div></div>" : "") + (e.screenshot ? '<a href="#" class="dplayer-icon dplayer-camera-icon" data-balloon="' + n("Screenshot") + '" data-balloon-pos="up"><span class="dplayer-icon-content">' + a.get("camera") + "</span></a>" : "") + '<div class="dplayer-comment"><button class="dplayer-icon dplayer-comment-icon" data-balloon="' + n("Send danmaku") + '" data-balloon-pos="up"><span class="dplayer-icon-content">' + a.get("comment") + "</span></button></div>" + (e.subtitle ? '<div class="dplayer-subtitle-btn"><button class="dplayer-icon dplayer-subtitle-icon" data-balloon="' + n("Hide subtitle") + '" data-balloon-pos="up"><span class="dplayer-icon-content">' + a.get("subtitle") + "</span></button></div>" : "") + '<div class="dplayer-setting"><button class="dplayer-icon dplayer-setting-icon" data-balloon="' + n("Setting") + '" data-balloon-pos="up"><span class="dplayer-icon-content">' + a.get("setting") + '</span></button><div class="dplayer-setting-box"></div></div><div class="dplayer-full"><button class="dplayer-icon dplayer-full-in-icon" data-balloon="' + n("Web full screen") + '" data-balloon-pos="up"><span class="dplayer-icon-content">' + a.get("full-in") + '</span></button><button class="dplayer-icon dplayer-full-icon" data-balloon="' + n("Full screen") + '" data-balloon-pos="up"><span class="dplayer-icon-content">' + a.get("full") + '</span></button></div></div><div class="dplayer-bar-wrap"><div class="dplayer-bar-time hidden">00:00</div><div class="dplayer-bar-preview"></div><div class="dplayer-bar"><div class="dplayer-loaded" style="width: 0;"></div><div class="dplayer-played" style="width: 0; background: ' + e.theme + '"><span class="dplayer-thumb" style="background: ' + e.theme + '"></span></div></div></div></div>' + i.contextmenuList(e.contextmenu, n) + '<div class="dplayer-notice"></div>' + '<a href="' + e.jumpUrl + '" target="_blank" class="enter-btn">进入直播<i class="fa fa-angle-right"></i></a>'
                },
                danmakumargin: function(e) {
                    var t = "";
                    if (e)
                        for (var n in e)
                            t += n + ":" + e[n] + ";";
                    return t
                },
                contextmenuList: function(e, t) {
                    for (var n = '<div class="dplayer-menu">', i = 0; i < e.length; i++)
                        n += '<div class="dplayer-menu-item"><a target="_blank" href="' + e[i].link + '">' + t(e[i].text) + "</a></div>";
                    return n += "</div>"
                },
                qualityList: function(e) {
                    for (var t = '<div class="dplayer-quality-list">', n = 0; n < e.length; n++)
                        t += '<div class="dplayer-quality-item" data-index="' + n + '">' + e[n].name + "</div>";
                    return t += "</div>"
                },
                video: function(e, t, n, i, a, s) {
                    var o = s && "webvtt" === s.type;
                    return '<video class="dplayer-video ' + (e ? 'dplayer-video-current"' : "") + '" ' + (t ? 'poster="' + t + '"' : "") + " webkit-playsinline playsinline " + (n || o ? 'crossorigin="anonymous"' : "") + " " + (i ? 'preload="' + i + '"' : "") + ' src="' + a + '">' + (o ? '<track kind="metadata" default src="' + s.url + '"></track>' : "") + "</video>"
                },
                setting: function(e, t) {
                    return {
                        original: '<div class="dplayer-setting-item dplayer-setting-speed"><span class="dplayer-label">' + e("Speed") + '</span><div class="dplayer-toggle">' + t.get("right") + '</div></div><div class="dplayer-setting-item dplayer-setting-loop"><span class="dplayer-label">' + e("Loop") + '</span><div class="dplayer-toggle"><input class="dplayer-toggle-setting-input" type="checkbox" name="dplayer-toggle"><label for="dplayer-toggle"></label></div></div><div class="dplayer-setting-item dplayer-setting-showdan"><span class="dplayer-label">' + e("Show danmaku") + '</span><div class="dplayer-toggle"><input class="dplayer-showdan-setting-input" type="checkbox" name="dplayer-toggle-dan"><label for="dplayer-toggle-dan"></label></div></div><div class="dplayer-setting-item dplayer-setting-danunlimit"><span class="dplayer-label">' + e("Unlimited danmaku") + '</span><div class="dplayer-toggle"><input class="dplayer-danunlimit-setting-input" type="checkbox" name="dplayer-toggle-danunlimit"><label for="dplayer-toggle-danunlimit"></label></div></div><div class="dplayer-setting-item dplayer-setting-danmaku"><span class="dplayer-label">' + e("Opacity for danmaku") + '</span><div class="dplayer-danmaku-bar-wrap"><div class="dplayer-danmaku-bar"><div class="dplayer-danmaku-bar-inner"><span class="dplayer-thumb"></span></div></div></div></div>',
                        speed: '<div class="dplayer-setting-speed-item" data-speed="0.5"><span class="dplayer-label">0.5</span></div><div class="dplayer-setting-speed-item" data-speed="0.75"><span class="dplayer-label">0.75</span></div><div class="dplayer-setting-speed-item" data-speed="1"><span class="dplayer-label">' + e("Normal") + '</span></div><div class="dplayer-setting-speed-item" data-speed="1.25"><span class="dplayer-label">1.25</span></div><div class="dplayer-setting-speed-item" data-speed="1.5"><span class="dplayer-label">1.5</span></div><div class="dplayer-setting-speed-item" data-speed="2"><span class="dplayer-label">2</span></div>'
                    }
                }
            };
            e.exports = i
        }
        , function(e, t, n) {
            "use strict";
            function i(e, t) {
                if (!(e instanceof t))
                    throw new TypeError("Cannot call a class as a function")
            }
            var a = function() {
                function e(e, t) {
                    for (var n = 0; n < t.length; n++) {
                        var i = t[n];
                        i.enumerable = i.enumerable || !1,
                            i.configurable = !0,
                        "value"in i && (i.writable = !0),
                            Object.defineProperty(e, i.key, i)
                    }
                }
                return function(t, n, i) {
                    return n && e(t.prototype, n),
                    i && e(t, i),
                        t
                }
            }()
                , s = function() {
                function e(t) {
                    i(this, e),
                        this.icons = t.icons,
                        this.iconColor = t.iconsColor
                }
                return a(e, [{
                    key: "get",
                    value: function(e) {
                        return '<svg xmlns="http://www.w3.org/2000/svg" width="' + (this.icons[e][2] || "100%") + '" height="' + (this.icons[e][2] || "100%") + '" version="1.1" viewBox="' + this.icons[e][0] + '"><path class="dplayer-fill" style="fill:' + this.iconColor + '" d="' + this.icons[e][1] + '" id="dplayer-' + e + '"></path></svg>'
                    }
                }]),
                    e
            }();
            e.exports = s
        }
        , function(e, t, n) {
            "use strict";
            function i(e, t) {
                if (!(e instanceof t))
                    throw new TypeError("Cannot call a class as a function")
            }
            var a = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function(e) {
                    return typeof e
                }
                : function(e) {
                    return e && "function" == typeof Symbol && e.constructor === Symbol && e !== Symbol.prototype ? "symbol" : typeof e
                }
                , s = function() {
                function e(e, t) {
                    for (var n = 0; n < t.length; n++) {
                        var i = t[n];
                        i.enumerable = i.enumerable || !1,
                            i.configurable = !0,
                        "value"in i && (i.writable = !0),
                            Object.defineProperty(e, i.key, i)
                    }
                }
                return function(t, n, i) {
                    return n && e(t.prototype, n),
                    i && e(t, i),
                        t
                }
            }()
                , o = function() {
                function e(t) {
                    i(this, e),
                        this.options = t,
                        this.container = this.options.container,
                        this.danTunnel = {
                            right: {},
                            top: {},
                            bottom: {}
                        },
                        this.danIndex = 0,
                        this.dan = [],
                        this.showing = !0,
                        this._opacity = this.options.opacity,
                        this.events = this.options.events,
                        this.unlimited = this.options.unlimited,
                        this._measure(""),
                        this.load()
                }
                return s(e, [{
                    key: "load",
                    value: function() {
                        var e = this
                            , t = void 0;
                        t = this.options.api.maximum ? this.options.api.address + "?id=" + this.options.api.id + "&max=" + this.options.api.maximum : this.options.api.address + "?id=" + this.options.api.id;
                        var n = (this.options.api.addition || []).slice(0);
                        n.push(t),
                        this.events && this.events.trigger("danmaku_load_start", n),
                            this._readAllEndpoints(n, function(t) {
                                e.dan = [].concat.apply([], t).sort(function(e, t) {
                                    return e.time - t.time
                                }),
                                    window.requestAnimationFrame(function() {
                                        e.frame()
                                    }),
                                    e.options.callback(),
                                e.events && e.events.trigger("danmaku_load_end")
                            })
                    }
                }, {
                    key: "reload",
                    value: function(e) {
                        this.options.api = e,
                            this.dan = [],
                            this.clear(),
                            this.load()
                    }
                }, {
                    key: "_readAllEndpoints",
                    value: function(e, t) {
                        for (var n = this, i = [], a = 0, s = 0; s < e.length; ++s)
                            this.options.apiBackend.read(e[s], function(s) {
                                return function(o, l) {
                                    if (++a,
                                            o ? (o.response ? n.options.error(o.response.msg) : n.options.error("Request was unsuccessful: " + o.status),
                                                i[s] = []) : i[s] = l,
                                        a === e.length)
                                        return t(i)
                                }
                            }(s))
                    }
                }, {
                    key: "send",
                    value: function(e, t) {
                        var n = {
                            token: this.options.api.token,
                            player: this.options.api.id,
                            author: this.options.api.user,
                            time: this.options.time(),
                            text: e.text,
                            color: e.color,
                            type: e.type
                        };
                        this.options.apiBackend.send(this.options.api.address, n, t),
                            this.dan.splice(this.danIndex, 0, n),
                            this.danIndex++;
                        var i = {
                            text: this.htmlEncode(n.text),
                            color: n.color,
                            type: n.type,
                            border: "2px solid " + this.options.borderColor
                        };
                        this.draw(i),
                        this.events && this.events.trigger("danmaku_send", n)
                    }
                }, {
                    key: "frame",
                    value: function() {
                        var e = this;
                        if (this.dan.length && !this.paused && this.showing) {
                            for (var t = this.dan[this.danIndex], n = []; t && this.options.time() > parseFloat(t.time); )
                                n.push(t),
                                    t = this.dan[++this.danIndex];
                            this.draw(n)
                        }
                        window.requestAnimationFrame(function() {
                            e.frame()
                        })
                    }
                }, {
                    key: "opacity",
                    value: function(e) {
                        if (void 0 !== e) {
                            for (var t = this.container.getElementsByClassName("dplayer-danmaku-item"), n = 0; n < t.length; n++)
                                t[n].style.opacity = e;
                            this._opacity = e,
                            this.events && this.events.trigger("danmaku_opacity", this._opacity)
                        }
                        return this._opacity
                    }
                }, {
                    key: "draw",
                    value: function(e) {
                        var t = this
                            , n = this.options.height
                            , i = this.container.offsetWidth
                            , s = this.container.offsetHeight
                            , o = parseInt(s / n)
                            , l = function(e) {
                            var n = e.offsetWidth || parseInt(e.style.width)
                                , i = e.getBoundingClientRect().right || t.container.getBoundingClientRect().right + n;
                            return t.container.getBoundingClientRect().right - i
                        }
                            , r = function(e) {
                            return (i + e) / 5
                        }
                            , d = function(e, n, s) {
                            for (var d = i / r(s), c = 0; t.unlimited || c < o; c++) {
                                var u = function(a) {
                                    var s = t.danTunnel[n][a + ""];
                                    if (!s || !s.length)
                                        return t.danTunnel[n][a + ""] = [e],
                                            e.addEventListener("animationend", function() {
                                                t.danTunnel[n][a + ""].splice(0, 1)
                                            }),
                                            {
                                                v: a % o
                                            };
                                    if ("right" !== n)
                                        return "continue";
                                    for (var c = 0; c < s.length; c++) {
                                        var u = l(s[c]) - 10;
                                        if (u <= i - d * r(parseInt(s[c].style.width)) || u <= 0)
                                            break;
                                        if (c === s.length - 1)
                                            return t.danTunnel[n][a + ""].push(e),
                                                e.addEventListener("animationend", function() {
                                                    t.danTunnel[n][a + ""].splice(0, 1)
                                                }),
                                                {
                                                    v: a % o
                                                }
                                    }
                                }(c);
                                switch (u) {
                                    case "continue":
                                        continue;
                                    default:
                                        if ("object" === (void 0 === u ? "undefined" : a(u)))
                                            return u.v
                                }
                            }
                            return -1
                        };
                        "[object Array]" !== Object.prototype.toString.call(e) && (e = [e]);
                        for (var c = document.createDocumentFragment(), u = 0; u < e.length; u++)
                            !function(a) {
                                e[a].type || (e[a].type = "right"),
                                e[a].color || (e[a].color = "#fff");
                                var s = document.createElement("div");
                                s.classList.add("dplayer-danmaku-item"),
                                    s.classList.add("dplayer-danmaku-" + e[a].type),
                                    e[a].border ? s.innerHTML = '<span style="border:' + e[a].border + '">' + e[a].text + "</span>" : s.innerHTML = e[a].text,
                                    s.style.opacity = t._opacity,
                                    s.style.color = e[a].color,
                                    s.addEventListener("animationend", function() {
                                        t.container.removeChild(s)
                                    });
                                var o = t._measure(e[a].text)
                                    , l = void 0;
                                switch (e[a].type) {
                                    case "right":
                                        l = d(s, e[a].type, o),
                                        l >= 0 && (s.style.width = o + 1 + "px",
                                            s.style.top = n * l + "px",
                                            s.style.transform = "translateX(-" + i + "px)");
                                        break;
                                    case "top":
                                        l = d(s, e[a].type),
                                        l >= 0 && (s.style.top = n * l + "px");
                                        break;
                                    case "bottom":
                                        l = d(s, e[a].type),
                                        l >= 0 && (s.style.bottom = n * l + "px");
                                        break;
                                    default:
                                        console.error("Can't handled danmaku type: " + e[a].type)
                                }
                                l >= 0 && (s.classList.add("dplayer-danmaku-move"),
                                    c.appendChild(s))
                            }(u);
                        return this.container.appendChild(c),
                            c
                    }
                }, {
                    key: "play",
                    value: function() {
                        this.paused = !1
                    }
                }, {
                    key: "pause",
                    value: function() {
                        this.paused = !0
                    }
                }, {
                    key: "_measure",
                    value: function(e) {
                        if (!this.context) {
                            var t = getComputedStyle(this.container.getElementsByClassName("dplayer-danmaku-item")[0], null);
                            this.context = document.createElement("canvas").getContext("2d"),
                                this.context.font = t.getPropertyValue("font")
                        }
                        return this.context.measureText(e).width
                    }
                }, {
                    key: "seek",
                    value: function() {
                        this.clear();
                        for (var e = 0; e < this.dan.length; e++) {
                            if (this.dan[e].time >= this.options.time()) {
                                this.danIndex = e;
                                break
                            }
                            this.danIndex = this.dan.length
                        }
                    }
                }, {
                    key: "clear",
                    value: function() {
                        this.danTunnel = {
                            right: {},
                            top: {},
                            bottom: {}
                        },
                            this.danIndex = 0,
                            this.options.container.innerHTML = "",
                        this.events && this.events.trigger("danmaku_clear")
                    }
                }, {
                    key: "htmlEncode",
                    value: function(e) {
                        return e.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#x27;").replace(/\//g, "&#x2f;")
                    }
                }, {
                    key: "resize",
                    value: function() {
                        for (var e = this.container.offsetWidth, t = this.container.getElementsByClassName("dplayer-danmaku-item"), n = 0; n < t.length; n++)
                            t[n].style.transform = "translateX(-" + e + "px)"
                    }
                }, {
                    key: "hide",
                    value: function() {
                        this.showing = !1,
                            this.pause(),
                            this.clear(),
                        this.events && this.events.trigger("danmaku_hide")
                    }
                }, {
                    key: "show",
                    value: function() {
                        this.seek(),
                            this.showing = !0,
                            this.play(),
                        this.events && this.events.trigger("danmaku_show")
                    }
                }, {
                    key: "unlimit",
                    value: function(e) {
                        this.unlimited = e
                    }
                }]),
                    e
            }();
            e.exports = o
        }
        , function(e, t, n) {
            "use strict";
            function i(e, t) {
                if (!(e instanceof t))
                    throw new TypeError("Cannot call a class as a function")
            }
            var a = function() {
                function e(e, t) {
                    for (var n = 0; n < t.length; n++) {
                        var i = t[n];
                        i.enumerable = i.enumerable || !1,
                            i.configurable = !0,
                        "value"in i && (i.writable = !0),
                            Object.defineProperty(e, i.key, i)
                    }
                }
                return function(t, n, i) {
                    return n && e(t.prototype, n),
                    i && e(t, i),
                        t
                }
            }()
                , s = function() {
                function e(t, n, a, s) {
                    i(this, e),
                        this.container = t,
                        this.width = n,
                        this.container.style.backgroundImage = "url('" + a + "')",
                        this.events = s
                }
                return a(e, [{
                    key: "resize",
                    value: function(e, t) {
                        this.container.style.width = e + "px",
                            this.container.style.height = t + "px",
                            this.container.style.top = 2 - t + "px"
                    }
                }, {
                    key: "show",
                    value: function() {
                        this.container.style.display = "block",
                        this.events && this.events.trigger("thumbnails_show")
                    }
                }, {
                    key: "move",
                    value: function(e) {
                        this.container.style.backgroundPosition = "-" + 160 * (Math.ceil(e / this.width * 100) - 1) + "px 0",
                            this.container.style.left = e - this.container.offsetWidth / 2 + "px"
                    }
                }, {
                    key: "hide",
                    value: function() {
                        this.container.style.display = "none",
                        this.events && this.events.trigger("thumbnails_hide")
                    }
                }]),
                    e
            }();
            e.exports = s
        }
        , function(e, t, n) {
            "use strict";
            function i(e, t) {
                if (!(e instanceof t))
                    throw new TypeError("Cannot call a class as a function")
            }
            var a = function() {
                function e(e, t) {
                    for (var n = 0; n < t.length; n++) {
                        var i = t[n];
                        i.enumerable = i.enumerable || !1,
                            i.configurable = !0,
                        "value"in i && (i.writable = !0),
                            Object.defineProperty(e, i.key, i)
                    }
                }
                return function(t, n, i) {
                    return n && e(t.prototype, n),
                    i && e(t, i),
                        t
                }
            }()
                , s = function() {
                function e() {
                    i(this, e),
                        this.events = {},
                        this.videoEvents = ["abort", "canplay", "canplaythrough", "durationchange", "emptied", "ended", "error", "loadeddata", "loadedmetadata", "loadstart", "mozaudioavailable", "pause", "play", "playing", "progress", "ratechange", "seeked", "seeking", "stalled", "suspend", "timeupdate", "volumechange", "waiting"],
                        this.playerEvents = ["screenshot", "thumbnails_show", "thumbnails_hide", "danmaku_show", "danmaku_hide", "danmaku_clear", "danmaku_loaded", "danmaku_send", "danmaku_opacity", "contextmenu_show", "contextmenu_hide", "notice_show", "notice_hide", "quality_start", "quality_end", "destroy", "resize", "fullscreen", "fullscreen_cancel", "webfullscreen", "webfullscreen_cancel", "subtitle_show", "subtitle_hide", "subtitle_change"]
                }
                return a(e, [{
                    key: "on",
                    value: function(e, t) {
                        this.type(e) && "function" == typeof t && (this.events[e] || (this.events[e] = []),
                            this.events[e].push(t))
                    }
                }, {
                    key: "trigger",
                    value: function(e, t) {
                        if (this.events[e] && this.events[e].length)
                            for (var n = 0; n < this.events[e].length; n++)
                                this.events[e][n](t)
                    }
                }, {
                    key: "type",
                    value: function(e) {
                        return -1 !== this.playerEvents.indexOf(e) ? "player" : -1 !== this.videoEvents.indexOf(e) ? "video" : (console.error("Unknown event name: " + e),
                            null)
                    }
                }]),
                    e
            }();
            e.exports = s
        }
        , function(e, t, n) {
            "use strict";
            function i(e, t) {
                if (!(e instanceof t))
                    throw new TypeError("Cannot call a class as a function")
            }
            var a = function() {
                function e(e, t) {
                    for (var n = 0; n < t.length; n++) {
                        var i = t[n];
                        i.enumerable = i.enumerable || !1,
                            i.configurable = !0,
                        "value"in i && (i.writable = !0),
                            Object.defineProperty(e, i.key, i)
                    }
                }
                return function(t, n, i) {
                    return n && e(t.prototype, n),
                    i && e(t, i),
                        t
                }
            }()
                , s = n(0)
                , o = function(e) {
                return e && e.__esModule ? e : {
                    default: e
                }
            }(s)
                , l = function() {
                function e(t) {
                    var n = this;
                    i(this, e),
                        this.player = t,
                        this.player.events.on("webfullscreen", function() {
                            n.player.resize()
                        }),
                        this.player.events.on("webfullscreen_cancel", function() {
                            n.player.resize()
                        });
                    var a = function() {
                        n.player.resize(),
                            n.isFullScreen("browser") ? n.player.events.trigger("fullscreen") : n.player.events.trigger("fullscreen_cancel")
                    };
                    this.player.container.addEventListener("fullscreenchange", a),
                        this.player.container.addEventListener("mozfullscreenchange", a),
                        this.player.container.addEventListener("webkitfullscreenchange", a)
                }
                return a(e, [{
                    key: "isFullScreen",
                    value: function() {
                        switch (arguments.length > 0 && void 0 !== arguments[0] ? arguments[0] : "browser") {
                            case "browser":
                                return document.fullscreenElement || document.mozFullScreenElement || document.webkitFullscreenElement;
                            case "web":
                                return this.player.container.classList.contains("dplayer-fulled")
                        }
                    }
                }, {
                    key: "request",
                    value: function() {
                        switch (arguments.length > 0 && void 0 !== arguments[0] ? arguments[0] : "browser") {
                            case "browser":
                                this.player.container.requestFullscreen ? this.player.container.requestFullscreen() : this.player.container.mozRequestFullScreen ? this.player.container.mozRequestFullScreen() : this.player.container.webkitRequestFullscreen ? this.player.container.webkitRequestFullscreen() : this.player.video.webkitEnterFullscreen && this.player.video.webkitEnterFullscreen();
                                break;
                            case "web":
                                this.player.container.classList.add("dplayer-fulled"),
                                    this.lastScrollPosition = o.default.getScrollPosition(),
                                    document.body.classList.add("dplayer-web-fullscreen-fix"),
                                    this.player.events.trigger("webfullscreen")
                        }
                    }
                }, {
                    key: "cancel",
                    value: function() {
                        switch (arguments.length > 0 && void 0 !== arguments[0] ? arguments[0] : "browser") {
                            case "browser":
                                document.cancelFullScreen ? document.cancelFullScreen() : document.mozCancelFullScreen ? document.mozCancelFullScreen() : document.webkitCancelFullScreen && document.webkitCancelFullScreen();
                                break;
                            case "web":
                                this.player.container.classList.remove("dplayer-fulled"),
                                    document.body.classList.remove("dplayer-web-fullscreen-fix"),
                                    o.default.setScrollPosition(this.lastScrollPosition),
                                    this.player.events.trigger("webfullscreen_cancel")
                        }
                    }
                }, {
                    key: "toggle",
                    value: function() {
                        var e = arguments.length > 0 && void 0 !== arguments[0] ? arguments[0] : "browser";
                        this.isFullScreen(e) ? this.cancel(e) : this.request(e)
                    }
                }]),
                    e
            }();
            e.exports = l
        }
        , function(e, t, n) {
            "use strict";
            function i(e, t) {
                if (!(e instanceof t))
                    throw new TypeError("Cannot call a class as a function")
            }
            var a = function() {
                function e(e, t) {
                    for (var n = 0; n < t.length; n++) {
                        var i = t[n];
                        i.enumerable = i.enumerable || !1,
                            i.configurable = !0,
                        "value"in i && (i.writable = !0),
                            Object.defineProperty(e, i.key, i)
                    }
                }
                return function(t, n, i) {
                    return n && e(t.prototype, n),
                    i && e(t, i),
                        t
                }
            }()
                , s = n(0)
                , o = function(e) {
                return e && e.__esModule ? e : {
                    default: e
                }
            }(s)
                , l = function() {
                function e(t) {
                    i(this, e),
                        this.storageName = {
                            opacity: "dplayer-danmaku-opacity",
                            volume: "dplayer-volume",
                            unlimited: "dplayer-danmaku-unlimited",
                            danmaku: "dplayer-danmaku-show",
                            subtitle: "dplayer-subtitle-show"
                        },
                        this.default = {
                            opacity: .7,
                            volume: t.options.volume || .7,
                            unlimited: (t.options.danmaku && t.options.danmaku.unlimited ? 1 : 0) || 0,
                            danmaku: 1,
                            subtitle: 1
                        },
                        this.data = {},
                        this.init()
                }
                return a(e, [{
                    key: "init",
                    value: function() {
                        for (var e in this.storageName) {
                            var t = this.storageName[e];
                            this.data[e] = parseFloat(o.default.storage.get(t) || this.default[e])
                        }
                    }
                }, {
                    key: "get",
                    value: function(e) {
                        return this.data[e]
                    }
                }, {
                    key: "set",
                    value: function(e, t) {
                        this.data[e] = t,
                            o.default.storage.set(this.storageName[e], t)
                    }
                }]),
                    e
            }();
            e.exports = l
        }
        , function(e, t, n) {
            "use strict";
            function i(e, t) {
                if (!(e instanceof t))
                    throw new TypeError("Cannot call a class as a function")
            }
            var a = function() {
                function e(e, t) {
                    for (var n = 0; n < t.length; n++) {
                        var i = t[n];
                        i.enumerable = i.enumerable || !1,
                            i.configurable = !0,
                        "value"in i && (i.writable = !0),
                            Object.defineProperty(e, i.key, i)
                    }
                }
                return function(t, n, i) {
                    return n && e(t.prototype, n),
                    i && e(t, i),
                        t
                }
            }()
                , s = function() {
                function e(t, n, a, s) {
                    i(this, e),
                        this.container = t,
                        this.video = n,
                        this.options = a,
                        this.events = s,
                        this.init()
                }
                return a(e, [{
                    key: "init",
                    value: function() {
                        var e = this;
                        if (this.container.style.fontSize = this.options.fontSize,
                                this.container.style.bottom = this.options.bottom,
                                this.container.style.color = this.options.color,
                            this.video.textTracks && this.video.textTracks[0]) {
                            var t = this.video.textTracks[0];
                            t.oncuechange = function() {
                                var n = t.activeCues[0];
                                if (n) {
                                    e.container.innerHTML = "";
                                    var i = document.createElement("p");
                                    i.appendChild(n.getCueAsHTML()),
                                        e.container.appendChild(i)
                                } else
                                    e.container.innerHTML = "";
                                e.events.trigger("subtitle_change")
                            }
                        }
                    }
                }, {
                    key: "show",
                    value: function() {
                        this.container.classList.remove("dplayer-subtitle-hide"),
                            this.events.trigger("subtitle_show")
                    }
                }, {
                    key: "hide",
                    value: function() {
                        this.container.classList.add("dplayer-subtitle-hide"),
                            this.events.trigger("subtitle_hide")
                    }
                }, {
                    key: "toggle",
                    value: function() {
                        this.container.classList.contains("dplayer-subtitle-hide") ? this.show() : this.hide()
                    }
                }]),
                    e
            }();
            e.exports = s
        }
    ])
});
//# sourceMappingURL=DPlayer.min.js.map

/**
 * @description 首页
 * @author Young
 * @contacts young@kingjoy.co
 */

//主播请求的ajax
var hostAjax;

//数组去重
var arrayOnly = function (ele, arr) {

    if (arr.length == 0) {
        return true;
    }

    for (var j = 0; j < arr.length; j++) {
        if (ele == arr[j]) {
            return false;
        } else {
            return true;
        }
    }
}

//返回随机字符串
var randomString = function () {
    var seed = new Array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'Q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'
    );//数组
    seedLen = seed.length;//数组长度
    var str = '';
    for (i = 0; i < 4; i++) {
        j = Math.floor(Math.random() * seedLen);
        str += seed[j];
    }
    return str;
}

/**
 * @description 首页JSONP数据加载
 * @todo 该功能有一个bug，即两个不同的ajax会被阻断其中一个
 * @author Young
 * @param obj => 查阅OPTIONS
 */
var getItemData = function (obj) {

    var OPTIONS = {
        url: "",
        data: {},
        failText: "data fetch fail",
        successCallback: function () {
        }
    }

    $.extend(true, OPTIONS, obj);

    //若前一次请求未完成，阻断。
    if (hostAjax && hostAjax.readyState != 4) {
        hostAjax.abort();
    }
    ;

    hostAjax = $.ajax({
        type: "GET",
        url: OPTIONS.url,
        data: OPTIONS.data,
        dataType: 'json',
        // dataType: "jsonp",
        // jsonp: "callback",
        // jsonpCallback:"cb",
        success: function (json) {
            if (OPTIONS.successCallback) {
                OPTIONS.successCallback(json);
            }
            ;
            if (window.console) {
                console.info(json);
            }
            ;
        },
        error: function (json) {
            if (window.console) {
                console.warn(OPTIONS.failText);
            }
            ;
        }
    });
}

/**
 * @description 一对一房间首页交互
 * @author Young
 */
var bindOrd = function () {
    var $ord = $(".ordRoom");
    $ord.on("click", function (e) {

        e.preventDefault();

        var $that = $(this);

        var ordImg = $that.find("img").attr("src"),
            ordTitle = $that.find(".title").text(),
            ordDuration = $that.data("duration"),
            ordPoints = $that.data("points"),
            ordStarttime = $that.data("starttime"),
            ordRoomId = $that.data("roomid"),
            ordAppointState = $that.data("appointstate");

        if (ordAppointState != '1') {
            return;
        }
        ;

        var tmp = "<div class='ordDialog'>" +
            "<img src=" + ordImg + " alt />" +
            "<div class='ordDialogContent'>" +
            "<h4>" + ordTitle + "</h4>" +
            "<p>直播时长：" + ordDuration + "</br>直播费用：" + ordPoints + "钻</br>开播时间：" + ordStarttime + "</p>" +
            "</div>" +
            "</div>";

        $.dialog({
            title: "立即约会",
            content: tmp,
            ok: function () {
                reserveRoom(ordRoomId);
            },
            okValue: "立即约会"
        }).show();

    });
}

/**
 * @description 生成首页排行榜，并输出到页面
 * @author Young
 * @param data: ajax获取的数据
 */
var renderRankList = function (data) {

    $.each(data, function (id, item) {
        if (id.indexOf("rank_") > -1) {

            var userItem = "";

            for (var i = 0; i < item.length; i++) {

                //容错 bug fix，只显示5条数据
                if (i == 5) break;

                var badge = "", //排行榜第一列图标
                    anchorLevel = "", //排行榜第二列图标
                    isExp = false, //是否是主播
                    exp = ""; //排行榜主播名字长度css

                if (id.indexOf("_exp_") > 0) isExp = true; // 判断是否是主播，当json中key包含“_exp_”的数据为主播排行榜数据

                // 当vip字段不为空时，显示贵族勋章，否则显示普通徽章
                // 如果是主播，不显示任何图标(lv_rich=0为主播)
                if (isExp) {
                    exp = 'rank-text-exp';
                }
                else {
                    if ('undefined' == typeof item[i].vip || item[i].vip.toString() == '0') {
                        badge = (item[i].icon_id == 0) ? "" : '<div class="rank-badge badge badge' + item[i].icon_id + '"></div>';
                    }
                    else {
                        badge = '<div class="hotListImg basicLevel' + item[i].vip + '"></div>';
                    }
                }

                //头像处理
                item[i].headimg = item[i].headimg ? window.IMG_PATH + '/' + item[i].headimg + '?w=80&h=80' : cross.cdnPath + '/src/img/head_80.png';

                // 赌圣、富豪榜显示爵位icon
                // 如果是主播的话不显示爵位，只显示等级icon
                anchorLevel = isExp ? '<div class="rank-mark hotListImg AnchorLevel' + item[i].lv_exp + '"></div>' : '';

                userItem += '<div class="rank-item panel-hover" rel="' + item[i].uid + '">' +
                    /*'<img class="rank-avatar" src="' + item[i].headimg + '" />' +*/
                    '<div class="rank-num">' + (i + 1) + '.</div>' +
                    '<div class="rank-item-des">' +
                    '<div class="rank-text ' + exp + '">' + item[i].username + '</div>' +
                    '<div class="rank-item-inner">' + badge + anchorLevel +
                    '</div>' +
                    '</div>' +
                    '<div class="personDiv" data-rel="' + item[i].uid + '">' +
                    '<div class="personContent clearfix">' +
                    '<img class="personLoading" src="' + Config.imagePath + '/loading.gif" />' +
                    '</div>' +
                    '</div>' +
                    '</div>';
            }

            $('#' + id).html(userItem);

        }
        ;
    });
}

/**
 * @description 搜索功能设置跳转
 * @author Young
 */
var searchHandle = function () {
    var $searchIpt = $("#searchIpt");
    var $searchBtn = $("#searchIptBtn");

    var searchActin = function () {
        var searchVal = $searchIpt.val();
        if (searchVal != "") {
            location.href = "/search?nickname=" + searchVal;
        }
    }
    //绑定click事件
    $searchBtn.on("click", function () {
        searchActin();
    });

    //回车键触发click事件
    $searchIpt.on("keyup", function (e) {
        if (e.keyCode == 13) {
            searchActin();
        }
        ;
    });
}

//处理滚动下来菜单
//当前翻页类型
var VideoList = function () {

    var loadTmp = "<div class='m-load'></div>";
    var that = this;
    var tplData = [];
    var tpl = "";
    //获取类型
    this.cat = "all";

    //加载数量
    this.pageCount = 0;
    this.pageSize = 31;
    //过滤6条数据后
    this.pageNumber = 25;

    /**
     * @description 页面视频追加列表渲染
     * @author Young
     * @param $tab: tab容器, data:JSONP所获取的数据, countStart列表截取的起始值
     */
    this.renderIndexData = function ($tab, data, countStart) {

        var currentData = [];
        var recData = [];
        var tmp = "";

        //房间判空
        if (!data.rooms || data.rooms.length == 0) {
            $('#' + $tab[0].id).append('<div class="main-tips">暂时还没有此类房间开放，尽请期待！</div>');
            return;
        }

        //带参个数判定
        if (arguments.length == 3) {
            currentData = data.rooms.slice(countStart, countStart + this.pageSize);
        } else {
            currentData = data.rooms;
        }

        //全部主播筛选
        currentData = (this.cat == 'all' ? currentData.slice(6) : currentData);

        if(this.cat == 'all') {
            recData = data.rooms;
            tplData = recData.slice(0 ,6);
            switch (true) {
                case "one_many":
                    tpl = renderOneToMoreItem(tplData);
                    break;
                case "ord":
                    tpl = renderOrdItem(tplData);
                    break;
                default:
                    tpl = renderItem(tplData);
            }
            $('.J-rank-anchor').html(tpl);
        }

        switch ($tab[0].id) {
            case "one_many":
                tmp = renderOneToMoreItem(currentData);
                break;
            case "ord":
                tmp = renderOrdItem(currentData);
                break;
            default:
                tmp = renderItem(currentData);
        }
        //append数据
        $tab.append(tmp);



        //绑定一对一房间预约
        if ($tab[0].id == "ord") {
            bindOrd();
        }
        ;

        //限制房间拦截逻辑，改为从直播间拦截限制房间
        //bindLimitedRoom($tab.find(".movieList"));

        //显示和不显示按钮
        var $moreBtn = $tab.siblings(".inx-more-btn");

        //显示append按钮
        $moreBtn.show();

        //取消加载图标
        $tab.find(".m-load").remove();

        //追加列表的事件绑定
        if ($(tmp).filter(".l-list").length == this.pageNumber) {

            //绑定一次加载更多按钮
            $moreBtn.one("click", function () {

                //添加转圈圈
                $("#" + that.cat).append(loadTmp);

                //按照参数cat类型显示数据
                that.renderIndexData($("#" + that.cat), data, that.pageCount);

                //如果追加成功，删除这个按钮
                //$(this).remove();
            });

            //如果length==20显示追加按钮
            $moreBtn.show();
        } else {
            //如果追加一次不足20个，则删除这个按钮
            $moreBtn.hide();
        }
        ;

        this.pageCount = this.pageCount + this.pageNumber;
    }

    //tab切换时重置
    $(".tab-item").on("click", function () {

        that.cat = $(this).data("cat");

        //如果类型为fav和res就不清空
        if (that.cat != "res") {

            //重置pageCount
            that.pageCount = 0;

            //添加等待图
            $("#" + that.cat).html("");
            $("#" + that.cat).append(loadTmp);

            //按照参数cat类型获取数据
            getIndexData(that.cat, function (data) {
                //清空cat
                $("#" + that.cat).html("");
                //渲染页面
                that.renderIndexData($("#" + that.cat), data, that.pageCount);
            }, function (ret) {

                console.log(ret.responseText);
            });

        }
        ;

    });
}

$(function () {

    //个人信息面板
    getPanelData($(".rank-content"));

    User.handleAfterGetUserInfo = function () {
        var img_url = window.User.IMG_URL;
        var qrcode_img = JSON.parse(window.User.QRCODE_IMG);
        $(".txt-download img").attr('src', img_url + '/' + qrcode_img[0].temp_name);
    }

    $('.bannerslider').flexslider({
        animation: "slide",
        controlsContainer: $(".custom-controls-container"),
        customDirectionNav: $(".custom-navigation a")
    });


    //搜索
    searchHandle();
    //返回顶部
    var JsTop = {
        btnToTop: $(".J-totop"),
        btnToBox: $(".J-box")
    };
    $(document).scroll(function () {
        var _top = $(document).scrollTop();
        _top > 0 ? JsTop.btnToBox.css("display", "block") : _top == 0 && JsTop.btnToBox.css("display", "none")
    })
    JsTop.btnToTop.on("click", function () {
        $(document).scrollTop(0)
    })

    //首页主播列表和排行榜数据，每1分钟请求一次
    setInterval(function () {
        //触发刷新任务
        $(".J-tab-menu").find(".tab-item[data-cat=all]").trigger("click");
        //获取排行榜主要数据
        getIndexData("rank", function (data) {
            //渲染排行榜列表
            renderRankList(data);
        });
    }, 300000);

    //初始化任务系统，该任务无需用户登录状态
    // var indexTask = new Task();
    // indexTask.initTask();

    var handle = getLocation("handle");
    var roomid = getLocation("rid");
    var timecost = getLocation("timecost");

    window.currentVideo.roomId = roomid;
    window.currentVideo.timeCost = timecost;

    //timeCount 实例化
    window.roomTimeCount = new RoomTimeCount();
    //一对多门票房间 实例化
    window.roomTicket = new RoomTicket();

    $(document).on("click", ".l-list", function () {
        //currentVideo
        window.currentVideo.roomId = $(this).data('roomid');
        //是否需要密码
        window.currentVideo.isPassword = $(this).data('tid') == 2 ? true : false;
        //是否是限制房间
        window.currentVideo.isLimited = $(this).data("islimited") == 1 ? true : false;
        //是否是时长房间
        window.currentVideo.isTimeCost = $(this).is('[timecost]') ? true : false;

        //房间类型
        //1: 普通房间 2: 密码房间 3: 门票房间 4: 一对一 6：时长房间 7：一对多
        //roomType待改造后使用
        //以后将isTimeCost字段迁移到 roomType里面，将isPassword 和 isLimited从tid分离出来
        window.currentVideo.roomType = $(this).data('tid');

        //每分钟花费
        //以后将timecost放进dataRoom里面 window.currentVideo.dataRoom
        window.currentVideo.timeCost = $(this).data("timecost");

        //密码房间初始化
        if (window.currentVideo.isPassword) {
            var roomPwd = new RoomPwd();
            roomPwd.afterPwdSuccess = function () {
                window.roomTimeCount.showComfirm();
            };
        }

        //时长房间初始化
        /**
         * 现已经改版，时长房间不能和密码房间同时存在
         * 2017.5.18
         */
        //if(!window.currentVideo.isPassword && window.currentVideo.isTimeCost){
        //    timeCount.showComfirm();
        //}

        //时长房间初始化
        if (window.currentVideo.isTimeCost) {
            window.roomTimeCount.showComfirm();
        }

    });

    //房间处理
    var handleArr = handle.split('|');
    for (var i = 0; i < handleArr.length; i++) {
        if (handleArr[i] == 'roompwd') {
            window.currentVideo.isPassword = true;
        }

        if (handleArr[i] == 'timecost') {
            window.currentVideo.isTimeCost = true;
        }
    }

    //判断是否是本人
    if (roomid == User.UID) {
        return;
    }
    ;

    /**
     * 现在已经改版，时长房间，一对一，一对多不能和密码房间同时存在
     * 2017.5.18
     */
    //密码房间,不是时长房间
    //if(window.currentVideo.isPassword && roomid && !window.currentVideo.isTimeCost){
    //    var roomPwd = new RoomPwd();
    //}

    //密码房间
    if (window.currentVideo.isPassword) {
        var roomPwd = new RoomPwd();
    }

    //密码房间，时长房间 同时存在
    //if(window.currentVideo.isPassword && roomid && window.currentVideo.isTimeCost){
    //    var roomPwd = new RoomPwd();
    //    roomPwd.afterPwdSuccess = function(){
    //        //alert('时长房间判断');
    //        timeCount.showComfirm();
    //    };
    //}

    //时长房间，不是密码房间
    //if(!window.currentVideo.isPassword && window.currentVideo.isTimeCost){
    //    timeCount.showComfirm();
    //}

    //时长房间
    if (window.currentVideo.isTimeCost) {
        window.roomTimeCount.showComfirm();
    }

    //一对多房间
    if (handle == "room_one_to_many") {
        var oneToManyData = JSON.parse(base64.decode(getLocation("data")));
        window.roomTicket.showBuyTicketDialog({
            ordTitle: oneToManyData.username,
            ordDuration: oneToManyData.duration,
            ordPoints: oneToManyData.points,
            ordStartTime: oneToManyData['start_time'],
            ordEndTime: oneToManyData["end_time"],
            ordRoomId: oneToManyData["rid"],
            ordOneToManyId: oneToManyData["id"]
        })
    }

    //显示登录窗口
    if (handle == "login") {
        User.showLoginDialog();
    }

    //显示注册窗口
    if (handle == "reg") {
        User.showRegDialog();
    }

    //主播列表
    var vl = new VideoList();

    //首页主播数据加载初始化
    $(".J-tab-menu").find(".tab-item[data-cat=all]").trigger("click");

    //获取排行榜主要数据
    getIndexData("rank", function (data) {
        //渲染排行榜列表
        renderRankList(data);
    });

});

