/**
 * @description 聊天 发送框  表情栏 综合控件
 * @author  seed
 * @param  getExpressionCode
 * @date 2017-2-9
 */
import React, {Component} from 'react';
import {bindActionCreators} from 'redux';
import {connect} from "react-redux";
import * as chatActions from '../../../actions/chatActions.js';
import * as userActions from '../../../actions/userActions.js';
import * as animationActions from '../../../actions/animationActions.js'; //上线立删
import styles from './chatControl.css';
import Button from '../../Common/Button/Button.js';
import Common from "../../../utils/Common.js";

/* 关键字解码类 */
import chatCommon from "../../../utils/Chat.js";

const mapStateToProps = (state) =>{
      return {
            userInfo: state.userInfo,
            chatAt: state.chatAt
      }
}

const mapDispatchToProps = (dispatch) =>{
      return {
            chatAction: bindActionCreators(chatActions,dispatch),
            userActions: bindActionCreators( userActions, dispatch),
            animationActions: bindActionCreators(animationActions,dispatch), //上线立删
      }
}

class ChatControl  extends Component {

      constructor(props){
            super(props);
            this.state = {
                  //消息缓存功能
                  cacheKey:false,
                  cacheList:[],
                  //ajax 获得的关键字替换规则
                  kw:"",
                  timeStamp:'first',   //初始化为 "first"  详见checkThenSendMessage 其中的判断
            }
      }

      //键盘事件绑定 (回车等...)
      checkKeyCode(event){

            // Ctrl+回车键
            if(event.ctrlKey && event.keyCode === 13){
                  this.checkThenSendMessage();
            }

            //回车键
            else if(event.keyCode === 13){
                  this.checkThenSendMessage();
            }
            //左箭头
            // else if(event.keyCode === 37){
            //
            //       let cacheKey = this.state.cacheKey;
            //       let cacheList = this.state.cacheList;
            //
            //       //滚动历史信息
            //       if(typeof cacheKey === "number"){
            //
            //             //操作文本框内容
            //             this.refs.chatInput.value = this.state.cacheList[cacheKey].content;
            //
            //             //向上滚动
            //             if((cacheKey+1) < cacheList.length){
            //                   cacheKey++;
            //             }
            //
            //             //保存缓存指针
            //             this.setState({
            //                   cacheKey:cacheKey
            //             })
            //
            //       }
            // }
            // //上箭头
            // else if(event.keyCode === 38){
            //
            //       let cacheKey = this.state.cacheKey;
            //       let cacheList = this.state.cacheList;
            //
            //       //滚动历史信息
            //       if(typeof cacheKey === "number"){
            //
            //             //操作文本框内容
            //             this.refs.chatInput.value = this.state.cacheList[cacheKey].content;
            //
            //             //向上滚动
            //             if((cacheKey+1) < cacheList.length){
            //                   cacheKey++;
            //             }
            //
            //             //保存缓存指针
            //             this.setState({
            //                   cacheKey:cacheKey
            //             })
            //
            //       }
            //
            // }
            // //右箭头
            // else if(event.keyCode === 39){
            //
            //       let cacheKey = this.state.cacheKey;
            //       let cacheList = this.state.cacheList;
            //
            //       //滚动历史信息
            //       if(typeof cacheKey === "number"){
            //             //向下滚动
            //             if(cacheKey > 0){
            //                   cacheKey--;
            //             }
            //
            //             //操作文本框内容
            //             this.refs.chatInput.value = this.state.cacheList[cacheKey].content;
            //
            //             if(cacheKey > 0 ){
            //                   //保存缓存指针
            //                   this.setState({
            //                         cacheKey:cacheKey
            //                   })
            //             }
            //
            //       }
            // }
            // //下箭头
            // else if(event.keyCode === 40){
            //
            //       let cacheKey = this.state.cacheKey;
            //       let cacheList = this.state.cacheList;
            //
            //       //滚动历史信息
            //       if(typeof cacheKey === "number"){
            //             //向下滚动
            //             if(cacheKey > 0){
            //                   cacheKey--;
            //             }
            //
            //             //操作文本框内容
            //             this.refs.chatInput.value = this.state.cacheList[cacheKey].content;
            //
            //             if(cacheKey > 0 ){
            //                   //保存缓存指针
            //                   this.setState({
            //                         cacheKey:cacheKey
            //                   })
            //             }
            //
            //       }
            // }

      }

      //@ 其他人..
      chatAt(userName){
            let atText = "@"+userName+" ";
            //操作文本框内容
            this.refs.chatInput.value = atText;
            //让输入框再次获得焦点
            this.refs.chatInput.focus();
      }

      //通过ajax 获得关键字替换规则
      componentDidMount(){
            this.props.chatAction.getChatKeywords( ( replaceStr )=>{
                  if(typeof replaceStr.kw == "string"){
                        this.setState({
                              kw:replaceStr.kw
                        })
                  }
            });
      }

      // 将回调的表情代码  加入input 内容  、让输入框再次获得焦点
      componentDidUpdate(){
            let { code = false } = this.props.expressionData;

            // 将回调的表情代码  加入input 内容
            if(code){
                  this.refs.chatInput.value = this.refs.chatInput.value + "{/" + code.substring(1,3) + "}";
                  //让输入框再次获得焦点
                  this.refs.chatInput.focus();
            }
      }

      //表情控件开关
      connectExpressionCtrl(){
            this.props.expressionCtrl();
      }

      //验证用户输入信息 , 验证通过后发送至信息列表
      checkThenSendMessage( action=""){

            let chatContent = this.refs.chatInput.value;
            let roomid = this.props.userInfo.roomid;
            let that = this;

            //发送消息类型。默认为0   如果在开启飞屏的话...  会被重定义为 9...
            let defaultMsgType = 0;

            //如果是游客,禁止发言
            if( this.props.userInfo.ruled ===-1 ){
                  Common.handleUnlogin("请登录后再发言！");
                  return;
            }

            //如果输入信息不为空 并且已拉取到聊天长度限制..信息间隔
            if(chatContent.length != 0 && this.props.userInfo.chatlimit ){

                  //@人名字的长度
                  let atUserNameLen = this.props.chatAt.userName ? this.props.chatAt.userName.length : 0;
                  //@人实际要减去的长度
                  let atResultUserNameLen = atUserNameLen > 0 ? atUserNameLen + 1 : 0;

                  //表情数量
                  let expressionArr = chatCommon.getExpressionArray(chatContent);

                  //判断是否符合长度限制
                  if( (chatContent.length - atResultUserNameLen - expressionArr.length * 4 ) > this.props.userInfo.chatlimit && action != "flyScreen"){
                        $.dialog({
                          title: '温馨提示',
                          content: "您当前只能发送"+ this.props.userInfo.chatlimit+"个字符, 开通贵族提升等级可以发送更多内容喔, 并且拥有文字高亮特权!",
                          okValue: "立即开通贵族",
                          ok: function () {
                            Common.handleBtnVip(roomid, that.props.userActions.sendVIPMessage);
                          },
                          cancelValue: "狠心离开",
                          cancel: function(){}
                        }).show();
                  }else{
                        const now = new Date().getTime();
                        const past = this.state.timeStamp;

                        //如果是第一次发送消息的话... 使其通过下面的判断
                        const timeVal = ( past === "first") ?  ( this.props.userInfo.chatsecond * 1000 ) :  Math.floor(now - past);

                        /**
                         * 首先验证用户输入时间 是否在间隔范围 (飞屏直接通过.. 这块写的不太好.. 飞屏和聊天应该是各自独立的代码块)
                         */

                        //大于间隔范围
                        if( timeVal >= ( this.props.userInfo.chatsecond * 1000 ) || action == "flyScreen" ){

                              //关键词过滤
                              chatContent = chatCommon.chatKeywordHandle(chatContent,this.state.kw);

                              //如果开启 飞屏 的话...  ( 这块的参数绑在飞机图标上 )
                              if(action == "flyScreen"){

                                    //拉取当前用户钻石数..
                                    let points = this.props.userInfo.points;

                                    //如果钻石数不足...
                                    if(points <= 0){
                                          $.tips("很抱歉 您的钻石不足");
                                          return ""
                                    }

                                    defaultMsgType = 9;
                                    //拼接用户名
                              }

                              //当前时间戳
                              const timeStamp = new Date().getTime();

                              //生成需要发送的JSON
                              const chatJson = chatCommon.json_30001({
                                    content: chatContent,  //聊天信息
                                    timeStamp: timeStamp,  //时间戳
                                    type: defaultMsgType,  //消息类型
                              }, this.props.userInfo);

                              //发送一条信息
                              this.props.chatAction.sendChatMessage(chatJson);

                              let cacheList = this.state.cacheList;
                              //发送至 cacheList 缓存列表
                              cacheList.unshift(chatJson);

                              //更新缓存状态
                              this.setState({
                                    cacheList:cacheList,
                                    cacheKey:0,
                                    timeStamp:timeStamp
                              })

                              //清空聊天框内容
                              this.refs.chatInput.value = null;

                              //给父组件信号  清除最后一个表情
                              return this.props.expressionClear();

                        }else{
                              // 发送频率太高
                              // 两次发送信息 小于当前限制间隔
                              let timeMsg = (( this.props.userInfo.chatsecond * 1000 ) - timeVal) / 1000;
                              timeMsg = (Number.parseInt(timeMsg)) + 1;

                              $.dialog({
                                    title: '温馨提示',
                                    content: "您发送频率太高啦, 还有"+ timeMsg + "秒可以发送, 开通贵族提升等级就可以发送更多内容喔, 并且拥有文字高亮特权!",
                                    okValue: "立即开通贵族",
                                    ok: function () {
                                          Common.handleBtnVip(roomid, that.props.userActions.sendVIPMessage);
                                    },
                                    cancelValue: "狠心离开",
                                    cancel: function(){}
                              }).show();
                        }

                  }
            }else{
                  $.tips("发送内容不得为空")
            }
      }

      render(){
            //参与生命周期绑定
            this.checkThenSendMessage = this.checkThenSendMessage.bind(this);
            this.checkKeyCode = this.checkKeyCode.bind(this);
            this.connectExpressionCtrl = this.connectExpressionCtrl.bind(this);
            this.chatAt = this.chatAt.bind(this);

            if(this.props.chatAt.userName){
                  this.chatAt(this.props.chatAt.userName)
            }

            return (
                  <div className={ styles.chatControl }>
                        <div className={ styles.smeil } onClick={ this.connectExpressionCtrl }></div>
                        <div className={ styles.chatInput }>
                              <div className={ styles.chatInputControl }>
                                    <input type="text" className={ styles.chatInputText } placeholder="和大家愉快的聊天吧" ref="chatInput" onKeyUp={ this.checkKeyCode }/>
                                    <div className={ styles.planeBtn } onClick={ ()=>{ this.checkThenSendMessage("flyScreen") } }>
                                          <span className={ styles.planeIcon } ></span>
                                          <div className={ styles.planeText } ref="remindOfPlane">100钻发飞屏</div>
                                    </div>
                              </div>
                              <Button text="发言" buttonClass={ styles.sendButton } size="large" onHandleClick={ this.checkThenSendMessage }></Button>
                        </div>
                  </div>
            )
      }

}
export default connect(mapStateToProps, mapDispatchToProps)(ChatControl);