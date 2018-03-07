/**
 * Created by merci on 2017/5/2.
 */
/**
 * @description:此组件为点击用户查看资料时展开的资料面板
 **/
import React, { Component } from "react";
import {bindActionCreators} from 'redux';
import {connect} from "react-redux";
import * as commonActions from "../../../actions/commonActions.js";
import IconUser from "../IconUser/IconUser.js";
import Button from "../Button/Button.js";
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
        // userAction:bindActionCreators(userActions,dispatch),
        // chatAction:bindActionCreators(chatActions,dispatch)
    }
}
class UserInfoPanel extends Component{
    //获得不同的key
    getHashPrefix(){
        return Math.random().toString(16).slice(2,8) + "_" + Math.random().toString(16).slice(2,5);
    }
    //关闭当前窗口
    closeUserPanel(){
        // this.props.commonAction.setUserPanelStatus(false);
        this.props.commonAction.setCheckMsg(false);
    }

    //添加关注或者取消关注的方法
    handleAttention(){
        let focusMsg = this.props.spreadUserPanel.focusInfo.msg;
        //如果点击的用户id与登录id相同，表示为自己点自己，不允许关注自己
        if( this.props.userInfo.uid === this.props.spreadUserPanel.userDatas.userId ){
            $.tips("请勿关注自己");
            return;
        }else{
            if( focusMsg === "未关注" || focusMsg ==="取消关注成功"){
                //添加关注动作
                this.props.commonAction.fetchFocusInfo(this.props.spreadUserPanel.userDatas.userId,1);
            }else{
                //取消关注动作
                this.props.commonAction.fetchFocusInfo(this.props.spreadUserPanel.userDatas.userId,2);
            }
        }
    }
    render(){
        //获取定位用坐标
        let userPanelStyle = {
            top:this.props.spreadUserPanel.userDatas.pageY + 10,
            left:this.props.spreadUserPanel.userDatas.pageX + 5,
        }
        //获取被点击用户的uid
        // let userId= this.props.spreadUserPanel.userDatas.userId;

        //在点击查看资料时请求用户的信息

        let checkUserInfo = this.props.spreadUserPanel.checkCurrentUserInfo;

        //用户资料面板是否显示控制
        let userInfoPanel = this.props.spreadUserPanel.checkMsgStatus ? "block" : "none";

        //关注按钮文字：添加关注/取消关注
        let btnText = "";
        let focusMsg = this.props.spreadUserPanel.focusInfo.msg;
        btnText = (focusMsg === "未关注" || focusMsg ==="取消关注成功") ? "添加关注" : "取消关注" ;

        this.closeUserPanel = this.closeUserPanel.bind(this);
        this.handleAttention = this.handleAttention.bind(this);

        return (
            <div className={ styles.spreadWindow } style={{ display : userInfoPanel }}>
                <div className={ styles.closeUserPanel } onClick={ this.closeUserPanel } ></div>
                <div className={ styles.userPanel } style={ userPanelStyle }>
                    <div  className={ styles.msgPanel } id="msgPanel" key={ this.getHashPrefix() }>
                        <h3 className={ styles.msgPanelTitle }>个人信息</h3>
                        <div className={ styles.msgPanelHeader }>
                            <img src={ checkUserInfo.headimg }/>
                            <span>{ checkUserInfo.nickname }</span>
                            {checkUserInfo.vip !== "0" ? <IconUser type="vip" lv={ checkUserInfo.vip }></IconUser> : ""}
                            <IconUser  lv={ checkUserInfo.lv_rich }></IconUser>
                            <span></span>
                        </div>
                        <div className={ styles.msgPanelMain }>
                            <span>{ checkUserInfo.sex }</span>
                            <span>{ checkUserInfo.age }</span>
                            <span>{ checkUserInfo.starname }</span>
                            <span>{ checkUserInfo.procity }</span>
                        </div>
                        <div className={ styles.msgPanelAttention }>
                            <Button
                                text={ btnText }
                                type="round"
                                onHandleClick={ this.handleAttention }
                            >
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        );

    }
}
export default connect(mapStateToProps,mapDispatchToProps)(UserInfoPanel);