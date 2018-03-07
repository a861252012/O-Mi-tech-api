$(function(){
    var user = new User();
    user.bindLoginEvent();


    $(".forget-pw").on("click", function(){
    	$.tips("请联系客服！");
    });
});