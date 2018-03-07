/**
 * @description 充值页面
 * @author Young
 * @contacts young@kingjoy.co
 */

$(function () {
    //标记支付状态
    var markCharge = function (orderId, sucCb) {
        $.ajax({
            url: "/charge/checkCharge",
            data: {"orderId": orderId},
            dataType: "json",
            type: "GET",
            success: function (res) {
                if (sucCb) {
                    sucCb(res)
                }
                ;
            },
            error: function () {

            }
        });
    }
    //充值记录页面删除操作
    var delItem = function (d) {
        $.ajax({
            url: "/charge/del",
            type: "POST",
            data: {"lid": d.id},
            dataType: "json",
            success: function (json) {
                if (json.ret) {
                    $.tips(json.info);
                    d.target.closest("tr").remove();
                } else {
                    $.tips(json.info);
                }
                ;
            },
            error: function () {
                $.tips("删除失败");
            }
        });
    }

    //绑定充值记录页面事件
    $(document).on("click", ".charge-del", function () {
        var data = {};
        data.target = $(this);
        data.id = $(this).data("id");

        delItem(data);
    });

    $(document).on("click", ".charge-unknown", function () {
        var tradeNo = $(this).data("tradeno");
        var dialogFinishState = $.dialog({title: "提示", content: "正在获取状态，请稍等"}).show();

        markCharge(tradeNo, function (res) {
            $.tips(res.msg);
            dialogFinishState.remove();
        });
    });


    //充值页面点击按钮提交充值额度
    // $("#chargePay").on("click", function(){
    //
    // 	var $tipsBox = $(".charge-tips");
    // 	var inputPrice = $("#chargeIpt").val();
    //
    // 	var $chargePrice = $("input[name='price']");
    // 	var $chargeChannels = $(".chargePlat");
    // 	var $chargeChannel = $(".chargePlat:checked");
    // 	var $chargeType = $chargeChannel.parent('label');
    // 	var $chargeIptRadio = $("#chargeIptRadio");
    //
    // 	//充值金额
    // 	if (!$chargePrice.is(":checked")) {
    // 		$tipsBox.text("请选择要充值的金额");
    // 		return;
    // 	};
    //
    // 	//平台金额选择
    // 	var isChargeChannelChecked = false;
    // 	$.each($chargeChannels, function(i, e){
    //
    // 		if($(e).is(":checked") && $(e).is(":visible")) {
    // 			isChargeChannelChecked = true;
    // 			return false;
    // 		}
    //
    // 	});
    //
    // 	//充值渠道判断
    // 	if ($chargeChannels.length == 0 || !isChargeChannelChecked) {
    // 		$tipsBox.text("请选择充值渠道，若无充值渠道，请联系客服。");
    // 		return;
    // 	}
    //
    // 	//if ($chargeIptRadio.is(":checked") && (!$.isNumeric(inputPrice) || !/^[1-9]\d*$/.test(inputPrice) || parseInt(inputPrice, 10) < 10 || parseInt(inputPrice, 10) > 100000)) {
    // 	//	$tipsBox.text("请填写正确的金额");
    // 	//	return;
    // 	//};
    //
    // 	//radio 表单值
    //    var radioPrice = $chargePrice.filter(":checked").val();
    //
    //    //充值金额
    //    var price = Number(radioPrice) != -1 ? radioPrice : inputPrice;
    //
    //    //创建一个新窗口
    //    var newWindow = window.open();
    //
    //    //请求跳转链接key
    //    $.ajax({
    //        url: "/charge/pay",
    //        data: {
    //            "price": price,
    //            "vipLevel": $chargeChannel.val(),
    // 			"mode_type": $chargeType.data('id')
    //        },
    //        dataType: "json",
    //        type: "POST",
    //        success: function(res){
    // 			//是否已经提交成功
    //            if (!res.status) {
    //                var dialogPayState = $.dialog({
    //                    title: "支付状态",
    //                    id: "dialogChargeState",
    //                    content: "<p>充值金额：" + price + "元<br/></p>",
    //                    onshow: function(){
    //                    	$("#dialogChargeState").on("click", function(){
    //                    		markCharge(res.msg.orderId);
    //                    	});
    //                    },
    //                    okValue: "已完成支付",
    //                    ok: function(){
    //                    	//获取充值状态
    //                    	var makeSureMarkCharge = function(){
    //
    //                    		var dialogFinishState = $.dialog({title:"提示", content:"正在获取状态，请稍等"}).show();
    //
    //                         	markCharge(res.msg.orderId, function(json){
    //                         		//如果返回0，则能够获取状态，即成功，失败，或交易未知
    //                         		if(!json.status){
    //                         			location.href = "/member/charge";
    //                         		}else{
    //                         		//如果返回1，即程序内部问题
    //                         			$.dialog({
    //                         				title: "提示",
    //                         				content: json.msg,
    //                         				ok: function(){
    //                         					makeSureMarkCharge();
    //                         					return false;
    //                         				},
    //                         				okValue: "再次向系统确认",
    //                         				cancel: function(){},
    //                         				cancelValue: "关闭"
    //                         			}).show();
    //                         		}
    //
    //                         		dialogFinishState.remove();
    //                         	});
    //                    	}
    //
    //                    	makeSureMarkCharge();
    //                    }
    //                });
    //
    //                dialogPayState.show();
    //
    //                newWindow.location.href = res.msg.gotourl;
    //            }else{
    //            	$.tips(res.msg);
    //            };
    //
    //        },
    //        error: function(res){
    //            console.log(res);
    //        }
    //    });
    // });

    //每一个价格点击选项
    // $(".charge-label").on("click", function(){
    //
    // 	//页面传入recharge_money
    // 	var chargeVal = parseInt($(this).find("[name='price']").val(), 10);
    //
    // 	//充值组
    // 	var chargeTypes = [];
    //
    // 	//用户组
    // 	var currentChargeTypes = [];
    //
    // 	//用户组和充值组的交集
    // 	var comboChargeTypes = [];
    // 	//radio组
    // 	var $radios = $(".charge-box-hz").find("input[type=radio]");
    //
    // 	//获取支付通道区间
    // 	$.each(recharge_money, function(j, k){
    //
    // 		if (k.recharge_max >= chargeVal) {
    // 			//数据判空
    // 			if ($.trim(k.recharge_type) == "") {
    // 				chargeTypes = [];
    // 			}
    //
    // 			//如果为数组
    // 			if ($.isArray(k.recharge_type)) {
    // 				chargeTypes = $.merge([], k.recharge_type);
    // 				//chargeTypes数据处理
    // 				$.each(chargeTypes, function(m, n){
    // 					chargeTypes[m] = parseInt(n, 10);
    // 				});
    // 			}
    //
    // 			//如果为字符串数组
    // 			if (typeof k.recharge_type == "string") {
    //
    // 				chargeTypes = $.merge([], JSON.parse(k.recharge_type));
    // 				//chargeTypes数据处理
    // 				$.each(chargeTypes, function(m, n){
    // 					chargeTypes[m] = parseInt(n, 10);
    // 				});
    //
    // 			}
    //
    // 			//跳出区间选择
    // 			return false;
    // 		}
    //
    // 	});
    //
    // 	//获取当前充值通道数组
    // 	$.each($radios, function(i, e){
    // 		currentChargeTypes.push(parseInt($(e).closest("label").data("id"), 10));
    // 	});
    //
    // 	//获取
    // 	comboChargeTypes = Array.intersect(currentChargeTypes, chargeTypes);
    //
    // 	//如果充值金额不在后台配置范围内，出现空数组情况，显示全部充值渠道
    // 	if(!comboChargeTypes.length){
    // 		$("#chargePlat").find("label").hide();
    // 		$(".charge-tips").text('暂时没有充值渠道，请联系客服充值。');
    // 	}else{
    // 		//充值平台显示
    // 		for (var i = 0; i < comboChargeTypes.length; i++) {
    //
    // 			$radios.each(function(m, n){
    // 				if (parseInt($(n).closest("label").data("id"), 10) == comboChargeTypes[i]) {
    // 					//显示
    // 					$(n).closest("label").css({ "display": "inline-block" });
    // 					//移除第一个
    // 					comboChargeTypes.shift();
    // 					//return true;
    // 				}else{
    // 					//隐藏
    // 					$(n).closest("label").css({ "display": "none" });
    // 				}
    // 			});
    //
    // 		}
    //
    // 		$(".charge-tips").text('');
    // 	}
    // });

    //清除提示
    // $("[name='price']").on("click", function(){
    // 	$(".charge-tips").html("");
    // });

    // //添加active高亮
    // $(".charge-box").on("click", "label", function(){
    //     var $radio = $(this).find("input[type=radio]");
    //     if($radio.prop("checked")){
    //         $(this).siblings().removeClass("active");
    //         $(this).addClass("active");
    //     }
    // });
    //每一个价格点击选项
    var chargeLabel = $(".charge-label");
    var pay = {
        payAisle: $('.charge-box-hz'),
        defaultChannel: JSON.parse(decodeURI(chargeLabel.eq(0).find('input[type="hidden"]').val())),
        payType: JSON.parse(decodeURI($('.pay-data').val()))
    }

    pay.payAisle.find('label').hide();

    $.each(pay.defaultChannel, function(item, value) {
        pay.payType.filter(function(type) {
            if(type.cid == pay.defaultChannel[item].cid){
                $('.type-' + pay.defaultChannel[item].cid).show();
            }
        })
    })
    chargeLabel.on("click", function () {
        var payType = JSON.parse(decodeURI($(this).find('input[type="hidden"]').val()));
        pay.payAisle.find('label').hide();
        $.each(payType, function(item, value) {
            payType.filter(function(type) {
                if(type.cid == payType[item].cid){
                    $('.type-' + payType[item].cid).show();
                }
            })
        })

    })
    //添加支付通道Class
    var payList = {
        alipay:'支付宝',
        wechat: '微信',
        bankpay: '网银支付',
        transfer: '人工转账',
        qqpay: 'QQ钱包'

    };
    var inputLen = $('.charge-box-hz').find('input[name="vipLevel"]');
    for(var i = 0 ; i < inputLen.length; i++) {
        var inputIndex = $('input[name="vipLevel"]').eq(i);
        var labelIndex = inputIndex.parent('label');
        var text = $('input[name="vipLevel"]').eq(i).val();
        switch (text) {
            case payList.alipay :
                labelIndex.addClass('alipay');
                break;
            case payList.wechat :
                labelIndex.addClass('wechat');
                break;
            case payList.bankpay :
                labelIndex.addClass('bankpay');
                break;
            case payList.transfer :
                labelIndex.addClass('transfer');
                break;
            case payList.qqpay :
                labelIndex.addClass('qqpay');
                break;
            default:
                console.log('没有支付通道');
        }
    }

    //添加active高亮
    $(".charge-box").on("click", "label", function () {
        var $radio = $(this).find("input[type=radio]");
        if ($radio.prop("checked")) {
            $(this).siblings().removeClass("active");
            $(this).addClass("active");
        }
        var inputChecked = $("input[type='radio']:checked");
        $('#chargepay input[name="oid"]').val(inputChecked.val());
        $('#chargePlat label').click(function() {
            $('#chargepay input[name="cid"]').val($('#chargePlat input[type="radio"]:checked').parent().attr('data-id'));
            if($(this).attr('type') == '人工转账') {
                $('.input-transfer').animate({
                    opacity: 1
                })
            } else {
                $('.input-transfer').animate({
                    opacity: 0
                }).find('input[type="text"]').val('');
            }
        })
        $('.input-transfer').animate({
            opacity: 0
        }).find('input[type="text"]').val('');
    });

    var inputChecked = $("input[type='radio']:checked");
    $('#chargepay input[name="oid"]').val(inputChecked.val());
    $('#chargePlat label').click(function() {
        $('#chargepay input[name="cid"]').val($('#chargePlat input[type="radio"]:checked').parent().attr('data-id'));
        if($(this).attr('type') == '人工转账') {
            $('.input-transfer').animate({
                opacity: 1
            })
        } else {
            $('.input-transfer').animate({
                opacity: 0
            }).find('input[type="text"]').val('');
        }
    })
    $('.input-transfer').find('input[type="text"]').blur(function() {
        $('input[name="remark"]').val($(this).val())
    })



    //url带msg参数
    if (!!getLocation("msg")) {
        $.tips(getLocation("msg"));
    }
    ;

    //url带radioprice参数
    if (!!getLocation("radioprice")) {
        var price = getLocation("radioprice");
        var $radio = $("[type=radio][value=" + price + "]");
        var $input = $("#chargeIpt");
        if ($radio.length) {
            $radio.prop("checked", true);
        } else {
            $("#chargeIptRadio").prop("checked", true);
            $input.val(price);
        }
        ;

    }
    ;

    //选择自行输入选项，焦点聚焦到input
    $("#chargeIptBox").on("click", function () {
        $("#chargeIpt").focus();
        $(".charge-box").find("label").removeClass("active");
    });

    $("#chargeIpt").on("click", function () {

        $("#chargeIptRadio").prop("checked", true);

    }).on("keyup", function () {

        var num = 0;
        var val = $(this).val();
        var $inputBox = $(this).parent("span").siblings(".charge-diamond");
        var $tipsBox = $(".charge-tips");

        //最多充值100000元
        if ($.isNumeric(val) && parseInt(val, 10) > 100000) {

            num = parseInt(val, 10);
            $inputBox.text((num * 10) + "钻石");
            $tipsBox.text("最多充值100000元");

            //最少充值10元
        } else if ($.isNumeric(val) && parseInt(val, 10) < 10) {

            $tipsBox.text("最少充值10元");

            //符合标准
        } else if ($.isNumeric(val) && /^[1-9]\d*$/.test(val) && parseInt(val, 10) >= 10 && parseInt(val, 10) <= 100000) {

            num = parseInt(val, 10);
            $inputBox.text((num * 10) + "钻石");
            $tipsBox.text("");

            //不可输入0，小数，负数，字母和特殊符号
        } else {

            $inputBox.text("");
            $tipsBox.text("不可输入0，小数，负数，字母和特殊符号");

        }
        ;

    });

});