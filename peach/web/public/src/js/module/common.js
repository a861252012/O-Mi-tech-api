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