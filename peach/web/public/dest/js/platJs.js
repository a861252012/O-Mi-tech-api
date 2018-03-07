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
// $(function(){
//   //一对多门票房间 实例化
//   window.roomTicket = new RoomTicket();
//
//
// });