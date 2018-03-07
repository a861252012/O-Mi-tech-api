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
