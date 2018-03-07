
import HttpRequest from '../httpRequest.js';

class Common {
    //进行数据排序的公用方法
    static compare(prop){
        return function (obj1, obj2) {
            let val1 = obj1[prop];
            let val2 = obj2[prop];
            if (!isNaN(Number(val1)) && !isNaN(Number(val2))) {
                val1 = Number(val1);
                val2 = Number(val2);
            }
            if (val1 < val2) {
                return 1;
            } else if (val1 > val2) {
                return -1;
            } else {
                return 0;
            }
        }
    }

    //用于排序算法中对象转为数组
    static handleArr( obj , prop ){
        if(Object.keys(obj).length !== 0){
            let compareList=[];
            for (let item of Object.keys(obj)){
                compareList.push(
                    obj[item]
                )
            }
            compareList.sort(this.compare(prop))
            return compareList;
        }else {
            return [];
        }

    }

    //讲对象排序转为数组（prop:"online", "manager", "vip"）
    static convertObjToArray( obj , prop ){
        if(Object.keys(obj).length !== 0){
            let compareList = [];
            let sortList = [];

            for (let item of Object.keys(obj)){
                compareList.push(
                    obj[item]
                )
            }

            //排序
            compareList.sort(this.compare(prop));

            /**
             * 重组sort数据并将,并将主播排第一
             */
            compareList.map((item)=>{
                sortList.push(item.uid);
            });

            return sortList;
        }else {
            return [];
        }

    }

    //移除数组中的指定元素
    static arrayRemoveElement(arr, element) {
        let index = arr.indexOf(element);
        if (index > -1) {
            arr.splice(index, 1);
        }
        return arr;
    }

    //统一处理充值
    static handleBtnCharge(){
        if( !User.isLogin()){
            this.handleUnlogin("请先登录后再充值");
        }else{
            // window.open("/charge/order")
            showPay();
        }
    }

    //统一处理开通贵族
    static handleBtnVip(uid, successCallback){
        if( !User.isLogin()){
            this.handleUnlogin("请先登录后再开通贵族");
        }else{
            HttpRequest.showVipDialog(uid, successCallback);
        }
    }

    static handleUnlogin(tipsText){
        $.dialog({
          title: "提示",
          content: tipsText || "请登录后再进行操作",
          okValue: "立即登录",
          ok: function(){
            User.showLoginDialog();
          },
          cancelValue: "关闭",
          cancel: function(){}
        }).show();
    }
}
export default Common;