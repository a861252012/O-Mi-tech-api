/**
 * @description: 送礼选择窗口
 * @author: Young
 * @date: 2017/2/20
 */

import React, {Component} from "react";
import Button from "../../Common/Button/Button.js";
import IconCommon from "../../Common/IconCommon/IconCommon.js";
import GiftBoard from "../GiftBoard/GiftBoard.js";
import Common from "../../../utils/Common.js";

import GiftSelectPanel from "../GiftSelectPanel/GiftSelectPanel.js";

import styles from './giftControl.css';

import {bindActionCreators} from 'redux';
import {connect} from 'react-redux';
import * as giftActions from '../../../actions/giftActions.js';
import * as userActions from '../../../actions/userActions.js';

const mapStateToProps = (state) => {
    return {
        giftCurrentState: state.giftCurrentState,
        userInfo: state.userInfo,
        gifts: state.gifts
    }
}

const mapDispatchToProps = (dispatch) => {
    return {
        giftActions: bindActionCreators(giftActions, dispatch),
        userActions: bindActionCreators(userActions, dispatch)
    }
}

const MobileStyle = () => {
    if (window.isMobile) {
        return (
            <style type="text/css">
                {
                    '.giftControl-giftRoot{ padding-top: 0px }' +
                    '.giftControl-toolSelectBox{ margin-top: 30px}' +
                    '.giftControl-giftPanelContainer{ bottom: 270px; left: 640px; top: initial;}' +
                    '@media (max-width: 1450px){' +
                    '.giftControl-giftTool{ width: 180px }' +
                    '.giftControl-toolInput{ width: 170px }' +
                    '}' +
                    '@media (max-width: 1390px){' +
                    '.giftControl-giftTool{ width: 160px }' +
                    '.giftControl-toolInput{ width: 150px }' +
                    '}'
                }
            </style>
        )
    } else {
        return (
            <style type="text/css">
                {
                    "@media (min-height: 800px){" +
                    ".giftControl-giftTool{ float: none; width: auto; margin-top: 10px; }" +
                    ".giftControl-selectLabel{ width: 120px; display: inline-block; vertical-align: middle; margin: 0px;}" +
                    ".giftControl-selectForm{ display: inline-block; vertical-align: middle; }" +
                    ".giftControl-toolSelectBox{ margin-top: 0px; margin-left: 55px; margin-right: 20px; display: inline-block; vertical-align: middle; }" +
                    ".giftControl-toolBtnBox{ float: none; margin: 0px; display: inline-block; vertical-align: middle; }" +
                    "}" +

                    '@media (max-width: 1400px){' +
                    '.giftControl-giftTool{ width: 200px;}' +
                    '.giftControl-toolSelectBox{ margin-top: 32px; }' +
                    '}' +
                    '@media (max-width: 1400px) and (min-height: 800px){' +
                    '.giftControl-selectLabel{ width: auto }' +
                    '.giftControl-giftTool{ width: auto }' +
                    '.giftControl-toolSelectBox{ margin-top: 0px}' +
                    '}'
                }
            </style>
        )
    }
}

class GiftControl extends Component {

    //input框输入礼物并选定的方法
    handleInputGiftSendNum(giftNumber) {
        let inputNumber = "";
        //判断是否是非法数字
        if (!this.handleInputNumber(giftNumber) || Number(giftNumber) <= 0) {
            inputNumber = "";
        } else {
            inputNumber = Number(giftNumber);
        }
        this.props.giftActions.updateGiftInputNumber(inputNumber);
    }

    //发送选定礼物的方法
    handleSend() {
        let input = this.refs.input;

        this.handleInputGiftSendNum(input.value);

        this.sendGift();
    }

    //跳转充值页面
    handleClickCharge() {
        Common.handleBtnCharge();
    }

    //处理礼物board的双击事件
    handleBoardClick() {
        //发送礼物
        this.sendGift();
    }

    //对传过来的礼物数量进行正则匹配，只能是正整数
    handleInputNumber(giftNumber) {
        let reg = /^[0-9]*[1-9]\d*$/;
        return reg.test(giftNumber);
    }

    /**
     * sendGift 发送礼物
     * @param userInfo 用户信息
     * @param giftCurrentState 用户最近选择的信息
     * @param actions 操作
     */
    sendGift() {
        //初始化
        let {giftCurrentState, giftActions, userInfo, gifts} = this.props;
        let gid = giftCurrentState.select.gid;
        let price = giftCurrentState.select.price;
        let gnum = giftCurrentState.inputNumber;
        let currentSelect = giftCurrentState.select;
        let animationRouteList = gifts.animationRouteList;
        let points = userInfo.points;
        let isGiftEffect = false; //会员礼物特效判断
        let giftData = gifts.giftData;
        let that = this;

        //发送拦截（未登录游客）
        if (userInfo.ruled == -1) {
            Common.handleUnlogin("登录后即可送礼喔!");
            return;
        }

        //发送拦截（没有选择礼物）
        if (!this.handleInputNumber(gnum)) {
            $.tips("请输入正确的数量");
            return;
        }

        if (gid == "") {
            $.tips("请选择一个礼物赠送哟");
            return;
        }

        //发送拦截（主播不能给自己送礼）
        if (userInfo.uid === userInfo.roomid) {
            $.tips("主播不能给自己送礼");
            return;
        }

        //余额不足
        if (price > points) {
            $.dialog({
                title: "提示",
                content: "您的钻石不足",
                okValue: "立即充值",
                ok: function () {
                    location.href = "/charge/order"
                }
            }).show();

            return;
        }

        // 贵族礼物拦截
        if (giftData[gid].category === 5 && userInfo.vip === 0) {
            $.dialog({
                title: "提示",
                content: "您必须成为贵族才能赠送贵族专属礼物！点击开启贵族!",
                okValue: "立即开通贵族",
                ok: function () {
                    Common.handleBtnVip(userInfo.uid, that.props.userActions.sendVIPMessage);
                }
            }).show();
            return;
        }

        // 动画礼物贵族拦截
        animationRouteList.map((item) => {
            //发送拦截（如果不是VIP却选了VIP礼物动画，弹出提示）
            if (item.isVip && (userInfo.vip === 0) && gnum === item.num) {
                $.dialog({
                    title: "提示",
                    content: "您必须成为贵族才能赠送贵族专属礼物轨迹！点击开启贵族!",
                    okValue: "立即开通贵族",
                    ok: function () {
                        Common.handleBtnVip(userInfo.uid, that.props.userActions.sendVIPMessage);
                    }
                }).show();
                isGiftEffect = true;
            }

            //动画礼物不能有礼物特效，后面依照需求而定
            // if(currentSelect.type == 'swf' && gnum === item.num){
            //     $.tips("亲，该礼物为动画礼物，没有礼物特效哟");
            //     isGiftEffect = true;
            // }
        });

        if (isGiftEffect) {
            return;
        }

        //send to server
        giftActions.sendGift({
            gid: gid,//送礼礼物ID
            uid: userInfo.roomid, //用主播ID
            gnum: gnum, //送礼礼物数
        });
    }

    //实现点击btn按钮时显示礼物动画面板
    handleGiftSelectPanel() {

        this.props.giftActions.updateGiftSelectPanelStatus(true);
    }

    //实现点击非礼物动画面板时(礼物动画面板之外部分)关闭礼物动画面板
    handleCloseSelectPanel() {

        this.props.giftActions.updateGiftSelectPanelStatus(false);
    }

    render() {

        let giftSelectPanelStatus = (this.props.giftCurrentState.giftSelectPanelStatus) ? {display: "block"}
            : {display: "none"}

        return (
            <div className="giftControl-giftRoot">
                <MobileStyle/>
                <div className={styles.vipGift} style={giftSelectPanelStatus}>
                    <div className={styles.closeGiftPanel} onClick={this.handleCloseSelectPanel.bind(this)}></div>
                    <div className="giftControl-giftPanelContainer">
                        <GiftSelectPanel></GiftSelectPanel>
                    </div>
                </div>

                <GiftBoard onHandleSend={this.handleBoardClick.bind(this)}></GiftBoard>
                <div className="giftControl-giftTool">

                    <div className="giftControl-toolSelectBox">
                        <div className="giftControl-selectLabel">
                            <div className={styles.toolTitle}>赠送:</div>
                            <label htmlFor="toolSelect"
                                   className={styles.toolSelectLabel}>{this.props.giftCurrentState.select.name}</label>
                        </div>
                        <div className="giftControl-selectForm">
                            <div className={styles.inputContainer}>
                                <Button buttonClass={styles.btnPanelActive} type="square" text="礼物特效" size="small"
                                        onHandleClick={this.handleGiftSelectPanel.bind(this)}></Button>
                                <input
                                    ref="input"
                                    type="text"
                                    value={this.props.giftCurrentState.inputNumber}
                                    //value="1"
                                    id="toolInput"
                                    placeholder="请输入数量"
                                    className="giftControl-toolInput"
                                    onChange={(e) => {
                                        this.handleInputGiftSendNum(e.target.value)
                                    }}
                                />
                            </div>
                        </div>
                    </div>
                    <div className="giftControl-toolBtnBox">
                        <Button type="square" text="赠送" size="middle" color="purple" buttonClass="purpleButton"
                                onHandleClick={this.handleSend.bind(this)} style={{marginLeft: "0px"}}></Button>
                        <Button type="square" text="充值" size="middle"
                                icon={<IconCommon iconClass="charge"></IconCommon>}
                                onHandleClick={this.handleClickCharge.bind(this)}></Button>
                    </div>
                </div>
            </div>
        )
    }

}

export default connect(mapStateToProps, mapDispatchToProps)(GiftControl);