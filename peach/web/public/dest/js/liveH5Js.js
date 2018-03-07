/*
 * @description 贵族体系
 * @auth Young
 */

(function (__noble, window) {

  //获取贵族道具信息
  var tmpDialogGetProps = ["<div class='noble-d-prop'>",
    "<div class='noble-d-prop_left'>",
      "<img src='" + window.CDN_HOST + "/flash/" + window.PUBLISH_VERSION + "/image/gift_material/#{gid}.png'/>",
      "</div>",
    "<div class='noble-d-prop_right'>",
    "<h2>#{title}</h2>",
    "<p>获取资格：#{level_title}</p>",
    "<p>获取方式：贵族坐骑，顾名思义是贵族的象征。当你开通#{level_title}后，该坐骑就会随着#{level_title}一起出现。</p>",
    "<p>坐骑描述：#{desc}</p>",
    "</div>",
    "</div>"].join("");

  __noble = function () {

    /**
     * @description 构造函数
     * @author young
     * @return null
     */

    this.currentGid = 30;
    this.roomId = 0;

    this.init = function () {
      Noble.initChargeDialog();
    };

    this.getCurrentGid = function () {
      return this.currentGid;
    };

    this.setCurrentGid = function (gid) {
      this.currentGid = gid;
    }

    this.getRoomId = function () {
      return this.roomId;
    }

    this.setRoomId = function (roomId) {
      this.roomId = roomId;
    }

    this.init();

  }

  /**
   * @author Young
   * @description 静态方法
   * @return null
   */
  $.extend(__noble, {

    //最新dialog对象
    currentChargeDialog: null,
    //实例对象
    ins: null,
    /**
     * 获取坐骑
     * @param  {json} data 坐骑id
     * @param  {function} func 请求成功后回调
     * @return {[type]}      [description]
     */
    getProp: function (data, func) {
      $.ajax({
        url: "/getvipmount",
        type: "POST",
        dataType: "json",

        //data: {
        //    mid: int[坐骑id]
        //}
        data: data,

        success: function (data) {
          if (func) {
            func(data)
          }
          ;
        },

        error: function () {
          Utility.log("get vip mount error!");
        }
      });
    },

    /**
     * 转换接口输出的数据
     * @auth Yvan
     * @param infoCell
     * @returns {*|Array}
     */
    changeNobleData: function (infoCell) {
      infoCell = infoCell || [];
      if (!infoCell.length) {
        var infoPermission = infoCell['permission'],
            infoSystem = infoCell['system'];

        //贵族等级icon
        infoCell['level_id_icon'] = '<span class="hotListImg basicLevel' + infoCell['level_id'] + '"></span>';

        //赠送爵位跟礼包钱
        for (var k in infoSystem) {
          if (k == "keep_level") {
            infoSystem[k] = infoSystem[k] + "钻";
          }
          else {
            infoSystem[k] = k == "gift_level" ? '<span class="hotListImg basicLevel' + infoSystem[k] + '"></span>' : infoSystem[k] + '<span class="noble-table-diamond"></span>';
          }
        }

        //是否限制访问房间
        infoPermission['allowvisitroom'] = infoPermission['allowvisitroom'] == 0 ? '不受限' : '限制';

        //是否允许修改昵称
        switch (infoPermission['modnickname'].toString()) {
          case '-1' :
            infoPermission['modnickname'] = '不受限';
            break;
          case '0' :
            infoPermission['modnickname'] = '限制';
            break;
          default :
            var mnnArr = infoPermission['modnickname'].split("|"),
                type = "";
            if (mnnArr[1] == 'month') {
              type = "月";
            }
            else if (mnnArr[1] == 'week') {
              type = "周";
            }
            else if (mnnArr[1] == 'year') {
              type = "年";
            }
            infoPermission['modnickname'] = mnnArr[0] + "次/" + type;
        }

        //是否有进房欢迎语
        infoPermission['haswelcome'] = infoPermission['haswelcome'] == 0 ? '无' : '有';

        //聊天文字时间限制
        infoPermission['chatsecond'] = infoPermission['chatsecond'] == 0 ? '' : infoPermission['chatsecond'] + '/次';

        //是否有聊天特效
        infoPermission['haschateffect'] = infoPermission['haschateffect'] == 0 ? '无' : '有';

        //聊天文字长度限制
        switch (infoPermission['chatlimit'].toString()) {
          case '-1' :
            infoPermission['chatlimit'] = '不限制';
            break;
          case '0' :
            infoPermission['chatlimit'] = '禁言';
            break;
          default :
            infoPermission['chatlimit'] = infoPermission['chatlimit'] + '字';
        }

        //房间是否有贵宾席
        infoPermission['hasvipseat'] = infoPermission['hasvipseat'] == 0 ? '无' : '有';

        //防止被禁言
        switch (infoPermission['nochat'].toString()) {
          case '1' :
            infoPermission['nochat'] = '防止房主';
            break;
          case '0' :
            infoPermission['nochat'] = '无';
            break;
          case '2' :
            infoPermission['nochat'] = '防止管理员';
            break;
          case '1|2' :
            infoPermission['nochat'] = '防止房主、管理员';
            break;
          default :
        }

        //禁言别人的权限
        infoPermission['nochatlimit'] = infoPermission['nochatlimit'] == 0 ? '无' : infoPermission['nochatlimit'] + '普通用户/天';

        //防被踢
        switch (infoPermission['avoidout'].toString()) {
          case '1' :
            infoPermission['avoidout'] = '防房主';
            break;
          case '0' :
            infoPermission['avoidout'] = '无';
            break;
          case '2' :
            infoPermission['avoidout'] = '防止管理员';
            break;
          case '1|2' :
            infoPermission['avoidout'] = '防止房主、管理员';
            break;
          default :
        }

        //踢人的权限
        infoPermission['letout'] = infoPermission['letout'] == 0 ? '无' : infoPermission['letout'] + '普通用户/天';

        //是否允许隐身
        infoPermission['allowstealth'] = infoPermission['allowstealth'] == 0 ? '无' : '有';
      }
      return infoCell;
    },
    /**
     * 贵族充值
     * @param data: func:回调 data:[传入ajax 含有两个参数，参数gid为贵族等级id，roomid为房间id，如果roomid为空则不给佣金]
     * @return null [description]
     */
    chargeNoble: function (data) {

      var that = this;

      $.ajax({
        url: "/openvip",
        type: 'get',
        dataType: "jsonp",
        jsonp: "callback",
        jsonpCallback: "cb",

        // data: {
        // 	gid: "", groupid [群组id]
        // 	roomid: "" 如果在房间内开通[房间id]
        // },

        data: data,

        success: function (res) {

          that.currentChargeDialog.close();

          switch (res.code) {
            case 0:

              //开通成功后的前置方法
              that.chargeNoblePreSuccessCB(res);

              $.tips("贵族开通成功！您现在就可以使用您的专属坐骑啦！", function () {

                //开通贵族成功后，点击成功按钮后的回调
                that.chargeNobleSuccessCB(res);

              });
              break;

            case 102:

              $.dialog({
                title:"提示",
                content:"您的钻石不足, 充值即可开通贵族喔!",
                okValue:"立即充值",
                ok:function () {
                  location.href = "/charge/order"
                }
              }).show();
              break;

            default:
              $.tips(res.msg);
              break;

          }
        },

        error: function () {
          Utility.log("charge noble error!");
          that.chargeNobleErrorCB();
        }

      });

    },

    //充值回调
    chargeNobleSuccessCB: function (res) {
      //todo
    },

    //充值成功前回调
    chargeNoblePreSuccessCB: function (res) {
      //todo
    },

    //充值错误回调
    chargeNobleErrorCB: function () {

    },
    //初始化弹窗
    initChargeDialog: function () {
      var that = this;

      var tmpDialogChargeNoble = ["<div class='noble-d-charge'>",
        "<ul class='noble-d_menu clearfix'></ul>",

        "<div class='noble-d_main'></div>",
        "</div>"].join("");

      //init
      that.currentChargeDialog = $.dialog({
        title: "开通贵族",
        content: tmpDialogChargeNoble,
        onshow: function () {

          var gid = that.ins.getCurrentGid();

          //弹窗list
          that.appendNobleDialogList(gid);

          //绑定tab事件
          that.bindNobleSwitchEvent();

        }

      });
    },

    //添加开通贵族弹窗列表
    appendNobleDialogList: function (gid) {
      var that = this;
      var cgid = 0;

      that.getNobleAllInfo(function (data) {

        var tmp = "";
        var info = data.info;

        $.each(info, function (i, e) {
          tmp = tmp + "<li class='noble-d_tab' data-gid='" + info[i].gid + "'><span class='hotListImg basicLevel" + info[i].level_id + "'></span></li>";
        });

        var $tmp = $(tmp);
        $tmp.eq(0).addClass("active");
        $(".noble-d_menu").html($tmp);

        //触发初始化的tab
        $(".noble-d_menu").find(".noble-d_tab").filter("[data-gid=" + gid + "]").trigger("click");
      });

    },

    //绑定充值弹窗事件
    showChargeDialog: function () {

      //show
      this.currentChargeDialog.show();

    },

    //获取道具信息
    getPropInfo: function ($target) {

      return {
        "gid": $target.data("gid"),
        "title": $target.data("title"), //坐骑名字
        "level_title": $target.data("lvtitle"), //贵族等级
        "desc": $target.data("desc") //坐骑描述
      }

    },

    //绑定获取道具事件
    showGetPropsDialog: function ($target, func) {

      var that = this;

      //dialog模板
      var tmp = Utility.template(tmpDialogGetProps, that.getPropInfo($target));

      var dialogGetProp = $.dialog({
        title: "贵族专属",
        content: tmp,
        onshow: function () {

        },

        ok: function () {
          dialogGetProp.close();

          if (func) {
            func()
          }
          ;

        },
        okValue: "开通贵族身份"
      }).show();

    },

    /**
     * 通过id获取贵族信息
     * @param { int } gid 贵族id
     * @param  { function } successCB 成功后的回调
     * @return null
     */
    getNobleInfo: function (successCB) {

      var that = this;

      $.ajax({
        url: "/getgroup",
        type: 'get',
        dataType: "jsonp",
        jsonp: "callback",
        jsonpCallback: "cb",

        data: {"gid": that.ins.getCurrentGid()},

        success: function (res) {
          //Utility.log(res);
          if (res.code == 0) {
            if (successCB && res.info) {
              successCB(res.info);
            }
            ;
          } else {
            $.tips(res.msg);
          }
          ;
        },

        error: function () {
          Utility.log("get noble group info failure.");
        }

      });

    },

    /**
     * 获取贵族所有信息
     * @return {[type]} [description]
     */
    getNobleAllInfo: function (successCB) {
      $.ajax({
        url: '/getgroupall',
        type: 'get',
        dataType: "jsonp",
        jsonp: "callback",
        jsonpCallback: "cb",
        data: "",
        success: function (data) {
          if (!data.code) {
            if (successCB) {
              successCB(data);
            }
            ;
          } else {
            Utility.log(data.msg);
          }
          ;
        },
        error: function () {
          Utility.log("ajax request error");
        }
      });
    },

    /**
     * 更新贵族弹窗字符串
     * @param  {json} data 数据输入对象
     * @return {string} 返回数据模板
     */
    flushNobleInfo: function (data) {

      data.nobleLink = "/noble";

      var tmp = ["<div class='noble-d-charge_head'>",
        "<h2>" + data.level_name + "</h2>",
        "</div>",
        "<div class='noble-d-charge_content'>",
        "<table>",
        "<tr><td>首开礼包：</td><td>" + data.system.gift_money + "</td><td>改名限制：</td><td>" + data.permission.modnickname + "</td></tr>",
        //"<tr><td>赠送爵位：</td><td>" + data.system.gift_level + "</td><td>房间特效欢迎语：</td><td>" + data.permission.haswelcome + "</td></tr>",
        "<tr><td>坐骑：</td><td>" + data.g_mount.name + "</td><td>房间特效欢迎语：</td><td>" + data.permission.haswelcome + "</td></tr>",
        "<tr><td>贵族标识：</td><td>" + data.level_id_icon + "</td><td>聊天特效：</td><td>" + data.permission.haschateffect + "</td></tr>",
        "<tr><td>房间限制：</td><td>" + data.permission.allowvisitroom + "</td><td>发送文字特权：</td><td>" + data.permission.chatlimit + "</td></tr>",
        "<tr><td>贵宾席位置：</td><td>" + data.permission.hasvipseat + "</td><td>隐身人：</td><td>" + data.permission.allowstealth + "</td></tr>",
        "</table>",
        "<h3>开通价格：" + data.system.open_money + "</h3>",
        "<p>次月保级条件：贵族等级有效期内，累计充值达" + data.system.keep_level + "。</p>",
        "</div>",
        "<div class='noble-d-charge_btnbox'>",
        "<button class='noble-d_charge_btn btn btn-center' data-gid='" + data.gid + "'>立即开通贵族</button>",
        "<a href='" + data.nobleLink + "' target='_blank' class='noble-d-link'>了解更多</a>",
        "</div>"].join("");

      $(".noble-d_main").html(tmp);

    },

    /**
     * 绑定获取贵族信息事件
     * @return {null} [description]
     */
    bindNobleSwitchEvent: function () {

      var that = this;

      $(".noble-d_menu").on("click", ".noble-d_tab", function () {
        var $this = $(this);

        that.ins.setCurrentGid($this.data("gid"));

        $this.siblings(".noble-d_tab").removeClass("active").end().addClass("active");

        //刷新贵族信息
        that.getNobleInfo(function (data) {

          that.bindNobleDialogEvent(data);

        });

      });
    },

    /**
     * 绑定开通贵族弹窗
     */
    bindNobleDialogEvent: function (data) {
      var that = this;
      //转换数据
      data = that.changeNobleData(data);
      //刷新信息
      that.flushNobleInfo(data);

      //点击充值按钮
      $(".noble-d_charge_btn").on("click", function (e) {

        //disabled
        $(this).prop("disabled", true);

        var gid = $(this).data("gid");
        //调用开通贵族接口
        that.chargeNoble({
          "gid": gid,
          "roomId": window.ROOMID
        });

        that.chargeNobleErrorCB = function () {
          $(this).prop("disabled", false);
        }

      });
    }


  });

  window.Noble = __noble;

})(typeof Noble !== "undefined" ? Noble : {}, window);
$(function () {

    /**
     * 该方法已经被迁移到了React中
     */
    // window.Fla = {};
    //
    // //Fla开头用于flash内部方法调用
    // Fla.showNobleDialog = function (rid) {
    //
    //   var nb = new Noble();
    //
    //   Noble.ins = nb;
    //
    //   Noble.ins.setRoomId(rid);
    //
    //   //调用开通成功后的前置方法
    //   Noble.chargeNoblePreSuccessCB = function (json) {
    //     var str = "";
    //     // for( var a in json.data ){
    //     // 	str = str + json.data[a] + ",";
    //     // }
    //     str = json.data.roomid + "," + json.data.uid + "," + json.data.name + "," + json.data.vip + "," + json.data.cashback;
    //
    //     document.getElementById("videoRoom").openVipSuccess(str);
    //   };
    //
    //   //开通成功后的后置方法
    //   Noble.chargeNobleSuccessCB = function (json) {
    //     location.reload();
    //   }
    //
    //   Noble.showChargeDialog();
    //
    // }

    //邮箱检查
    //checkSafeEmail();
    replaceOldRoomLink();
    User.handleAfterGetUserInfo = function () {
        var img_url = window.User.IMG_URL;
        var qrcode_img = JSON.parse(window.User.QRCODE_IMG);
        $("#J_qrCode, #J_menuQrCode").attr('src', img_url + '/' + qrcode_img[0].temp_name);
    }
    $('#J_smartBannerBig').addClass('smart-banner-shows');
});

//直播间替换旧房间链接
function replaceOldRoomLink() {
    var search = location.pathname;
    var roomid = search.match(/(\d+)/)[0];
    $("#oldRoom").attr("href", "/" + roomid);
}

//安全邮箱检查
function checkSafeEmail() {
    var mailCheck = window.SAFE_MAIL_STATE;
    //拼接弹窗的内容
    var checkSafeMail = "<div class='mail-check-live'>" +
        "<table class='needCheck'>" +
        "<tbody>" +
        "<tr >" +
        "<td colspan='2'>安全访问条件:</td>" +
        "</tr>" +
        "<tr class='mail-check-status'>" +
        "<td>邮箱验证</td>" +
        "<td class='needCheck-item '><span class='needCheck-item-yes' >是</span></td>" +
        "</tr>" +
        "<tr>" +
        "<td>账户余额</td>" +
        "<td class='needCheck-item'>" + mailCheck.in_limit_points + "钻</td>" +
        "</tr>" +
        "</tbody>" +
        "</table>" +
        "<table class='nowCheck'>" +
        "<tbody>" +
        "<tr>" +
        "<td colspan='2'>您的当前状态:</td>" +
        "</tr>" +
        "<tr class='mail-check-status'>" +
        "<td>邮箱验证</td>" +
        "<td class='needCheck-item nowCheck-item'></td>" +
        "</tr>" +
        "<tr>" +
        "<td>账户余额</td>" +
        "<td class='needCheck-item'>" + mailCheck.points + "钻</td>" +
        "</tr>" +
        "</tbody>" +
        "</table>" +
        "<div class='mail-check-live-level'>" +
        "<span></span>" +
        "<span>当前账号安全级别:</span>" +
        "<span class='mail-check-live-level-text'></span>" +
        "<div class='p-bar'><span class='progress'></span></div>" +
        "</div>" +
        "<div class='mail-warn'>" +
        "账号提醒：您的账户" +
        "<span class='mail-warn-points'>低于" + mailCheck.in_limit_points + "钻,</span>" +
        "存在安全危机，请充值或验证邮箱提高账户安全度" +
        "</div>" +
        "</div>";
    //调用dialog
    var mailSafeDialog = $.dialog({
        title: "账号安全提醒",
        content: checkSafeMail,
        okValue: "验证邮箱",
        closeButtonDisplay: true,
        ok: function () {
            //跳转邮箱验证路径
            location.href = "/mailverific";
        },
        cancelValue: "充值",
        cancel: function () {
            //跳转充值路径
            showPay();
        }
    });

    //如果是游客，无弹窗
    if (mailCheck.roled === "") {
        mailSafeDialog.remove();
    } else {
        mailSafeDialog.show();
    }
    //判断用户当前状态是否验证邮箱，如果是，则显示“是”，否则显示“否”
    if (mailCheck.safemail === "") {
        $(".nowCheck-item").html("否")
    } else {
        $(".nowCheck-item").html("是")
    }

    //判断用户账号级别
    var checkNumber = 0;//0：账号级别低；1：账号级别中；2：账号级别高

    var needPoints = parseInt(mailCheck.in_limit_points);

    var nowPoints = parseInt(mailCheck.points);

    if (mailCheck.in_limit_safemail === "0") {
        //如果in_limit_safemail===0，则不进行邮箱验证，只验证钻石数
        $(".mail-check-status").remove();

        if (needPoints > nowPoints) {
            $('.mail-warn-points').show();
            checkNumber = 0;//如果用户钻石数低于要求钻石数，则账号安全级别为低
        } else {
            checkNumber = 2;//如果用户钻石数低于要求钻石数，则账号安全级别为高
        }
    } else {
        //如果in_limit_safemail===1，则需要同时进行邮箱跟钻石数的验证
        if (needPoints > nowPoints) {//如果用户钻石数低于要求钻石数

            //提示中出现钻石数
            $('.mail-warn-points').show();

            if (mailCheck.safemail === "") {
                checkNumber = 0;

            } else {
                checkNumber = 1;
            }
        } else {
            if (mailCheck.safemail === "") {
                checkNumber = 1;
                if (mailCheck.new_user === "0") {//老用户
                    setTimeout(function () {//钻石够，显示10s消失
                        mailSafeDialog.remove();
                    }, 10000);
                } else {
                    mailSafeDialog.show();
                }
            } else {
                checkNumber = 2;
            }
        }
    }
    switch (checkNumber) {
        case 0:
            $(".progress").addClass("progress-l");
            $(".mail-check-live-level-text").html("低").css("color", "red");
            break;
        case 1:
            $(".progress").addClass("progress-m");
            $(".mail-check-live-level-text").html("中").css("color", "#ECB43A");
            break;
        default:
            mailSafeDialog.remove();
    }
}

//重新写入代理
var gserver = window.location.protocol + "//" + window.location.hostname;

//获取url参数中的rid
function getRid() {
    var url = window.location.href.replace(/[><'"]/g, "");
    var paraString = url.substring(url.indexOf("?") + 1, url.length).split("&");
    var paraObj = {};
    var i;
    var j;

    for (i = 0; j = paraString[i]; i++) {
        paraObj[j.substring(0, j.indexOf("=")).toLowerCase()] = j.substring(j.indexOf("=") + 1, j.length);
    }

    var returnValue = paraObj["rid"];

    if (typeof(returnValue) == "undefined") {
        return "";
    } else {
        return returnValue;
    }

}

//获取用户PHPSESSID
function getUserKey() {
    var cookieVal = window.document.cookie;
    var user_key = "";
    //alert(cookieVal);
    if (cookieVal != undefined) {
        var cookies = cookieVal.split(";");
        for (var i = 0; i < cookies.length; i++) {
            var cookieOne = cookies[i];
            var pos = cookieOne.indexOf("=");
            if (pos > -1) {
                var key = cookieOne.substring(0, pos);
                key = key.replace(/^\s+|\s+$/g, "");
                var value = cookieOne.substring(pos + 1);
                value = value.replace(/^\s+|\s+$/g, "");
                if (key == "PHPSESSID") {
                    user_key = value;
                }
            }
        }
    }
    return user_key;
}

//获取room key
function getRoomKey() {
    var cookie = window.document.cookie, t = "";
    if (void 0 != cookie) {
        for (var cookieSpl = cookie.split(";"), i = 0; i < cookieSpl.length; i++) {
            var o = cookieSpl[i], r = o.indexOf("=");
            if (r > -1) {
                var key = o.substring(0, r);
                key = key.replace(/^\s+|\s+$/g, "");
                var value = o.substring(r + 1);
                value = value.replace(/^\s+|\s+$/g, "").replace(/\"/g, ""), "room_host" == key && (t = value);
            }
        }
    }
    return t;
}


//获取flash
function getSWF(movieName) {
    if (navigator.appName.indexOf("Microsoft") != -1) {
        return window[movieName];
    } else {
        return document[movieName];
    }
}

function showUserCenter() {//个人中心
    if ($.isEmptyObject(window.OpenAPI.link)) {
        window.open(gserver + "/member/index", "_blank");
    } else {
        //接入第三方平台
        window.open(window.OpenAPI.link.usercenter, "_blank");
    }
}

function gohall() {//大厅

    if ($.isEmptyObject(window.OpenAPI.link)) {
        window.open(gserver + "/", "_blank");
    } else {
        //接入第三方平台
        window.open(window.OpenAPI.link.hall, "_blank");
    }
}

function gomarket() {//商场
    if ($.isEmptyObject(window.OpenAPI.link)) {
        window.open(gserver + "/shop", "_blank")
    } else {
        //接入第三方平台
        window.open(window.OpenAPI.link.shop, "_blank");
    }

}

function uattention() {//我的关注
    window.open(gserver + "/member/attention", "_blank")
}

function uprops() {//道具
    window.open(gserver + "/member/scene", "_blank")
}

function uconsRecords() {//消费记录
    window.open(gserver + "/member/consumerd", "_blank")
}

function userMsg() {//私信
    window.open(gserver + "/member/msglist/2", "_blank")
}

function systemMsg() {//系统消息
    if ($.isEmptyObject(window.OpenAPI.link)) {
        window.open(gserver + "/member/msglist/1", "_blank")
    } else {
        window.open(window.OpenAPI.link.msg, "_blank");
    }
}

function showPay() { //提示充值
    if ($.isEmptyObject(window.OpenAPI.link)) {
        window.open(gserver + "/charge/order", "_blank");
    } else {
        //接入第三方平台
        window.open(window.OpenAPI.link.pay, "_blank");
    }
}

function showReg() {
    //跳到注册页面
    //window.open("/op/index.php", "_blank");
    User.showRegDialog();
}

function showLogin() {
    //跳到登录页面
    User.showLoginDialog();

}

function showLogout() {//退出登录
    window.location.href = gserver + "/logout";
}

function gotoRoom(_rid) {//跳转到指定房间
    window.top.location.href = window._flashVars.httpDomain + "/" + _rid;
}

function reportVideo(_uid) {//举报
    alert("已经举报了")
}

//用户断开刷新或者跳转操作关闭rtmp
window.onbeforeunload = function () {
    getSWF("videoRoom").closeRtmp();
    console.log("on before unload");
}
