/**
 * @description 安全邮箱确认相关页面
 * @author Young
 * @contacts young@kingjoy.co
 */

/**
 * @description 将时间戳转换为分钟和秒钟
 * @param msd: 时间戳
 * @return tn: string 分钟和秒钟
 * @author Young
 * @contacts young@kingjoy.co
 */

function millisecondToDate(msd) {
    var minutes = Math.floor(msd / 60 / 1000);
    var seconds = parseInt((msd - minutes * 60 * 1000) / 1000);
    var tn = "";
    if (minutes == 0) {
        tn = seconds + "s";
    } else {

        if (minutes != 10) {
            minutes = "0" + minutes;
        }

        tn = minutes + "'" + seconds + "s";
    }
    ;
    return tn;
}


function timeCount(time) {
    time = parseInt(time)
    if (isNaN(time) || time < 0)
        time = 0
    var $mailVerificBtn = $("#resendMail");

    //var $sendBtn = $(".mail-btn-box").find(".btn");
    var $mailIpt = $(".sMail_2");

    $mailVerificBtn.addClass("btn-disabled").removeClass("btn-orange");
    $mailVerificBtn.prop("disabled", true);
    $mailIpt.prop("disabled", true);
    var timeCount = setInterval(function () {
        if (time <= 0) {
            $mailVerificBtn.removeClass("btn-disabled").addClass("btn-orange");
            $mailVerificBtn.attr('style', null);
            $mailVerificBtn.prop("disabled", false);
            $(".mail-btn-tips-time").text("");
            $mailIpt.prop("disabled", false);
            sessionStorage.removeItem('EmailCountDown');
            clearTimeout(timeCount);
        } else {
            var timeNow = millisecondToDate(time);
            $(".mail-btn-tips-time").text(timeNow);
            time = time - 1000;
            sessionStorage.setItem('EmailCountDown', time);
        }
    }, 1000);
}

$(function () {
    var $btn = $(".mail-btn-box").find(".btn");

    //验证邮箱
    var $mailIpt = $(".mail").find('.mail-ipt');
    $mailIpt.accountInput(".mail-ipt-tips");

    //验证第二页的邮箱
    var $mailIpt = $(".mail").find('#sMail');
    $mailIpt.accountInput(".mail-resend-tips");

    //var $pwdIpt = $(".mail").find("#newPwd");
    //var $pwdIptAgain = $(".mail").find("#newPwdAgain");
    //
    //$pwdIpt.passwordInput(".pwd-ipt-tips");
    //$pwdIptAgain.passwordAgain("#newPwd", ".pwdagain-ipt-tips");


    $("#return_index").on("click", function () {
        $.tips("您还未完成邮箱验证")
    })


    if ($("#pagePwdSend").length) {
        $("#pwdSendBtn").on("click", function () {
            $("#getPwd").trigger("focus");
            if ($(".mail-ipt-tips").text().length == 0) {
                $("#pwdSendForm").submit();
            }
            ;
        });
    }
    ;

    if ($("#pagePwdChange").length) {
        $("#pwdChangeBtn").on("click", function () {
            $("#newPwd").trigger("focus");
            $("#newPwdAgain").trigger("focus");

            if ($(".pwd-ipt-tips").text().length == 0 && $(".pwdagain-ipt-tips").text().length == 0) {
                $("#pwdChangeForm").submit();
            }
            ;
        });
    }
    ;

    if ($("#pageMailVerific").length) {
        $("#mailVerificBtn").on("click", function () {
            $("#sMail").trigger("focus");

            var sMailVal = $.trim($("#sMail").val());

            if (!sMailVal.length) {
                $(".mail-ipt-tips").text("安全邮箱不得为空")
                return
            }

            $.ajax({
                url: '/sendVerifyMail',
                type: "post",
                data: {
                    mail: sMailVal,
                },
                success: function (data) {
                    if (data.status != 1) {
                        $.tips(data.msg);
                        return
                    }

                    if ($(".mail-ipt-tips").text().length == 0) {
                        sessionStorage.setItem('emailVal', $("#sMail").val());
                        sessionStorage.setItem('EmailCountDown', data.countDown);
                        if (sessionStorage.getItem('emailVal') !== null) {
                            location.href = '/mailsend';
                        }
                    }
                }
            })
        });
    }

    //发送请求..具体参数..
    var sendMail = function (emailVal) {

        // if(!sessionStorage.getItem('EmailCountDown')){
        // 	sessionStorage.setItem('EmailCountDown',60000);
        // }

        $.ajax({
            url: '/sendVerifyMail',
            type: "post",
            data: {
                mail: emailVal,
            },
            success: function (data) {
                timeCount(data.countDown > 0 ? data.countDown : 0)
                //$("#emailRemind").html(data.msg);
                $.tips(data.msg);
            }
        })
    }


    var emailVal = sessionStorage.getItem('emailVal');
    var countDown = sessionStorage.getItem('EmailCountDown')
    countDown = countDown ? countDown : 0
    timeCount(countDown)
    $('#sMail').val(emailVal)

    //再次发送..
    $("#resendMail").on("click", function () {
        if (!$(this).prop("disabled")) {
            $("#sMail").trigger("focus");
            var mailVal = $.trim($("#sMail").val());
            if (!mailVal.length) {
                //$(".mail-ipt-tips").text("安全邮箱不得为空")
                $.tips("安全邮箱不得为空");
            }
            mailVal && $(".mail-resend-tips").text().length == 0 && sendMail(mailVal);
        }
    });

});