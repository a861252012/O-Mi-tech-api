/**
 * @description: 聊天模块
 * @author: Seed
 * @date: 2017/2/20
 */
import React, { Component } from "react";
//礼物信息列表
import GiftsList from "../../components/Chat/GiftsList/GiftsList.js";
//聊天信息列表
import ChatList from "../../components/Chat/ChatList/ChatList.js";
//公告区域
import Notice from "../../components/Chat/Notice/Notice.js";
//选项卡组件
import Tabs from "../../components/Common/Tabs/Tabs.js";
//聊天表单区域
import ChatControl from "../../components/Chat/ChatControl/ChatControl.js";
//表情控件
import ExpressionPanel from "../../components/Chat/ExpressionPanel/ExpressionPanel.js";
//礼物轮播
import GiftCarousel from "../../components/Gift/GiftCarousel/GiftCarousel.js";
//gift board
import RoomGift from '../Gift/Gift.js';

import styles from "./chat.css";

const MobileStyle = () => {
  if (window.isMobile) {
    return (
        <style type="text/css">
          {
            '.chat-container{min-width: 505px;}'
          }
        </style>
    )
  } else {
    return (
        <style type="text/css">
          {
            '@media (max-width: 1400px){' +
            '.chat-container{ margin-left: 561px; }' +
            '}'
          }
        </style>
    )
  }
}

class RoomChat extends Component {
    
    constructor(props){
        super(props);
        //获得Tabs的回调 Key
        this.state = {
            tabIndex: 0,
            giftListChangeKey:0,
            expressionData:{
                code : false,
                ctrl : false  
            }
        }
    }

    onHandleSwitchTab(index){
        //特殊绑定: 礼物列表伸缩高度
        this.setState({ 
            tabIndex: index, 
            giftListChangeKey:index,
        })
    }

    handleExpressionCode( code ){
        //从表情面板中得到回调的 code  ... 继续往输入框传递...

        this.setState({
             expressionData: {
                code:code,
            }
         });

    }

    handleExpressionClear(){
        //发送成功时  将上一次输入的最后一个表情清除
        this.setState({
             expressionData: {
                code:false,
            }
         });
    }

    handleExpressionCtrl(){
        //从表情面板中回调onclick句柄  操作ctrl字段   从而在chatControl 实现开关逻辑

        this.setState({
            expressionData:{
                ctrl:!this.state.expressionData.ctrl
            }
        })

    }

    render() {
        //tab 选项卡 设定
        let tabsContent =  [
            {
                key:"chatWindow",
                title:'聊天窗口',
            },
            {
                key:"giftList",
                title:'礼物清单',
            }

        ]

        return (
            <div className="chat-container" id="chat">
                <MobileStyle />
                <Tabs arr={ tabsContent }  onHandleSwitchTab={ this.onHandleSwitchTab.bind(this) } tabIndex={ this.state.tabIndex }>
                    <div>
                        <GiftCarousel></GiftCarousel>
                        <GiftsList lengthChange={this.state.giftListChangeKey}></GiftsList>
                        <Notice></Notice>
                        <ChatList></ChatList>
                        <ExpressionPanel expressionData = { this.state.expressionData }  handleExpressionCode={ this.handleExpressionCode.bind(this) }></ExpressionPanel>
                        <ChatControl expressionData = { this.state.expressionData } expressionCtrl = { this.handleExpressionCtrl.bind(this) }  expressionClear =  {this.handleExpressionClear.bind(this) } ></ChatControl>
                        <RoomGift />
                    </div>
                    <div>
                        <GiftsList lengthChange={this.state.giftListChangeKey}></GiftsList>
                    </div>
                </Tabs>
            </div>
        )
    }

}

export default RoomChat;