/**
 * @description: 返回一个大窗口  嵌套 带有“取、关管理员、踢人、禁言" 的用户二级菜单（绝对定位，块级元素）
 * @author: seed
 * @param: redux
 * @date: 2017-3-22
 */
import React, { Component } from "react";
import {bindActionCreators} from 'redux';
import {connect} from "react-redux";
import * as commonActions from "../../../actions/commonActions.js";
import * as userActions from "../../../actions/userActions.js";
import * as chatActions from "../../../actions/chatActions.js";
// import UserInfoPanel from "./UserInfoPanel.js";

import styles from "./userPanel.css";

const mapStateToProps = ( state ) =>{
    return {
        spreadUserPanel:state.spreadUserPanel,
        userInfo:state.userInfo,
        users:state.users
    }
}

const mapDispatchToProps = ( dispatch )=>{
    return {
        commonAction:bindActionCreators(commonActions,dispatch),
        userAction:bindActionCreators(userActions,dispatch),
        chatAction:bindActionCreators(chatActions,dispatch)
    }
}



class SpreadUserPanel extends  Component{

    getHashPrefix(){
        return Math.random().toString(16).slice(2,8) + "_" + Math.random().toString(16).slice(2,5);
    }
    //查看资料
    checkMsg(){
        return (
            <span onClick={ this.toCheckMsg } key={ this.getHashPrefix() }>
                查看资料
            </span>
        );
    }

    //查看面板资料
    toCheckMsg(){
        this.props.commonAction.setCheckMsg(true);
        this.props.commonAction.setUserPanelStatus(false);
        this.props.commonAction.fetchCheckUserInfo(this.props.spreadUserPanel.userDatas.userId);
        this.props.commonAction.fetchFocusInfo(this.props.spreadUserPanel.userDatas.userId,0);
    }

    //关闭当前窗口
    closeUserPanel(){
        this.props.commonAction.setUserPanelStatus(false);
        // this.props.commonAction.setCheckMsg(false);
    }
    //打开当前窗口
    openUserPanel(){
        this.props.commonAction.setUserPanelStatus(true);
    }

    //设置管理员
    JSXsetManager(){
        return <span key={ this.getHashPrefix() } onClick={ this.toSetManager }>设置管理员</span>
    }
    toSetManager(){
        this.props.userAction.callSetManager( this.props.spreadUserPanel.userDatas.userId )
        this.closeUserPanel()
    }

    //取消管理员
    JSXcancelManager(){
        return <span key={ this.getHashPrefix() } onClick={ this.toCancelManager }>取消管理员</span>
    }
    toCancelManager(){
        this.props.userAction.callCancelManager( this.props.spreadUserPanel.userDatas.userId )
        this.closeUserPanel()
    }

    //踢出房间
    JSXknockOut(){
        return <span key={ this.getHashPrefix() } onClick={ this.toKnockOut } >T出房间</span>
    }
    toKnockOut(){
      let that = this;
      let userName = this.props.spreadUserPanel.userDatas.userName;

        $.dialog({
          title: '踢出房间',
          content: '您确定要把' + userName + 'T出房间吗?他将30分钟内不能再次进入您的房间!',
          okValue: '踢出',
          ok: function(){
            that.props.userAction.callRemoveFromRoom( that.props.spreadUserPanel.userDatas.userId )
            that.closeUserPanel()
          },
          cancelValue: '取消',
          cancel: function(){}
        }).show();
    }

    //禁止发言
    JSXbanToSpeak(){
        return <span key={ this.getHashPrefix() } onClick={ this.toBanToSpeak }>禁言房间</span>
    }
    toBanToSpeak(){
      let that = this;
      let userName = this.props.spreadUserPanel.userDatas.userName;

      $.dialog({
        title: '禁言',
        content: '您确定要把'+ userName +'禁言吗?他将30分钟内不能在房间内发言!',
        okValue: '禁言',
        ok: function(){
          that.props.userAction.callBanToPost( that.props.spreadUserPanel.userDatas.userId )
          that.closeUserPanel()
        },
        cancelValue: '取消',
        cancel: function(){}
      }).show();

    }

    //我的关注..
    JSXattention(){
        return <span key={ this.getHashPrefix() } onClick={ this.toAttention }>我的关注</span>
    }
    toAttention(){
        window.open('/member/attention')
    }

    //我的道具
    JSXscene(){
        return <span key={ this.getHashPrefix() } onClick={ this.toScene }>我的道具</span>
    }
    toScene(){
        window.open('/member/scene')
    }

    //消费记录
    JSXconsumerd(){
        return <span key={ this.getHashPrefix() } onClick={ this.toConsumerd }>消费记录</span>
    }
    toConsumerd(){
        window.open('/member/consumerd')
    }

    //马上充值..
    JSXwantUp(){
        return <span key={ this.getHashPrefix() } onClick={ this.toUp }>马上充值</span>
    }
    toUp(){
        window.open('/charge/order')
    }

    //进入房间
    JSXtoRoom(roomid ){
        return <span key={ this.getHashPrefix() } onClick={ ( )=>{ this.toRoom(roomid ) }}>进入房间</span>
    }
    toRoom(roomid){
        //目标房间与当前房间是否一致..
        if(roomid == this.props.userInfo.roomid){
            this.closeUserPanel();
            $.tips("已在当前房间..")
        }else{
            window.location.href = '/'+roomid+'/h5';
        }
    }

    //个人中心
    JSXuserCentral(){
        return <span key={ this.getHashPrefix() } onClick={ this.toUserCentral }>个人中心</span>
    }
    toUserCentral(){
        window.open('/member/index')
    }

    //@ 他人
    JSXat(){
        return <span key={ this.getHashPrefix() } onClick={ this.toAt } >@他</span>
    }
    toAt(){
        this.closeUserPanel();
        this.props.chatAction.atOtherPerson(this.props.spreadUserPanel.userDatas.userName);
    }

    //退出登录
    JSXlogOut(){
        return <span key={ this.getHashPrefix() } onClick={ this.toLogOut }>退出登录</span>
    }
    toLogOut(){
        window.location.href = "/logout";
    }

    JSXresult(){

        let finalResult = [];

        //从redux获得checkMsgStatus，如果为true，则显示个人资料面板
        let spreadUserPanel = this.props.spreadUserPanel;

        //获取定位用坐标
        let userPanelStyle = {
            top: spreadUserPanel.userDatas.pageY + 5,
            left: spreadUserPanel.userDatas.pageX + 5,
        }

        //确认当前用户身份
        let userInfo = this.props.userInfo;

        //主播
        if (userInfo.ruled == 3) {

            //如果主播点击自己....
            if (spreadUserPanel.userDatas.userId == userInfo.uid) {
                finalResult.push(this.checkMsg())
                finalResult.push(this.JSXattention())
                finalResult.push(this.JSXscene())
                finalResult.push(this.JSXconsumerd())
                finalResult.push(this.JSXwantUp())
                finalResult.push(this.JSXuserCentral())
                finalResult.push(this.JSXlogOut())
            } else {
                finalResult.push(this.checkMsg())
                finalResult.push(this.JSXsetManager())
                finalResult.push(this.JSXcancelManager())
                finalResult.push(this.JSXbanToSpeak())
                finalResult.push(this.JSXknockOut())
                finalResult.push(this.JSXat())
            }
        }

        //一般用户
        else {
            //如果用户点击自己....
            if (spreadUserPanel.userDatas.userId == userInfo.uid) {
                finalResult.push(this.checkMsg())
                finalResult.push(this.JSXattention())
                finalResult.push(this.JSXscene())
                finalResult.push(this.JSXconsumerd())
                finalResult.push(this.JSXwantUp())
                finalResult.push(this.JSXuserCentral())
                finalResult.push(this.JSXlogOut())
            } else {
                //用户点别人..可以踢/禁言  除主播外的任何人..
                finalResult.push(this.checkMsg())
                finalResult.push(this.JSXknockOut())
                finalResult.push(this.JSXbanToSpeak())
                finalResult.push(this.JSXat())
            }
        }
        let userPanelStatus = this.props.spreadUserPanel.isUserPanelOpen ? "block": "none";
        return (
            <div className={ styles.spreadWindow } style={{ display: userPanelStatus }}>
                <div className={ styles.closeUserPanel } onClick={ this.closeUserPanel }></div>
                <div className={ styles.userPanel } style={ userPanelStyle }>

                    { finalResult }

                </div>
            </div>
        )
    }

    render(){

        this.closeUserPanel = this.closeUserPanel.bind(this);
        this.toSetManager = this.toSetManager.bind(this);
        this.toCancelManager = this.toCancelManager.bind(this);
        this.toBanToSpeak = this.toBanToSpeak.bind(this);
        this.toKnockOut = this.toKnockOut.bind(this);
        this.toAt = this.toAt.bind(this);
        this.toCheckMsg=this.toCheckMsg.bind(this);



        return (
            <div >
                { this.JSXresult() }
            </div>
        )
    }
}

export default connect(mapStateToProps,mapDispatchToProps)(SpreadUserPanel);