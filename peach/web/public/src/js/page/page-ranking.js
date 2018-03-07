/**
 * @description 排行页面
 * @author Young
 * @contacts young@kingjoy.co
 */

var JSON = {
    isEmpty:function(o){
        for (var i in o) {
            return false
        };
        return true;
    },
    getLength:function(o){
        var l = 0;
        for (var i in o) {
            if (o.hasOwnProperty(i)) {
                l++;
            }
        };
        return l;
    }
};

var view = {},
    data = {},
    //初始化数据
    dataInit = function(cb){

        $.ajax({
            url: '/rank_data',
            dataType: "json",
            success: function(json){
                data.rankData = json;
                if (cb) { cb() };
            },
            error: function(json){
               if(console){console.log("rank data fetch error")}
            }
        });

    },
    //初始化视图
    viewInit = function(){
        $.extend(view, {
            "$container": $(".J-tab")
        });

        //普通列表渲染
        $.each(data.rankData, function(id, item) {

            if (id.indexOf("rank_appoint") > -1) { return; };

            var user = '',
                userItem = "",
                index = 0;

            var dataLen = JSON.getLength(item);

            for(var i in item){
                index++;
                var num = i>0 ? ' num'+index : '';
                var imgURL = (item[i].headimg == "" || item[i].headimg == "null") ? (Config.imagePath + "/head_40.png") : window.IMG_PATH + '/' + item[i].headimg +'?w=40&h=40';
                var img = (index == 1 || index == 2 || index == 3) ? '<img class="rank-list_por" src="'+ imgURL +'">' : '';

                var badge = "", //排行榜第一列图标
                    mark = "", //排行榜第二列图标
                    isExp = false,//是否是普通观众, true为主播，false为普通观众
                    link = 'href="/' + item[i].uid + Config.liveMode + '" rel="'+ item[i].uid +'"';

                if(id.indexOf("_exp_") > 0) isExp = true; // 判断是否是主播，当json中key包含“_exp_”的数据为主播排行榜数据

                // 当vip字段不为空时，显示贵族勋章，否则显示普通徽章
                // 如果是主播，不显示任何图标
                if(!isExp) {
                    if('undefined' == typeof item[i].vip || item[i].vip.toString() == '0') {
                        badge = (Number(item[i].icon_id) == 0) ? "" : '<div class="rank-list_badge badge badge' + item[i].icon_id + '"></div>';
                    }
                    else {
                        badge = '<div class="rank-list_badge hotListImg basicLevel' + item[i].vip + '"></div>';
                    }

                    link = 'rel="'+ item[i].uid +'"';
                }

                // 赌圣、富豪榜显示爵位icon
                // 如果是主播的话不显示爵位，只显示等级icon
                //mark = isExp ? "AnchorLevel" + item[i].lv_exp : "basicLevel" + item[i].lv_rich;
                mark = isExp ? '<div class="hotListImg AnchorLevel'+item[i].lv_exp+'"></div>':'';

                var shortName = (i < 3 && (Number(item[i].icon_id) > 0 || Number(item[i].vip) > 0)) ? "rank-list_name__inshort" : "";
                //tmp
                userItem += '<li>'
                                + (i == 0 ? '' : '<div class="rank-list_num'+ num +'">' + index + '</div>') + img
                                +'<a '+ link +' class="rank-list_name panel-hover">'
                                    +'<span class="rank-list_name__in '+ shortName +'">'+ item[i].nickname +'</span>'
                                    +'<div class="personDiv" data-rel="'+ item[i].uid +'">'
                                        +'<div class="personContent clearfix">'
                                            +'<img class="personLoading" src="'+ Config.imagePath +'/loading.gif" />'
                                        +'</div>'
                                    +'</div>'
                                +'</a>' + badge + mark
                            +'</li>';

                if (dataLen < 15) {

                    user = user + userItem;
                    userItem = "";

                    if (dataLen == (Number(i)+1)) {
                        user = "<ul class='rank-menu_col'>" + user + "</ul>";
                    };

                } else {

                    if (index % 15 == 0){
                        user = user + "<ul class='rank-menu_col'>" + userItem + "</ul>";
                        userItem = "";
                    }
                };

            }

            $('#'+id).html(user);

        });

        //特殊列表渲染（排名前五人气排行榜）
        $.each(data.rankData, function(id, item) {

            if (id.indexOf("rank_appoint") == -1) { return; };

            var user = '',
                userItem = "",
                index = 0;


            var dataLen = 5;

            for(var i in item){

                if (index == 5) {break;};

                index++;

                var num = i > 0 ? ' num' + index : '';

                var imgURL = (item[i].headimg == "" || item[i].headimg == "null") ? (Config.imagePath + "/head_150.png") : window.IMG_PATH + '/' + item[i].headimg +'?w=150&h=150';
                // var img = '<img class="rank-list_por" src="'+ imgURL +'">';
                // //tmp
                // userItem += '<ul class="rank-menu_col"><li>'
                //                 +'<div class="rank-list_num'+ num +'"></div>' + img
                //                 +'<a href="/' + item[i].uid + Config.liveMode +'" rel="'+ item[i].uid +'" class="rank-list_name panel-hover">'
                //                     +'<span class="rank-list_name__in">'+ item[i].nickname +'</span>'
                //                     +'<div class="personDiv" data-rel="'+ item[i].uid +'">'
                //                         +'<div class="personContent clearfix">'
                //                             +'<img class="personLoading" src="'+ Config.imagePath +'/loading.gif" />'
                //                         +'</div>'
                //                     +'</div>'
                //                 +'</a>'
                //                 +'<div class="rank-list_des">'+ (item[i].description == "" ? "此人好懒，大家帮TA想想写些什么吧。" : item[i].description) +'</div>'
                //             +'</li></ul>';

                var img = '<img class="rank-list-por" src="'+ imgURL +'">';

                userItem += '<ul class="rank-menu-col">' +
                                '<li>' +
                                    '<div class="rank-list-num '+ num +'">' +
                                        img +
                                        '<span class="num">' + i + '</span>' +
                                        (i == 0 ? '<div class="crown"></div>' : '') +
                                    '</div>' +
                                    '<div class="rank-box">' +
                                        '<a href="/' + item[i].uid + Config.liveMode + '" rel="' + item[i].uid + '" class="rank-name panel-hover">' +
                                            '<span class="rank-list-name">' + item[i].nickname + '</span>' +
                                            '<div class="personDiv" data-rel="' + item[i].uid + '">' +
                                                '<div class="personContent clearfix">' +
                                                    '<img class="personLoading" src="' + Config.imagePath + '/loading.gif" />' +
                                                '</div>' +
                                            '</div>' +
                                        '</a>' +
                                        '<div class="rank-list-dsc">'+ (item[i].description == "" ? "此人好懒，大家帮TA想想写些什么吧。" : item[i].description) + '</div>' +
                                    '</div>' +
                                '</li>' +
                            '</ul>';
                user = user + userItem;
                userItem = "";
            }

            $('#'+id).html(user);

        });
    }

    //初始化事件
    eventInit = function(){
        getPanelData(view.$container);
    };


$(function(){
    dataInit(function(){
        viewInit();
        eventInit();
    });
});
