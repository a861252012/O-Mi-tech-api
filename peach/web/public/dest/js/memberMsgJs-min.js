/*! cross - v0.1.2 - 2018-02-05 */$(function(){var a=$.dialog({title:"发私信给",content:['<div class="msg-reply">','<textarea name="" id="txtContent" rows="10" class="textarea"></textarea>','<div class="tool clearfix">','<span class="tool-tips">',"还能输入",'<span class="tool-num">200</span>',"字","</span>",'<button class="btn">发送</button>',"</div>","</div>"].join(""),onshow:function(){var a=this.buttonTarget.closest("li").data("store"),b=a.fid,c=$(".msg-reply"),d=c.find(".tool-num"),e=$("#txtContent"),f=this;e.val(""),wordsCount(e,d,200),f.setTitle("发私信给"+a.nickname);var g;c.off("click",".btn"),c.on("click",".btn",function(){return 0==$.trim(e.val()).length?($.tips("发送私信的内容不能为空"),void f.remove()):(g&&4!=g.readyState&&g.abort(),void(focusXHR=$.ajax({url:"/member/domsg",type:"POST",dataType:"json",data:{content:e.val(),tid:b,fid:User.UID},success:function(a){a.ret?(f.remove(),$.tips("私信发送成功")):alert(a.info)}})))})}});$("#personMenuTab").on("click",".displayWinBtn",function(){a.setBtnTarget($(this)),a.show()}),$("#personMenuTab").on("click",".list-tool-delete",function(){var a=$(this).parents("li"),b=a.data("store");$.ajax({url:"/majax/delmsg",type:"POST",dataType:"json",data:b,success:function(b){b.ret?a.remove():alert(b.info)}})}),$("#sysMsg").on("click",".list-tool-delete",function(){var a=$(this).closest("li"),b=a.data("store");$.ajax({url:"/majax/delmsg",type:"POST",dataType:"json",data:b,success:function(b){b.ret?a.remove():alert(b.info)}})})});