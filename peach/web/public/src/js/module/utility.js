/**
 * @description 实用工具
 * @author Young
 * @param Utility, window
 */

(function(__utility, window){

	$.extend(__utility, {

		/**
		 * @description console.log() 以兼容IE情况
		 * @author Young
		 * @param string: 错误信息
		 */

		log: function(str){
			window.console && console.log(str);
		},

		/**
		 * @description 简易模板引擎
		 * @author Young
		 * @param string: 字符串模板
		 */
		template: function(src, options, ori){

		    var curStr;
		    //$.support为特征检测，checkOn IE返回false
		    if(!$.support.checkOn){
		        curStr = src;
		    }else{
		        curStr = [];
		        var len = src.length;
		        var i;
		        for(i=0; i<len; i++){
		            curStr.push(src[i]);
		        }
		        curStr = curStr.join("");
		    }

		    var formatReg = new RegExp("#{([a-z0-9_]+)}", "ig");
		    curStr = curStr.replace(formatReg, function(match, f1, index, srcStr){
		        return options[f1]?options[f1]:(ori?match:"");
		    });
		    return curStr;

		},

		/**
		 * 切换域
		 * @param  {[type]} str      [需要替换的字符串]
		 * @param  {[type]} afterStr [替换后的字符串]
		 * @return {[type]}          [origin]
		 */
		switchOrigin: function(str, afterStr){
			return "http://" + location.host.replace(str, afterStr);
		},


    /**
     * @description J-tab 列表切换初始化
     * @author Peter
     * @param: null
     */
    tabSwitch: function($btn, callback){

      if(arguments.length == 0){
        $btn = $("body");
      }

      $btn.find('.J-tab-menu li:not(.close)').on("click", function() {
        var $tab = $(this).closest(".J-tab");
        $(this).parent().children('li').removeClass('active');
        $(this).addClass('active');
        var tabMain = $tab.find('.J-tab-main');
        tabMain.removeClass('active');
        tabMain.eq($(this).index()).addClass('active');
        if (callback) {callback();};
      });

    }


  });

	window.Utility = __utility;

})(typeof Utility !== "undefined" ? Utility: {}, window);