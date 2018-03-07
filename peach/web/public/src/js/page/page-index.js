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

