/**
 * @description: 发起的http请求，对接php
 * @author: Young
 * @date: 2017/03/01
 */
class HttpRequest {

    /**
     * 注释：方法返回后请求信息
     */

    // 获得送礼列表（礼物清单）的方法
    static getGiftListData(callback){
        $.ajax({
            type:"get",
            url:"/rank_list_gift",
            data: { uid: window._flashVars.rid },
            dataType:"json",
            success:function (giftData) {
                callback&&callback(giftData);
            }
        })
    }

    // 获得周排行榜数据的方法
    static getRankWeekData(callback){
        $.ajax({
            type:"get",
            url:"/rank_list_gift_week",
            data: { uid: window._flashVars.rid },
            dataType:"json",
            success:function (rankWeekData) {
                callback&&callback(rankWeekData);
            }
        })

    }

    //调用vip开通功能弹窗, 传入用户UID
    static showVipDialog(rid, successCallback){

        //初始化贵族实例
      var nb = new Noble();

      Noble.ins = nb;

      Noble.ins.setRoomId(rid);

      //调用开通成功后的前置方法
      Noble.chargeNoblePreSuccessCB = function (json) {
        var str = "";
        // for( var a in json.data ){
        // 	str = str + json.data[a] + ",";
        // }
        str = json.data.roomid + "," + json.data.uid + "," + json.data.name + "," + json.data.vip + "," + json.data.cashback;

        //成功回调
        if (successCallback) {successCallback(json.data)}
        //打开成功弹窗
        document.getElementById("videoRoom").openVipSuccess(str);
      };

      //开通成功后的后置方法
      Noble.chargeNobleSuccessCB = function () {
        location.reload();
      }

      Noble.showChargeDialog();

        //window.Fla.showNobleDialog(uid);
    }

    // 聊天部分 获得关键字屏蔽规则
    static getChatKeywords(callback){
        $.ajax({
            url:"/kw",
            type:"get",
            success: function(data){
                callback && callback(data)
            }
        });
    }


    //获取送礼面板数据
    static getGiftBoardList(callback){
        $.ajax({
            type:"get",
            url:"/goods",
            dataType:'json',
            data: {
                time: (new Date()).getTime()
            },
            success: function(boardList){
                callback && callback(boardList)
                // console.log(boardList)
            }
        })
    }

    //获取用户个人资料（全部资料，包括地址，年龄等）
    static getCheckUserInfo(uid, callback){
        $.ajax({
            type:"get",
            url:"/getuser/"+uid,
            dataType:"json",
            success:function (checkUserInfo) {
                callback && callback(checkUserInfo)
            }
        })
    }

    //获取添加关注信息
    static getFocusInfo(pid,ret,callback){
        $.ajax({
            type:"get",
            dataType:"json",
            url:"/focus",
            data:{
                pid:pid,
                ret:ret
            },
            success:function (focusInfo) {
                callback && callback(focusInfo)
            }
        })
    }

    //获取动画路径
    static getAnimationRoutes(callback){
        $.ajax({
            type: "GET",
            url:  _flashVars.httpDomain + "/flash/" + window.FLASH_VERSION + "/xml/g_data.xml",
            dataType: 'xml',
            success: function(animationList){
                callback && callback(animationList)
                // console.log(boardList)
            },
            error: function(){

            }
        })
    }
}

export default HttpRequest;