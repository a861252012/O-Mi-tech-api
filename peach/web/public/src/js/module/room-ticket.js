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