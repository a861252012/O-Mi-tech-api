/**
 * @description 个人中心关注页面
 * @author Young
 * @contacts young@kingjoy.co
 */

$(function(){
    
    //取消关注
    $('.watch-btn').on("click", function(){
        var $this = $(this);
        
        $.ajax({
            url: '/focus',
            data: { pid: $this.parents('.l-list').data('rel'), ret: 2 },
            type: "GET",
            dataType: "json",
            success: function(json){
                if (json.status) {
                    $this.parents('.l-list').remove();
                }else{
                    alert(json.msg);
                };
            },
            error: function(){

            }
        });
    });


});

