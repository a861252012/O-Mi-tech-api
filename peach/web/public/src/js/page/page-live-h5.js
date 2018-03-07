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
