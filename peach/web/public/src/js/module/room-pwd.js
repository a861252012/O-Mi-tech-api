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