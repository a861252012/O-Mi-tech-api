/*! cross - v0.1.2 - 2018-02-05 */function request(a){for(var b=window.location.href.replace(/[><'"]/g,""),c=b.substring(b.indexOf("?")+1,b.length).split("&"),d={},e=0;j=c[e];e++)d[j.substring(0,j.indexOf("=")).toLowerCase()]=j.substring(j.indexOf("=")+1,j.length);var f=d[a.toLowerCase()];return"undefined"==typeof f?"":f}function setAgent(){var a=request("agent");a.length>0&&$.cookie("agent",a)}!function(a){function b(a){return h.raw?a:encodeURIComponent(a)}function c(a){return h.raw?a:decodeURIComponent(a)}function d(a){return b(h.json?JSON.stringify(a):String(a))}function e(a){0===a.indexOf('"')&&(a=a.slice(1,-1).replace(/\\"/g,'"').replace(/\\\\/g,"\\"));try{return a=decodeURIComponent(a.replace(g," ")),h.json?JSON.parse(a):a}catch(b){}}function f(b,c){var d=h.raw?b:e(b);return a.isFunction(c)?c(d):d}var g=/\+/g,h=a.cookie=function(e,g,i){if(void 0!==g&&!a.isFunction(g)){if(i=a.extend({},h.defaults,i),"number"==typeof i.expires){var j=i.expires,k=i.expires=new Date;k.setTime(+k+864e5*j)}return document.cookie=[b(e),"=",d(g),i.expires?"; expires="+i.expires.toUTCString():"",i.path?"; path="+i.path:"",i.domain?"; domain="+i.domain:"",i.secure?"; secure":""].join("")}for(var l=e?void 0:{},m=document.cookie?document.cookie.split("; "):[],n=0,o=m.length;n<o;n++){var p=m[n].split("="),q=c(p.shift()),r=p.join("=");if(e&&e===q){l=f(r,g);break}e||void 0===(r=f(r))||(l[q]=r)}return l};h.defaults={},a.removeCookie=function(b,c){return void 0!==a.cookie(b)&&(a.cookie(b,"",a.extend({},c,{expires:-1})),!a.cookie(b))}}(jQuery),function(a){"function"==typeof define&&define.amd?define(["jquery"],a):a(jQuery)}(function(a){"use strict";var b=function(b){var c=b.$dialog,d=a(window),e=0,f=0,g=d.width(),h=d.height(),i=c.width(),j=c.height(),k=(g-i)/2+e,l=382*(h-j)/1e3+f,m=c[0].style;m.left=Math.max(parseInt(k),e)+"px",m.top=Math.max(parseInt(l),f)+"px"},c=function(a){var b=a.$dialog,c=b.width(),d=b.height(),e=b[0].style;e.left="50%",e.marginLeft="-"+c/2+"px",e.top="50%",e.marginTop="-"+d/2+"px"},d=function(b,c,d){var e;if(a.support.checkOn){e=[];var f,g=b.length;for(f=0;f<g;f++)e.push(b[f]);e=e.join("")}else e=b;var h=new RegExp("#{([a-z0-9_]+)}","ig");return e=e.replace(h,function(a,b,e,f){return c[b]?c[b]:d?a:""})},e=function(a){this.$main,this.$dialog,this.$shadow,this.$closeBtn,this.$buttonBox,this.options,this.originalOptions,this.buttonTarget,this.onshow,this.init(a)},f=0,g=['<div class="d-dialog">','<div class="d-wrapper">','<div class="d-close"></div>','<div class="d-main">','<div class="d-title">#{title}</div>','<div class="d-content">#{content}</div>','<div class="d-bottom"></div>',"</div>","</div>","</div>",'<div class="d-shadow"></div>'].join("");e.DEFAULTS={id:new Date-0+f,title:"Dialog",content:"这是Dialog",width:"auto",height:"auto",okValue:"确定",cancelValue:"取消",closeButtonDisplay:!0,cancelDisplay:!0,cancelTextBtn:!1,buttonTarget:null,fixed:!1,autofocus:!0},a.extend(e.prototype,{init:function(b){this.options=this.getOptions(b),this.originalOptions=this.options;var c=d(g,this.options),e=this.options.id,h=this;this.$main=a(c),this.$closeBtn=this.$main.find(".d-close"),this.$dialog=this.$main.siblings(".d-dialog"),this.$shadow=this.$main.siblings(".d-shadow"),this.$buttonBox=this.$main.find(".d-bottom"),this.$dialog.attr("id",e),a(document).on("click",".d-close",function(a){h.remove(),a.stopPropagation()}),f++},create:function(){this.options=this.getOptions(this.originalOptions),a.isArray(this.options.button)||(this.options.button=[]),this.options.title||this.$main.find(".d-title").remove(),this.options.ok&&this.options.button.push({id:"ok",className:"btn",value:this.options.okValue,callback:this.options.ok,autofocus:!0}),this.options.cancel&&this.options.button.push({id:"cancel",className:"btn btn-white",value:this.options.cancelValue,callback:this.options.cancel,display:this.options.cancelDisplay,cancelTextBtn:this.options.cancelTextBtn}),this.options.closeButtonDisplay?this.$closeBtn.show():this.$closeBtn.hide(),this.setButton(this.options.button),this.options.button.length||this.$main.find(".d-bottom").remove()},getDefaults:function(){return e.DEFAULTS},getOptions:function(b){return a.extend(!0,{},this.getDefaults(),b)},setData:function(a){return this.data=a,this},show:function(){this.create(),a("body").append(this.$main),this.options.onshow&&(this.onshow=this.options.onshow,this.onshow()),this.options.fixed?b(this):c(this),this.$dialog.show(),this.$shadow.show();var d=this.$dialog.find("input, textarea, select").not("input[type='button']"),e=this.$dialog.find("input[type='button'], input[type='submit'], button, a");return setTimeout(function(){d.length?d[0].focus():e[0]&&e[0].focus()},0),this},close:function(){return this.$main.hide(),this},remove:function(){return this.$main.remove(),delete a.dialog.list[this.id],this.options.onremove&&this.options.onremove(),this},setButton:function(b){b=b||[];var c=this,d="",e=0;return this.callbacks={},"string"==typeof b?(d=b,e++):a.each(b,function(b,f){var g=f.id=f.id||f.value,h="",i=f.cancelTextBtn?"btn-leave":f.className;c.callbacks[g]=f.callback,f.display===!1?h=' style="display:none"':e++,d+='<button type="button" class="'+i+'" i-id="'+g+'"'+h+(f.disabled?" disabled":"")+(f.autofocus?' autofocus class="ui-dialog-autofocus"':"")+">"+f.value+"</button>",c.$buttonBox.on("click","[i-id="+g+"]",function(b){var d=a(this);d.attr("disabled")||c._trigger(g),b.preventDefault()})}),this.$buttonBox.html(d),this},setTitle:function(a){return this.$main.find(".d-title").text(a),this},setBtnTarget:function(a){return this.buttonTarget=a,this},focus:function(){},blur:function(){},_trigger:function(a){var b=this.callbacks[a];return"function"!=typeof b||b.call(this)!==!1?this.close().remove():this}}),a.dialog=function(b){var c=e.DEFAULTS.id;return b.id&&(c=b.id),a.dialog.list[c]=new e(b)},a.dialog.list={},a.dialog.get=function(b){return void 0===b?a.dialog.list:a.dialog.list[b]},a.tips=function(b,c){var d=a.dialog({title:"提示",content:b,cancel:function(){},cancelValue:"关闭",onremove:function(){c&&c()}});d.show()}}),function(a,b){$.extend(a,{isEmail:function(a){var b=/^(([^<>()[\]\\.,#$\^\&\%\*\-+!;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;return b.test(a)},isAccount:function(a){var b=/^[a-zA-Z0-9_\u4e00-\u9fa5]{2,8}$/;return b.test(a)},isLoginAccount:function(a){var b=/^[a-zA-Z0-9_\u4e00-\u9fa5]{6,16}$/;return b.test(a)},isPost:function(a){var b=/^[a-zA-Z0-9 ]{3,12}$/;return!!b.exec(a)},isMobile:function(a){var b=/^(13[0-9]|15[012356789]|18[0-9]|14[57]|00852)[0-9]{8}$/;return b.test(a)},isTel:function(a){var b=/^[+]{0,1}(\d){1,3}[ ]?([-]?((\d)|[ ]){1,12})+$/;return!!b.exec(a)},isQQ:function(a){return/^\d{5,11}$/.test(a)},isPhoneNum:function(a){return/^\d{5,20}$/.test(a)},isBankNum:function(a){return/^\d{6,19}$/.test(a)},isBankName:function(a){return this.isChinese(a)&&/^.{1,6}$/.test(a)},isDate:function(a){var b=/^((((1[6-9]|[2-9]\d)\d{2})-(0?[13578]|1[02])-(0?[1-9]|[12]\d|3[01]))|(((1[6-9]|[2-9]\d)\d{2})-(0?[13456789]|1[012])-(0?[1-9]|[12]\d|30))|(((1[6-9]|[2-9]\d)\d{2})-0?2-(0?[1-9]|1\d|2[0-8]))|(((1[6-9]|[2-9]\d)(0[48]|[2468][048]|[13579][26])|((16|[2468][048]|[3579][26])00))-0?2-29-))$/;return!!b.test(a)},isIdCardNo:function(a){var b=/^(\d{6})(18|19|20)?(\d{2})([01]\d)([0123]\d)(\d{3})(\d|X)?$/;return!!b.test(a)},isIP:function(a){var b=/^((2[0-4]\d|25[0-5]|[01]?\d\d?)\.){3}(2[0-4]\d|25[0-5]|[01]?\d\d?)$/;return!!b.test(a)},isPasswd:function(a){var b=/^[a-zA-Z0-9]{6,15}$/;return!!b.exec(a)},isChinese:function(a){var a=a.replace(/(^\s*)|(\s*$)/g,"");return!(!/^[\u4E00-\uFA29]*$/.test(a)||/^[\uE7C7-\uE7F3]*$/.test(a))},isChEn:function(a){var b=/^[a-zA-Z\u4E00-\u9FA5]{2,16}$/;return!!b.exec(a)},isImg:function(a){var b=new RegExp("[.]+(jpg|jpeg|png|swf|gif)$","gi");return!!b.test(a)},isInteger:function(a){return/^-?\d+$/.test(a)},isFloat:function(a){return/^(-?\d+)(\.\d+)?$/.test(a)},isPositiveInteger:function(a){return/^\d+$/.test(a)}}),b.Validation=a}("undefined"!=typeof Validation?Validation:{},window),function(a,b){$.extend(a,{log:function(a){b.console&&void 0},template:function(a,b,c){var d;if($.support.checkOn){d=[];var e,f=a.length;for(e=0;e<f;e++)d.push(a[e]);d=d.join("")}else d=a;var g=new RegExp("#{([a-z0-9_]+)}","ig");return d=d.replace(g,function(a,d,e,f){return b[d]?b[d]:c?a:""})},switchOrigin:function(a,b){return"http://"+location.host.replace(a,b)},tabSwitch:function(a,b){0==arguments.length&&(a=$("body")),a.find(".J-tab-menu li:not(.close)").on("click",function(){var a=$(this).closest(".J-tab");$(this).parent().children("li").removeClass("active"),$(this).addClass("active");var c=a.find(".J-tab-main");c.removeClass("active"),c.eq($(this).index()).addClass("active"),b&&b()})}}),b.Utility=a}("undefined"!=typeof Utility?Utility:{},window),function(a,b){"function"==typeof define&&define.amd?define(["../vendor/jquery/jquery.min.js"],b):"object"==typeof module&&module.exports?module.exports=b():this[a]=b(jQuery)}("Captcha",function(a){var b=function(){this.flashCaptcha=function(a){var b=(new Date).valueOf();a.attr("src","/captcha?t="+b)},this.bindChangeCaptcha=function(){var b=this;a(".J-change-scode").on("click",function(){var c=a(this).siblings(".s-code-img");b.flashCaptcha(c)})}};return b}),function(a,b){var c,d=new Captcha;a=function(){this.setRegErrorTips=function(a){$(".rTip").text(a).css("color","#c1111c")},this.setLoginErrorTips=function(a){$(".lTip").text(a).css("color","#c1111c")},this.submitR=function(a,c){var e=this,f=$("#rName"),g=$("#rNickname"),h=$("#rPassword"),i=$("#rAPassword"),j=$("#rsCodeIpt");$(a).off("click"),$(a).on("click",function(){if(0==f.val().length)return void e.setRegErrorTips("请输入登录邮箱！");if(!Validation.isEmail(f.val()))return void e.setRegErrorTips("邮箱格式不正确，且注册邮箱不能使用中文/:;\\空格，等特殊符号！");if(0==g.val().length)return void e.setRegErrorTips("请输入昵称！");if(!Validation.isAccount(g.val()))return void e.setRegErrorTips("注册昵称不能使用/:;\\空格,等特殊符号！(2-8位的昵称)");if(0==h.val().length)return void e.setRegErrorTips("请输入密码！");if(0==i.val().length)return void e.setRegErrorTips("请输入确认密码！");if(0!=h.val().length&&0!=i.val().length&&h.val()!=i.val())return void e.setRegErrorTips("两次密码输入不同！");if(0==j.val().length)return void e.setRegErrorTips("请输入验证码！");var a=$.trim(h.val()),k=$.trim(i.val());UserService.actionRegister({username:f.val(),nickname:g.val(),password:a,repassword:k,captcha:j.val(),sucCallback:function(a){b.IFRAME_LOGIN_STATE&&IFRAME_LOGIN_STATE.regSuc(),c&&c()},errCallback:function(a){e.setRegErrorTips(a.msg),d.flashCaptcha($("#rsCodeImg")),Utility.log(a.msg)}})}),$("#rsCodeIpt, #rAPassword, #rPassword, #uid, #rName").on("keydown",function(b){"13"==b.keyCode&&$(a).trigger("click")})},this.submitL=function(a){var c=this,e=$("#lName"),f=$("#lPassword"),g=$("#lsCodeIpt"),h=$("#lAuto");$(a).off("click"),$(a).on("click",function(){var a=$.trim(e.val()),i=$.trim(f.val()),j=$.trim(g.val());return 0==a.length?void c.setLoginErrorTips("请输入登录邮箱！"):0==i.length?void c.setLoginErrorTips("请输入登录密码！"):g.is(":hidden")||0!=j.length?void UserService.actionLogin({username:a,password:i,captcha:j,remember:h.prop("checked")?1:0,sucCallback:function(a){b.IFRAME_LOGIN_STATE&&IFRAME_LOGIN_STATE.loginSuc(),1==a.status&&(b.location.href=b.location.href)},errCallback:function(a){c.setLoginErrorTips(a.msg),e.afterIcon(vcIconWarnTMP),f.afterIcon(vcIconWarnTMP),d.flashCaptcha($("#lsCodeImg")),Utility.log(a.msg)}}):void c.setLoginErrorTips("请输入验证码！")}),$("#lsCodeIpt, #lPassword, #lName").on("keydown",function(b){"13"==b.keyCode&&$(a).trigger("click")})},this.getUserInfoSuccess=function(b,c){var d=b.info,e=d.hidden;if("undefined"!=typeof e)var f=parseInt(e,10)?"隐身":"在线",g=parseInt(e,10)?"dropdown-title-hidden":"dropdown-title-online",h=["<div class='loginDropdown dropdown' id='loginDropdown'>","<div class='dropdown-title "+g+"'><div class='dropdown-title-text'>"+f+"</div><span class='dropdown-tri'></span></div>","<div class='dropdown-list'>","<div class='dropdown-item' id='loginOnline' data-value='0'>在线</div>","<div class='dropdown-item' id='loginHide' data-value='1'>隐身</div>","</div></div>"].join("");$(".user-por").append(h);new Dropdown({id:"loginDropdown",handleItem:function(a){$.ajax({url:"/member/hidden/"+a.getAttribute("data-value"),type:"get",dataType:"json",data:"",success:function(b){if(1==b.status){var c=parseInt(a.getAttribute("data-value")),d=$("#loginDropdown").find(".dropdown-title");0==c?d.addClass("dropdown-title-online").removeClass("dropdown-title-hidden"):d.addClass("dropdown-title-hidden").removeClass("dropdown-title-online")}else console},error:function(){Utility.log("hidden set error(500)!")}})}});a.UID=d.uid,a.UL=parseInt(d.lv_rich,10),a.POINTS=d.points,a.INFO=d,a.DOWNLOADURL=b.downloadUrl,a.MY_TICKET=b.myticket,a.MY_RES=b.myres,c&&c(b)},this.bindLoginEvent=function(){d.bindChangeCaptcha(),$("#lName").accountInput(".lTip"),$("#lPassword").passwordInput(".lTip"),$("#lsCodeIpt").sCodeInput(".lTip"),c.submitL(".lButton")},this.init=function(){c=this},this.init()},$.extend(a,{UID:$.cookie("webuid"),IMG_URL:[],QRCODE_IMG:[],DOWNLOADAPPURL:[],MY_TICKET:[],MY_RES:[],loginDialog:$.dialog({id:"loginDialog",title:"用户中心",content:['<div class="J-dialog-tab J-tab">','<ul class="tab-title J-tab-menu clearfix" style="list-style: none;">','<li class="tab-item active J-tab-menu-item" id="dialogLogin">',"<h3>用户登录</h3>","</li>",'<li class="tab-item J-tab-menu-item" id="dialogRegister">',"<h3>用户注册</h3>","</li>","</ul>",'<div class="J-tab-main active">','<div class="m-form-wrapper">','<form action="" class="lForm m-form" onSubmit="return false">','<div class="m-form-item">','<label for="lName">登录邮箱：</label>','<input type="text" class="txt" id="lName" tabIndex="1" placeholder="您的登录邮箱" />',"</div>",'<div class="m-form-item">','<label for="lPass word">登录密码：</label>','<input type="password" class="txt" id="lPassword" tabIndex="2" autocomplete="off" placeholder="您的密码"/>',"</div>",'<div class="m-form-item">','<label for="lsCodeIpt">验&nbsp;&nbsp;证&nbsp;&nbsp;码：</label>','<input type="text" class="txt txt-short" id="lsCodeIpt" tabIndex="3" placeholder="不区分大小写"/>','<img src="" alt="验证码" id="lsCodeImg" class="s-code-img" />','<a href="javascript:void(0);" class="m-form-tip J-change-scode">换一换</a>',"</div>",'<div class="m-form-item clearfix">','<a href="/getpwd" target="_blank" class="forget-pw" title="忘记密码怎么办？点我">',"<span>忘记密码</span>",'<span class="i-vc i-vc-help"></span>',"</a>","</div>",'<div class="lTip"></div>',"</form>","</div>",'<div class="m-form-btnbox clearfix">','<button class="btn lButton btn-left" tabIndex="4">登 录</button>','<button class="btn rButtonSwitch btn-white btn-right" tabIndex="5">注 册</button>',"</div>","</div>",'<div class="J-tab-main">','<div class="m-form-wrapper">','<form action="" class="rForm m-form" onSubmit="return false">','<input type="text" style="display: none;" autocomplete="off"/>','<input type="password" style="display: none;" autocomplete="off"/>','<div class="m-form-item">','<label for="rName">登录邮箱：</label>','<input type="text" class="txt" id="rName" tabIndex="1" placeholder="填写您的邮箱地址"/>',"</div>",'<div class="m-form-item">','<label for="rNickname">您的昵称：</label>','<input type="text" class="txt" maxlength="16" id="rNickname" tabIndex="2" placeholder="2-8位汉字、数字或字母组成"/>',"</div>",'<div class="m-form-item">','<label for="rPassword">登录密码：</label>','<input type="password" class="txt" id="rPassword" tabIndex="3" autocomplete="off" placeholder="6-22个字母和数字组成"/>',"</div>",'<div class="m-form-item">','<label for="rAPassword">确认密码：</label>','<input type="password" class="txt" id="rAPassword" tabIndex="4" autocomplete="off"/>',"</div>",'<div class="m-form-item">','<label for="rsCodeIpt">验&nbsp;&nbsp;证&nbsp;&nbsp;码：</label>','<input type="text" class="txt txt-short" id="rsCodeIpt" tabIndex="5" placeholder="不区分大小写"/>','<img src="" alt="验证码" id="rsCodeImg" class="s-code-img" />','<a href="javascript:void(0);" class="m-form-tip J-change-scode">换一换</a>',"</div>",'<span class="rTip"></span>',"</form>","</div>",'<div class="m-form-btnbox">','<button class="btn btn-register rButton" tabIndex="6">立即注册，马上去看</button>',"</div>","</div>",'<div class="d-gg">','<a href="" target="_blank" class="d-gg-a">','<img class="d-gg-img" src="">',"</a>","</div>","</div>"].join(""),onshow:function(){var a=cross.make("User");return $("#dialogLogin, #dialogRegister").on("click",function(a){"dialogLogin"==a.currentTarget.id?d.flashCaptcha($("#lsCodeImg")):"dialogRegister"==a.currentTarget.id&&d.flashCaptcha($("#rsCodeImg"))}),$(".rButtonSwitch").on("click",function(){$("#dialogRegister").trigger("click")}),Utility.tabSwitch($(".J-dialog-tab")),d.bindChangeCaptcha(),$("#lName").accountInput(".lTip"),$("#lPassword").passwordInput(".lTip"),$("#lsCodeIpt").sCodeInput(".lTip"),$("#rName").accountInput(".rTip"),$("#rNickname").isNickname(".rTip"),$("#rPassword").regPasswordInput(".rTip"),$("#rsCodeIpt").sCodeInput(".rTip"),$("#rAPassword").passwordAgain("#rPassword",".rTip"),a.submitR(".rButton",function(){b.location.reload()}),a.submitL(".lButton"),User.info&&User.info.gg&&User.info.gg.login_ad?($(".d-gg-a").attr("href",User.info.gg.login_ad.link),void $(".d-gg-img").attr({title:User.info.gg.login_ad.title,src:User.info.gg.login_ad.img})):void $(".d-gg").remove()}}),isConnection:function(){return!!$.cookie("webuid")||!!$.cookie("v_remember_encrypt")||!!$.cookie("PHPSESSID")},isLogin:function(){return!!this.UID},loginSuccess:function(){},handleAfterGetUserInfo:function(){},getUserInfo:function(a){var b=this;$.ajax({url:"/indexinfo",type:"GET",dataType:"json",cache:!1,success:function(c){a&&c.ret&&a(c),User.DOWNLOADAPPURL=c.downloadAppurl,User.IMG_URL=c.img_url,User.QRCODE_IMG=c.qrcode_img,b.handleAfterGetUserInfo()},error:function(a){Utility.log("server error!")}})},flashUserInfo:function(){User.getUserInfo(function(b){user.getUserInfoSuccess(b,function(b){var c=function(a){for(var b=[],c=[],d=[],e=0;e<a.length;e++)switch(a[e].live_status){case 0:d.push(a[e]);break;case 1:c.push(a[e]);break;case 2:b.push(a[e]);break;default:d.push(a[e])}return b.concat(c,d)},d="",e="";0!=b.myres.length||0!=b.myticket.length?(d=renderOrdItem(c(b.myres)),e=renderOneToMoreItem(c(b.myticket))):d='<div class="main-tips">您暂时还没有预约主播哟，快快查看一对一房间了立即预约吧！</div>',$("#res").html(d+e),a.INFO.safemail.length?$("#member-index-safemail").length:(a.INFO.new_user?$(".mail-remind-text-new").show():($(".mail-remind-text-old").show(),$(".user-safemail-remind").show()),$(".mail-check-close").click(function(){$(".mail-check-wrap").hide(),$(".user-safemail-remind").show()}))})})},showLoginDialog:function(){this.loginDialog.show(),$("#dialogLogin").trigger("click")},showRegDialog:function(){this.loginDialog.show(),$("#dialogRegister").trigger("click")}}),b.User=a}("undefined"!=typeof User?User:{},window),function(a,b){"use strict";a=function(a){var b=this,c=function(a){a.objList.show(),a.state=!0},d=function(a){a.objList.hide(),a.state=!1};this.initState={dropdown:function(){c(b)},dropup:function(){d(b)}},this.state=!1,this.handleItem,this.init=function(){this.setOptions(),this.target=$("#"+this.options.id),this.objTtile=this.target.find(".dropdown-title-text"),this.objList=this.target.find(".dropdown-list"),this.handleItem=this.options.handleItem,this.bindEvents()},this.setOptions=function(){return this.options=a},this.getOptions=function(){return this.options},this.bindEvents=function(){this.objTtile.on("click",function(){b.state?b.setState("dropup"):b.setState("dropdown")}),this.objList.on("click",".dropdown-item",function(a){var c=a.target.innerText;b.setTitle(c),b.setState("dropup"),b.handleItem(a.target)})},this.setState=function(a){this.initState[a]()},this.setTitle=function(a){this.objTtile.text(a)},this.init()},b.Dropdown=a}("undefined"!=typeof Dropdown?Dropdown:{},window),function(a,b){"function"==typeof define&&define.amd?define(["../vendor/jquery/jquery.min.js"],b):"object"==typeof module&&module.exports?module.exports=b():this[a]=b(jQuery)}("UserService",function(a){var b=function(a){var b=[],c="",d="";a=a.split("");for(var e=0,f=a.length;e<f;e++)c=a[e],d=encodeURIComponent(c),d==c&&(d=c.charCodeAt().toString(16),d=("00"+d).slice(-2)),b.push(d);return b.join("").replace(/%/g,"").toUpperCase()},c={actionLogin:function(c){a.ajax({url:"/login",type:"post",dataType:"json",data:{uname:c.username,password:b(c.password),sCode:c.captcha,v_remember:c.remember},success:function(a){1==a.status?c.sucCallback&&c.sucCallback(a):c.errCallback&&c.errCallback(a)},error:function(a){Utility.log("login server error")}})},actionRegister:function(c){a.ajax({url:"/reg",type:"post",dataType:"json",data:{username:c.username,nickname:c.nickname,password1:b(c.password),password2:b(c.repassword),captcha:c.captcha},success:function(a){1==a.status?c.sucCallback&&c.sucCallback(a):c.errCallback&&c.errCallback(a)},error:function(a){Utility.log("register server error")}})}};return c}),$(function(){Utility.tabSwitch(),0==$("#livePage").length&&0==$("#loginPage").length&&loginInfoInit(),setAgent()});var timeComparing=function(a){var b=$("#"+a),c=b.find(".J-start"),d=b.find(".J-end"),e=(b.find(".btn"),c.val()),f=d.val(),g=new Date(e.replace(/-/g,"/")),h=new Date(f.replace(/-/g,"/")),i=(h.getTime()-g.getTime())/864e5;return i<0?($.tips("请输入正确的时间起始点。"),!1):void b.submit()},wordsCount=function(a,b,c){$(a).on("keydown keyup blur mousecenter mouseleave mousemove",function(){var a=$(this).val().length||0,d=c-a;if(b&&$(b).text(d>0?d:0),d<0)return $(this).val($(this).val().substring(0,c)),!1})};$.fn.extend({removeVCIcon:function(){return this.each(function(){var a=$(this);a.siblings(".i-vc").remove()})},afterIcon:function(a){return this.each(function(){$(this).removeVCIcon(),$(this).after(a)})},accountInput:function(a,b){return this.each(function(){$(this).on("focus blur",function(b){var c=$(this),d=c.val();0==d.length&&($(a).html("请输入您的邮箱！").css({color:"#29a2ff"}),c.afterIcon(vcIconInfoTMP))}).on("keyup",function(){var c=$(this),d=$.trim(c.val());if(0!=d.length)if(d.length<6)$(a).html("您的邮箱地址过短！").css("color","#c1111c"),c.afterIcon(vcIconWarnTMP);else if(d.length>30)$(a).html("您的邮箱地址过长！").css("color","#c1111c"),c.afterIcon(vcIconWarnTMP);else if(Validation.isEmail(d)){if(!b)return $(a).html(""),void c.afterIcon(vcIconCorrectTMP);".rTip"==a?$.ajax({url:"/verfiyName",type:"GET",dataType:"json",data:{type:"username",username:d},success:function(b){0==b.data?($(a).html(b.msg).css("color","#c1111c"),c.afterIcon(vcIconWarnTMP)):($(a).html(""),c.afterIcon(vcIconCorrectTMP))}}):($(a).html(""),c.afterIcon(vcIconCorrectTMP))}else $(a).html("您的邮箱格式不正确！").css("color","#c1111c"),c.afterIcon(vcIconWarnTMP)})})},isNickname:function(a,b){return this.each(function(){$(this).on("focus blur",function(){var b=$(this),c=b.val();0==c.length&&($(a).html("请输入昵称！").css({color:"#29a2ff"}),b.afterIcon(vcIconInfoTMP))}),$(this).on("keyup",function(){var c=$(this),d=$.trim(c.val());if(0!=d.length)if(Validation.isAccount(c.val())){if(!b)return $(a).html(""),void c.afterIcon(vcIconCorrectTMP);$.ajax({url:"/verfiyName",type:"GET",dataType:"json",data:{type:"nickname",username:d},success:function(b){0==b.data?($(a).html(b.msg).css("color","#c1111c"),c.afterIcon(vcIconWarnTMP)):($(a).html("昵称格式输入正确！").css("color","#29a2ff"),c.afterIcon(vcIconCorrectTMP))}})}else $(a).html("注册昵称不能使用/:;\\空格,等特殊符号！(2-8位的昵称)").css("color","#c1111c"),c.afterIcon(vcIconWarnTMP)})})},passwordInput:function(a){return this.each(function(){var b=$(this);b.on("focus blur",function(){var c=$.trim(b.val());0==c.length&&($(a).html("请输入您的密码！").css("color","#29a2ff"),b.afterIcon(vcIconInfoTMP))}),b.on("keyup",function(){var c=b.val(),d=/^[0-9a-zA-Z]{6,22}$/;0==c.length?($(a).html("请输入您的密码！").css("color","#c1111c"),b.afterIcon(vcIconWarnTMP)):d.test(c)?($(a).html(""),b.afterIcon(vcIconCorrectTMP)):($(a).html("密码格式错误！").css("color","#c1111c"),b.afterIcon(vcIconWarnTMP))})})},regPasswordInput:function(a){return this.each(function(){var b=$(this);b.on("focus blur",function(){var c=$.trim(b.val());0==c.length&&($(a).html("请输入新密码！").css("color","#29a2ff"),b.afterIcon(vcIconInfoTMP))}),b.on("keyup",function(){var c=b.val(),d=/^[0-9a-zA-Z]{6,22}$/,e=/^\d{6,22}$/;0==c.length?($(a).html("请输入您的密码！").css("color","#c1111c"),b.afterIcon(vcIconWarnTMP)):e.test(c)?($(a).html("不能全是数字！").css("color","#c1111c"),b.afterIcon(vcIconWarnTMP)):d.test(c)?($(a).html(""),b.afterIcon(vcIconCorrectTMP)):($(a).html("密码格式错误！").css("color","#c1111c"),b.afterIcon(vcIconWarnTMP))})})},sCodeInput:function(a){return this.each(function(){var b=$(this);b.on("focus blur",function(){var c=$.trim(b.val());0==c.length&&$(a).html("请输入验证码！").css("color","#29a2ff")}),b.on("keyup",function(){var b=$(this).val(),c=/^[a-zA-Z0-9]{4}$/;0==b.length?$(a).html("您的验证码不能为空！").css("color","#c1111c"):c.test(b)?$(a).html(""):$(a).html("请输入4位由数字或字母组成的验证码！").css("color","#c1111c")})})},passwordAgain:function(a,b){return this.each(function(){var c=$(this);c.on("focus blur",function(){var a=c.val();0==a.length&&($(b).html("请输入确认密码！").css("color","#29a2ff"),c.afterIcon(vcIconInfoTMP))}),c.on("keyup",function(){var d=$(a).val(),e=c.val();d===e?""==d?($(b).html("确认密码不能为空").css("color","#c1111c"),c.afterIcon(vcIconWarnTMP)):($(b).html(""),c.afterIcon(vcIconCorrectTMP)):($(b).html("两次密码输入不同").css("color","#c1111c"),c.afterIcon(vcIconWarnTMP))})})}});var vcIconCorrectTMP='<span class="i-vc i-vc-correct"></span>',vcIconWarnTMP='<span class="i-vc i-vc-warn"></span>',vcIconInfoTMP='<span class="i-vc i-vc-info"></span>',getLocation=function(a){var b=new RegExp("(^|&)"+a+"=([^&]*)(&|$)"),c=location.search.substr(1).match(b);return null!=c?decodeURIComponent(c[2]):""},getIndexData=function(a,b,c){$.ajax({url:"/videoList",data:{_:(new Date).valueOf()+randomString(),type:a},type:"GET",dataType:"JSON",error:function(a){c(a)},success:b})};window.currentVideo={};var renderItem=function(a){for(var b="",c="/",d=a.length,e=0;e<d;e++){var f=a[e];generateLvTypeHTML(f.lv_type);if("object"==typeof f&&null!==f){if(f.live_time){if(f.live_time.indexOf("时")>-1){var g=f.live_time.indexOf("钟");g>-1&&(f.live_time=f.live_time.substring(0,g+1))}f.live_time=f.live_time.replace(/([0-9]+)/g,function(a){return"<span>"+a+"</span>"})}switch(f.tips="",1==f.enterRoomlimit?(f.isLimit='<span class="limit"></span>',f.tips="该房间有条件限制"):f.isLimit="",1==f.top&&(f.isTop="<div class='c-icon top'>置顶</div>"),2==f.tid?(f.isLock='<span class="lock"></span>',f.tips="进入该房间需要密码"):f.isLock="",1==f.enterRoomlimit?f.tips="该房间有限制":2==f.tid?f.tips="该房间需要密码才能进入":f.tips="",1==f.timecost_live_status?(f.isTimeCostIcon='<span class="c-icon">时长</span>',f.isTimeCost="timecost",f.timeCost='data-timecost="'+f.timecost+'"',f.tips="该房间以观看时长计费"):(f.isTimeCostIcon="",f.isTimeCost="",f.timeCost=""),2==f.tid||f.timecost_live_status?f.videoPath='href="javascript:;"':f.videoPath='href="'+(c+f.rid+Config.liveMode)+'" target="_blank"',f.live_status){case 0:f.status_color="free",f.status_title="未开播";break;case 1:f.status_color="live",f.status_title="直播";break;case 2:f.status_color="hot",f.status_title="热播";break;default:f.status_color="free",f.status_title="未开播"}var h=(2==f.tid?1:0)+(1==f.enterRoomlimit?1:0)+(f.one_to_many_status?1:0)+(1==f.timecost_live_status?1:0);f.room_type=f.one_to_many_status?'<div class="room_type">一对多</div>':"",f.new_user&&8e5==f.new_user?f.isNewUser='<div class="badge badge800000"></div>':f.isNewUser="",f.headimg=f.headimg?window.IMG_PATH+"/"+f.headimg:Config.imagePath+"/vzhubo.jpg",b+='<div class="l-list" title="'+f.tips+'" data-tid="'+f.tid+'" data-roomid="'+f.rid+'" data-isLimited="'+f.enterRoomlimit+'"'+f.isTimeCost+" "+f.timeCost+"><a "+f.videoPath+' class="l-block"><img src="'+f.headimg+'" alt="'+f.username+'"/><div class="state icon-default"><div class="c-icon-bar bar'+f.rid+'" style="width:'+(29*h+(11==f.origin?0:25))+'px">'+(11==f.origin?"":"<span class='mobile'></span>")+f.isTimeCostIcon+f.isLimit+f.isLock+"</div></div>"+(null==f.isTop?"":f.isTop)+'<div class="status '+f.status_color+'">'+f.status_title+"</div>"+f.isNewUser+'<div class="play"></div><div class="content"><div class="c-username"><div class="username">'+f.username+'</div></div><div class="thumb-bar"></div></div><div class="state"><div class="c-icon-bar bar'+f.rid+'" style="width:'+(44*h+(11==f.origin?0:25))+'px">'+f.room_type+"</div></div></div></a></div>"}}return b},renderOrdItem=function(a){for(var b="",c="/",d="ordRoom",e=0;e<a.length;e++){var f=a[e];generateLvTypeHTML(f.lv_type);if("object"==typeof f&&null!==f){switch(f.videoPath='href="javascript:;"',f.headimg=f.headimg?window.IMG_PATH+"/"+f.headimg:Config.imagePath+"/vzhubo.jpg",Number(f.appoint_state)){case 1:f.btnReserve='<span class="btn btn-s btn-around btn-reserve">立即预约</span>';break;case 2:f.btnReserve='<span class="btn btn-s btn-around btn-reserve btn-disabled" >正在约会</span>';break;case 3:f.btnReserve='<span class="btn btn-s btn-around btn-reserve btn-disabled" >已被预约</span>';break;default:f.btnReserve='<span class="btn btn-s btn-around btn-reserve">立即预约</span>'}f.tips="",1==f.enterRoomlimit?(f.isLimit='<span class="limit"></span>',f.tips="该房间有条件限制"):f.isLimit="",1==f.top&&(f.isTop="<div class='c-icon top'>置顶</div>"),2==f.tid?(f.isLock='<span class="lock"></span>',f.tips="进入该房间需要密码"):f.isLock="",1==f.enterRoomlimit?f.tips="该房间有限制":2==f.tid?f.tips="该房间需要密码才能进入":f.tips="",1==f.timecost_live_status?(f.isTimeCostIcon='<span class="c-icon">时长</span>',f.isTimeCost="timecost",f.timeCost='data-timecost="'+f.timecost+'"',f.tips="该房间以观看时长计费"):(f.isTimeCostIcon="",f.isTimeCost="",f.timeCost=""),f.room_type=f.one_to_many_status?'<div class="room_type">一对多</div>':"","undefined"!=typeof f.listType&&"myres"==f.listType&&(f.videoPath='href="'+(c+f.uid)+Config.liveMode+'"',f.btnReserve='<span class="btn btn-s btn-around btn-reserve">进入房间</span>',d="");var g=(2==f.tid?1:0)+(1==f.enterRoomlimit?1:0)+(f.one_to_many_status?1:0)+(1==f.timecost_live_status?1:0);f.new_user&&8e5==f.new_user?f.isNewUser='<div class="badge badge800000"></div>':f.isNewUser="",b+='<div class="l-list '+d+'" data-appointstate="'+f.appoint_state+'" data-duration="'+f.live_duration+'" data-points="'+f.points+'" data-starttime="'+switchToZhTime(f.starttime)+'" data-roomid="'+f.id+'"><a '+f.videoPath+' class="l-block" target="_blank"><img src="'+f.headimg+'" alt="'+f.username+'"/><div class="state icon-default"><div class="c-icon-bar bar'+f.rid+'" style="width:'+(29*g+(11==f.origin?0:25))+'px">'+(11==f.origin?"":"<span class='mobile'></span>")+f.isTimeCostIcon+f.isLimit+f.isLock+"</div></div>"+(null==f.isTop?"":f.isTop)+'<div class="play-ord"><span class="l-price">'+f.points+"钻 ("+f.live_duration+")</span>"+f.btnReserve+"</div>"+f.isNewUser+'<div class="play"></div><div class="content"><div class="c-username"><div class="username">'+f.username+'</div></div><div class="state"><div class="c-icon-bar bar'+f.rid+'" style="width:'+(44*g+(11==f.origin?0:25))+'px">'+f.room_type+"</div></div></div></a></div>"}}return b},switchToZhTime=function(a){if(0!=a.length&&null!=a){var b=a.split(" ")[0],c=parseInt(b.split("-")[0],10)+"月"+parseInt(b.split("-")[1],10)+"日",d=a.split(" ")[1];return c+" "+d}},generateLvTypeHTML=function(a){var b="";switch(a){case 1:b="<span class='lvtype lvtype1'></span>";break;case 2:b="<span class='lvtype lvtype2'></span>";break;case 3:b="<span class='lvtype lvtype3'></span>";break;default:b="<span class='lvtype lvtype1'></span>"}return b},renderOneToMoreItem=function(a){for(var b="",c="/",d="ticketRoom",e=0;e<a.length;e++){var f=a[e],g=f.duration,h=f.rid,i=f.id;switchToZhTime(f.start_time),generateLvTypeHTML(f.lv_type);if("object"==typeof f&&null!==f){f.videoPath='href="javascript:;"',f.headimg=f.headimg?window.IMG_PATH+"/"+f.headimg:Config.imagePath+"/vzhubo.jpg",f.btnReserve='<span class="btn btn-s btn-around btn-reserve">立即购票</span>',f.tips="",1==f.enterRoomlimit?(f.isLimit='<span class="limit"></span>',f.tips="该房间有条件限制"):f.isLimit="",1==f.top&&(f.isTop="<div class='c-icon top'>置顶</div>"),2==f.tid?(f.isLock='<span class="lock"></span>',f.tips="进入该房间需要密码"):f.isLock="",1==f.enterRoomlimit?f.tips="该房间有限制":2==f.tid?f.tips="该房间需要密码才能进入":f.tips="",
1==f.timecost_live_status?(f.isTimeCostIcon='<span class="c-icon">时长</span>',f.isTimeCost="timecost",f.timeCost='data-timecost="'+f.timecost+'"',f.tips="该房间以观看时长计费"):(f.isTimeCostIcon="",f.isTimeCost="",f.timeCost=""),f.room_type=f.one_to_many_status?'<div class="room_type">一对多</div>':"","undefined"!=typeof f.listType&&"myticket"==f.listType&&(f.videoPath='href="'+(c+f.uid)+'"',f.btnReserve='<span class="btn btn-s btn-around btn-reserve">进入房间</span>',d="");var j=(2==f.tid?1:0)+(1==f.enterRoomlimit?1:0)+(f.one_to_many_status?1:0)+(1==f.timecost_live_status?1:0);f.new_user&&8e5==f.new_user?f.isNewUser='<div class="badge badge800000"></div>':f.isNewUser="",b+='<div class="l-list '+d+'" data-duration="'+g+'" data-points="'+f.points+'" data-starttime="'+switchToZhTime(f.start_time)+'" data-endtime="'+switchToZhTime(f.end_time)+'" data-roomid="'+h+'" data-onetomany="'+i+'" data-usercount="'+f.user_count+'"><a '+f.videoPath+' class="l-block" target="_blank"><img src="'+f.headimg+'" alt="'+f.username+'"/><div class="state icon-default"><div class="c-icon-bar bar'+f.rid+'" style="width:'+(29*j+(11==f.origin?0:25))+'px">'+(11==f.origin?"":"<span class='mobile'></span>")+f.isTimeCostIcon+f.isLimit+f.isLock+"</div></div>"+(null==f.isTop?"":f.isTop)+'<div class="play-ord"><span class="l-price">'+f.points+"钻 ("+Number(g)/60+"分钟)</span>"+f.btnReserve+"</div>"+f.isNewUser+'<div class="play"></div><div class="content"><div class="c-username"><div class="username">'+f.username+'</div></div><div class="thumb-bar"></div><div class="state"><div class="c-icon-bar bar'+f.rid+'" style="width:'+(44*j+(11==f.origin?0:25))+'px">'+f.room_type+"</div></div></div></a></div>"}}return b},reserveRoom=function(a){$.ajax({url:"/member/doReservation",dataType:"json",type:"GET",data:{duroomid:a,flag:!1},success:function(b){1==b.code?$.tips("预约成功"):407==b.code?$.dialog({title:"预约房间",content:"在同时间段您已经预约了其它房间，确定预约相同时间段的本房间吗？",ok:function(){$.ajax({url:"/member/doReservation",dataType:"json",type:"GET",data:{duroomid:a,flag:!0},success:function(a){1==a.code?$.tips("预约成功"):$.tips(a.msg)},error:function(a,b){$.tips("server error!")}})},okValue:"确定",cancel:function(){},cancelValue:"取消"}).show():405==b.code?$.tips(b.msg,function(){location.href="/charge/order"}):$.tips(b.msg)},error:function(a,b){$.tips("server error!")}})},loopOptions=function(a){for(var b={startNum:0,endNum:60,interval:1,isPlusZero:!0},c=$.extend(!0,b,a),d="",e=c.startNum;e<=c.endNum;e+=c.interval)d=e<10&&c.isPlusZero?d+"<option>0"+e+"</option>":d+"<option>"+e+"</option>";return d},loginInfoInit=function(){window.user=new User;var a=getLocation("u");if(a){var b=document.referrer;$.cookie("invitation_uid",a,1/24),$.cookie("invitation_refer",b,1/24)}var c=getLocation("agent");c&&$.cookie("agent",c,{expires:1/48,domain:document.domain.replace(/^www/,"")}),$(".J-login").on("click",function(a){User.showLoginDialog()}),$(".J-reg").on("click",function(a){User.showRegDialog()}),User.flashUserInfo()};