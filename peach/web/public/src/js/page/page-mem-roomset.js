$(function(){

    //初始化下拉菜单
    var resHour = loopOptions({startNum: 0, endNum: 23});
    $("select[name='hour']").append(resHour);

    var resMinute = loopOptions({startNum: 0, endNum: 59, interval: 5});
    $("select[name='minute']").append(resMinute);

    var resDuration = loopOptions({startNum: 25, endNum: 55, interval: 30});
    $("#oneToOne").find("select[name='duration']").append(resDuration);

    var resDuration = loopOptions({startNum: 20, endNum: 60, interval: 5});
    $("#onToMore").find("select[name='duration']").append(resDuration);

    var resPoints = loopOptions({startNum: 2000, endNum: 10000, interval: 1000});
    $("select[name='select-points']").append(resPoints);

    $("#rPwdRadioOpen").on("click", function(){
        $(".J-pwd-form").removeClass("none");
    });

    $("#rPwdRadioClose").on("click", function(){
        $(".J-pwd-form").addClass("none");
    });

    //修改房间密码功能
    $('#btnChangePW').on("click", function(){

        $('#rPwd').trigger("keyup");

        if ($('#rPwdTips').text().length > 0 && $("[name='room-radio']:checked").val() == "true") {
            return;
        };

        $.ajax({
            url: "/member/roomSetPwd",
            dataType: "json",
            type: "POST",
            data: { "password": $("#rPwd").val(), "room-radio": $("[name='room-radio']:checked").val() },
            success: function(res){
                if (res.code == 1) {
                    $.tips(res.msg);
                }else{
                    $.tips(res.msg);
                };
            },

            error: function(res, text){
                if (window.console) {
                    console.log(text);
                    console.log("server error");
                };
            }
        });
    });

    //预约一对一房间，钻石数输入方式判断
    $('.J-onetoone-label').on("click", function(){
        var $thisRadio = $(this).find('[type="radio"]');
        var type = $(this).data("type");
        $thisRadio.prop("checked", true);

        //select和input的禁用和开启状态
        if ($("#radioSelectPoints").is(":checked")) {
            $("#oneToOne").find('select[name="select-points"]').prop("disabled", false);
            $("#oneToOne").find('input[name="input-points"]').prop("disabled", true);
        }else{
            $("#oneToOne").find('select[name="select-points"]').prop("disabled", true);
            $("#oneToOne").find('input[name="input-points"]').prop("disabled", false);
        };
        
    });

    //预约房间
    $('#btnOneToOne').on('click', function(){

        var dateVal = $("#oneToOne").find("input[name='mintime']").val();
        var hourVal = $("#oneToOne").find("select[name='hour']").val();
        var minuteVal = $("#oneToOne").find("select[name='minute']").val();

        if (!$.trim(dateVal).length) {
            $.tips("请选择开播日期。");
            return;
        };

        var splitDate = dateVal.split("-");
        var resDate = new Date(parseInt(splitDate[0], 10), parseInt(splitDate[1], 10) - 1 , parseInt(splitDate[2], 10), hourVal, minuteVal);

        if (resDate.getTime() < Date.now()) {
            $.tips("不能设置过去的时间。");
            return;
        };

        //输入值
        var selectPointsVal = "";
        var inputPointsVal = "";

        if ($("#radioSelectPoints").is(":checked")) {
            selectPointsVal = $("#oneToOne").find("select[name='select-points']").val();
            inputPointsVal = "";
        }else{
            selectPointsVal = "";
            inputPointsVal = $("#oneToOne").find("input[name='input-points']").val();
            if (Number(inputPointsVal) <= 10000) {
                $.tips("手动输入值必须大于10000");
                return;
            };
        }
        
        $.ajax({
            url: "/member/roomSetDuration",
            dataType: "json",
            type: "GET",
            data: {
                "tid": 4,
                "mintime": dateVal,
                "hour": $("#oneToOne").find("select[name='hour']").val(),
                "minute": $("#oneToOne").find("select[name='minute']").val(),
                "duration": $("#oneToOne").find("select[name='duration']").val(),
                "select-points": selectPointsVal,
                "input-points": inputPointsVal
            },
            success: function(res){
                if (res.code == 1) {
                    $.tips("预约房间（一对一）添加成功！", function(){
                        location.reload();
                    });
                }else{
                    $.tips(res.msg);
                };
            },

            error: function(res, text){
                if (window.console) {
                    console.log(text);
                    console.log("server error");
                };
            }
        });
    });


    //一对多房间
    $('#btnOneToMore').on('click', function(){

        var dateVal = $("#onToMore").find("input[name='mintime']").val();
        var hourVal = $("#onToMore").find("select[name='hour']").val();
        var minuteVal = $("#onToMore").find("select[name='minute']").val();

        if (!$.trim(dateVal).length) {
            $.tips("请选择开播日期。");
            return;
        };

        var splitDate = dateVal.split("-");
        var resDate = new Date(parseInt(splitDate[0], 10), parseInt(splitDate[1], 10) - 1 , parseInt(splitDate[2], 10), hourVal, minuteVal);

        if (resDate.getTime() < Date.now()) {
            $.tips("不能设置过去的时间。");
            return;
        };
        //输入值
        var inputPointsVal = Number($("#onToMore").find("input[name='points']").val());
        if(!Validation.isPositiveInteger(inputPointsVal)){
            $.tips("请输入正确的钻石数。");
            return;
        }else if(inputPointsVal < 300){
            $.tips("单场最低价不能低于300钻石");
            return;
        }

        $.ajax({
            url: "/member/roomOneToMore",
            dataType: "json",
            type: "GET",
            data: {
                "tid": 4,
                "mintime": dateVal,
                "hour": $("#onToMore").find("select[name='hour']").val(),
                "minute": $("#onToMore").find("select[name='minute']").val(),
                "duration": $("#onToMore").find("select[name='duration']").val(),
                "points": inputPointsVal
            },
            success: function(res){
                if (res.code == 1) {
                    $.tips("预约房间（一对多）添加成功！", function(){
                        location.reload();
                    });
                }else{
                    $.tips(res.msg);
                };
            },

            error: function(res, text){
                if (window.console) {
                    console.log(text);
                    console.log("server error");
                };
            }
        });
    });

    //时长房间
    $('#btnTimeCount').on('click', function(){
        var timeCount = Number($('#timeCount').val());
        //判断值得正确性，正整数
        if(!Validation.isPositiveInteger(timeCount)){
            $.tips('请输入一个数值！');
            return;
        }

        $.ajax({
            url: '/member/roomSetTimecost',
            data: {
                'timecost': timeCount
            },
            dataType: 'json',
            type: 'POST',
            success: function(json){
                if(json.code ==1){
                    $.tips('设置成功！');
                }else{
                    $.tips(json.msg);
                }
            },
            error: function(){

            }
        });
    });

    //删除功能
    $('#roomSetList-one').on("click", ".btn-reserve-delete", function(){
        var $thatBtn = $(this);
        $.ajax({
            url: "/member/delRoomDuration",
            data: {rid: $(this).data("roomid")},
            type: "GET",
            success: function(res){
                if (res.code == 1) {
                    $thatBtn.closest("tr").remove();
                    $.tips("删除成功！");

                }else{
                    $.tips(res.msg);
                };
            }
        });
    });

    //一对多删除功能
    $('#roomSetList-many').on("click", ".btn-reserve-delete", function(){
        var $thatBtn = $(this);
        $.ajax({
            url: "/member/delRoomOne2Many",
            data: {rid: $(this).data("roomid")},
            type: "GET",
            success: function(res){
                if (res.code == 1) {
                    $thatBtn.closest("tr").remove();
                    $.tips("删除成功！");

                }else{
                    $.tips(res.msg);
                };
            }
        });
    });

    //一对一修改功能
    $('#roomSetList-one').on("click", ".btn-reserve-modify", function(){

        var tmp = "<div class='m-form dialogRoomModify'>" +
            "<div class='m-form-item'>"+
                "<label for='resIptDate'>预约日期：</label>"+
                '<input id="resIptDate" class="Wdate txt txt-s" type="text" onclick="WdatePicker()" value="">'+
            "</div>"+
            "<div class='m-form-item'>"+
                "<label for='resSelectHour'>预约时间：</label>"+
                "<select id='resSelectHour'></select> : "+
                "<select id='resSelectMinute'></select>"+
            "</div>"+
            "<div class='m-form-item'>"+
                "<label for='resSelectDuration'>预约时长：</label>"+
                "<select id='resSelectDuration'></select>"+
            "</div>"+
            "<div class='m-form-item'>"+
                "<label for='resSelectPoints'>钻石数量：</label>"+
                "<select id='resSelectPoints'></select>"+
            "</div>"+
        "</div>";

        var $thatBtn = $(this);


        $.dialog({
            title: "修改房间设置",
            content: tmp,
            onshow: function(){

                //数据生成
                var hourOptions = "";
                var minuteOptions = "";

                for (var i = 0; i < 24; i++) {
                    if (i < 10) {
                        hourOptions = hourOptions + "<option>" + ("0" + i) + "</option>";
                    }else{
                        hourOptions = hourOptions + "<option>" + i + "</option>";
                    };
                };
                var hp = loopOptions({startNum: 0, endNum: 23});
                $("#resSelectHour").append(hp);

                var mp = loopOptions({startNum: 0, endNum: 59, interval: 5});
                $("#resSelectMinute").append(mp);

                var dp = loopOptions({startNum: 25, endNum: 55, interval: 30});
                $("#resSelectDuration").append(dp);

                var pp = loopOptions({startNum: 4000, endNum: 10000, interval: 1000});
                $("#resSelectPoints").append(pp);

                //数据回填
                var $td = $thatBtn.closest("tr").find("td");

                var date = $td.eq(1).text().split(" ")[0],
                    duration = $td.eq(2).text(),
                    points = $td.eq(3).text(),
                    timeHour = $td.eq(1).text().split(" ")[1].split(":")[0],
                    timeMinute = $td.eq(1).text().split(" ")[1].split(":")[1];

                var $dRoomModify = $(".dialogRoomModify")
                $dRoomModify.find("#resIptDate").val(date);
                $dRoomModify.find("#resSelectHour").val(timeHour);
                $dRoomModify.find("#resSelectMinute").val(timeMinute);
                $dRoomModify.find("#resSelectPoints").val(points);
                $dRoomModify.find("#resSelectDuration").val(duration);
            },
            ok: function(){
                $.ajax({
                    url: "/member/roomUpdateDuration",
                    data: { 
                        durationid: $thatBtn.data("roomid"),
                        mintime: $("#resIptDate").val(),
                        hour: $("#resSelectHour").val(),
                        minute: $("#resSelectMinute").val(),
                        points: $("#resSelectPoints").val(),
                        duration: $("#resSelectDuration").val()
                    },
                    type: "GET",
                    success: function(res){
                        if (res.code == 1) {
                            $.tips("修改成功！");
                        }else{
                            $.tips(res.msg);
                        };
                    },
                    error: function(res){

                    }
                });
            },
            okValue: "确定",
            cancel: function(){},
            cancelValue: "取消"
        }).show();


    });

    //一对多修改功能
    var oneToMoreDetailDialog = function(id){
        return $.dialog({
            id: "checkUserDetails",
            title: "查看详情",
            content: ['<table border="1" class="table" id="showDetailsTable">',
                '<tr>',
                '<th>创建时间</th>',
                '<th>一对多开始时间</th>',
                '<th>一对多结束时间</th>',
                '<th>预约会员ID</th>',
                '<th>预约会员昵称</th>',
                '<th>付款时间</th>',
                '<th>消费钻石数</th>',
                '</tr>',
                // '<tr>',
                //     '<td>创建时间</td>',
                //     '<td>一对多开始时间</td>',
                //     '<td>一对多结束时间</td>',
                //     '<td>预约会员ID</td>',
                //     '<td>预约会员昵称</td>',
                //     '<td>付款时间</td>',
                //     '<td>消耗金额（元）</td>',
                // '</tr>',
                '</table>'].join(""),
            fixed: false,
            height:"300px",
            onshow: function(){
                $("#checkUserDetails .d-bottom").hide();
                $.ajax({
                    url: "/member/getBuyOneToMore",
                    dataType:"json",
                    type: "GET",
                    data: {
                        onetomore: id
                    },
                    success: function(json){
                        for(var i in json.msg){
                            var tmp="<tr>"+
                                "<td>"+json.msg[i].created+"</td>"+
                                "<td>"+json.msg[i].starttime+"</td>"+
                                "<td>"+json.msg[i].endtime+"</td>"+
                                "<td>"+json.msg[i].uid+"</td>"+
                                "<td>"+json.msg[i].nickname+"</td>"+
                                "<td>"+json.msg[i].created+"</td>"+
                                "<td>"+json.msg[i].points+"</td>"+
                                +"</tr>";
                            $("#showDetailsTable").append(tmp);
                        }

                    },
                    error: function(){

                    }
                })
            }
            // ok:function(){
            //     // localStorage.setItem('adultAlert', 1);
            //     // alert(id);
            // },
            // cancel:function(){
            //     // location.href="http://google.com";
            // },
            // okValue: "",
            // cancelValue: "",
        });
    }

    $("#roomSetList-many").on("click", ".check-details", function () {
        var roomid = $(this).data('roomid');
        oneToMoreDetailDialog(roomid).show();
    })

    $('#rPwd').passwordInput('#rPwdTips');

});