/**
 * @description 聊天相关处理方法
 * @author seed
 * @date 2017-2-21
 */
class ChatCommon {

      //根据关键字规则  过滤字符串
      static chatKeywordHandle(text,data= "" ){
            if(data != ""){
                  //从接口得到过滤词列表
                  const allRules = data.split("||");

                  if(Array.isArray((allRules))){
                        //循环过滤词 ，根据不同过滤词的匹配模式 试探匹配文字内容
                        allRules.map((rules)=>{

                              //过滤词替换模式
                              let rule = "";
                              //根据过滤词转换的正则表达式
                              let regStr = "";
                              //根据regStr 生成的 对应替换规则正则表达式
                              let replaceStr = "";
                              //过滤词文字内容
                              let content = rules.slice(0,rules.search(/\|\d{1}/));

                              //过滤词匹配模式   1：精确匹配   2：模糊匹配
                              rule = rules.substring(rules.search(/\|\d{1}/));

                              /**
                               * 根据模式  将关键字转为正则表达式*/
                              regStr = "";
                              if(rule === "|1"){

                                    regStr = "(.*)("+content + ")(.*)";

                              }else if(rule === "|2"){
                                    //模糊匹配聊天内容
                                    for(let i=0; i < content.length; i++ ){
                                          //这里屏蔽掉 所有正则中可能引起未转义错误的字符
                                          if(content[i] !== "+"){
                                                regStr +=  "(.*)("+content[i]+")";
                                          }
                                    }
                              }

                              regStr = new RegExp(regStr);

                              /**
                               * 根据模式  将关键字转为正则表达式*/

                              // 如果被当前正则匹配的话.... 拼装 replaceStr
                              if(text.search(regStr) === 0){

                                    if(rule === "|1"){
                                          //精确匹配规则
                                          replaceStr = "$1"+"*".repeat(content.length)+"$3";

                                    }else if(rule === "|2"){

                                          //模糊匹配规则
                                          for(let i = 1; i<= (content.length * 2); i++){
                                                if(i % 2 == 0){
                                                      replaceStr += "*";
                                                }else{
                                                      replaceStr += "$"+i;
                                                }
                                          }
                                    }

                                    //根据正则 递归匹配内容 直至无法匹配
                                    let loopAndReplace = ()=>{
                                          if(text.search(regStr) === 0 ){
                                                text = text.replace(regStr,replaceStr);
                                                loopAndReplace();
                                          }
                                    }

                                    loopAndReplace();

                              }
                        })
                  }
            }
            return text;
      }


      //检查登录用户个人信息
      static checkUserInfo(userInfo){
            if(typeof userInfo == "object"){
                  userInfo = {};
            }

            userInfo.uid = (userInfo.uid)? userInfo.uid : 0;
            userInfo.vip = (userInfo.vip)? userInfo.vip : 0;
            userInfo.hidden = (userInfo.hidden) ? userInfo.hidden : 0;
            userInfo.richLv = (userInfo.richLv )? userInfo.richLv : 0;
            userInfo.car = (userInfo.car )? userInfo.car : 0;
            userInfo.sendName = (userInfo.sendName ) ? userInfo.sendName : "";
            userInfo.icon = (userInfo.icon ) ? userInfo.icon : 0;
            userInfo.lv = (userInfo.lv) ? userInfo.lv : 0;

            return userInfo;
      }

      //JSON 报文处理相关

      //检查调用接口所提供的数据
      static checkData(data){
            return Object.assign({},data,{
                  recIcon:0,           //接收者是否是贵族
                  recHidden:0,         //接收者是否是神秘人
                  recUid:0,            //接收者用户id
                  recName:"",          //接收者用户名称
                  recLv:0,             //接收者Lv
            })
      }


      static json_30001(_data,_userInfo){

            let userInfo = this.checkUserInfo(_userInfo);
            let data = this.checkData(_data);

            return {
                  sendUid:userInfo.uid,
                  vip:userInfo.vip,
                  date:data.timeStamp,
                  sendHidden:userInfo.hidden,
                  richLv:userInfo.richLv,
                  recIcon:data.recIcon,
                  recHidden:data.recHidden,
                  recUid:data.recUid,
                  recLv:data.recLv,
                  recName:data.recName,
                  car:userInfo.car,
                  cmd:30001,
                  sendName:userInfo.name,
                  type:data.type,
                  content:data.content,
                  icon:userInfo.icon,
                  lv:userInfo.lv,
            }
      }

      //static json_4000x(_data,_userInfo){
      //
      //      let userInfo = this.checkUserInfo(_userInfo);
      //      let data = this.checkData(_data);
      //
      //      return {
      //            vip:userInfo.uid,
      //            date:new Date().getTime(),
      //            sendHidden:userInfo.hidden,
      //            richLv:userInfo.richLv,
      //            sendUid:userInfo.uid,
      //            car:userInfo.car,
      //            cmd:(data.cmd) ? data.cmd : 40001,
      //            sendName:userInfo.name,
      //            type:0,
      //            content:"送礼更新数据测试",
      //            recLv:1,
      //            recHidden:data.recHidden,
      //            recUid:data.recUid,
      //            recName:data.recName,
      //            recIcon:0,
      //            icon:userInfo.icon,
      //            lv:userInfo.lv,
      //      }
      //}


  /**
   * 匹配表情
   * param: string 聊天语句
   * return array 表情数组
   */
  static getExpressionArray(chatContent){
        let result = chatContent.match(/\{\/\d{2}\}/g);
        return Object.prototype.toString.call(result) == '[object Array]' ? result : [];
  }



}
export default ChatCommon;