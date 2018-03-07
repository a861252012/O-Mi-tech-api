/**
 * @description: 接收头部用户信息的组件
 * @author: Merci
 * @date: 2017/3/24
 */

import React, { Component } from "react";
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import * as commonActions from '../../../actions/commonActions.js';
import Button from "../../Common/Button/Button.js";
import IconCommon from "../../Common/IconCommon/IconCommon.js";
import UserPanel from "../../Common/UserPanel/UserPanel.js";
import Common from "../../../utils/Common.js";
import styles from "../../../containers/Header/header.css";


const mapDispatchToProps = (dispatch) => {
    return {
        commonActions: bindActionCreators(commonActions, dispatch)
    }
}

const mapStateToProps=(state)=>{
    return {
        userInfoData: state.userInfo
    }
}
class HeaderUserInfo extends Component{
    //跳转登录
    handleClickLogin(){
        User.showLoginDialog();
    }
    //跳转注册
    handleClickRegister(){
        //location.href="/op/index.php";
        User.showRegDialog();
    }
    //跳转充值页面
    handleClickCharge(){
        Common.handleBtnCharge();
    }
    //积分兑换
    handleClickExchange(){
        let userData = this.props.userInfoData;
        let that = this;
        $.dialog({
            title: '钻石兑换',
            content: `<div class=${styles.exchargeBox}>` +
            `<div class=${styles.exchargeLeft}>` +
            `<div class=${styles.exchargeLeftImg}></div>` +
            `</div>` +
            `<div class=${styles.exchargeRight}>` +
            `<p class=${styles.excharge}>亲爱的用户：</p>` +
            `<p class=${styles.excharge}>你可以将你的  <span class=${styles.roseRed}>` + userData.moneyName + `</span>  兑换成  <span class=${styles.roseRed}>蜜桃钻石</span></p>` +
            `<p class=${styles.excharge}>这样你就可以给你喜欢的主播送礼了</p>` +
            `<p class=${styles.textAlign}>当前兑换比例是  <span class=${styles.roseRed}>1 </span>` + userData.moneyName + ` = <span class=${styles.roseRed}>` + userData.exchangeRate + `</span> 蜜桃钻石</p>` +
            `<div>` +
            `<p class=${styles.excharge}>请输入你想兑换的金额</p>` +
            `<div class=${styles.textBlock}>` +
            `<span class=${styles.textTitle}>` + userData.moneyName + `</span>` +
            `<input type="number" id="amount" class=${styles.exchargeInput} name="amount" maxlength="12" step="0" min="0">` +
            `</div>` +
            `<div class=${styles.textFlag}>->` +
            `</div>` +
            `<div class=${styles.textBlock}>` +
            `<span class=${styles.textTitle}>蜜桃钻石</span>` +
            `<input type="text" id="diamond" name="diamond" disabled class=${styles.exchargeInput} maxlength="12">` +
            `</div>` +
            `<div class=${styles.exchargeBtn}>` +
            `<p class=${styles.excharge}>` + userData.moneyName + `余额：<span class=${styles.roseRed}>` + userData.score + `</span>，实时到账</p>` +
            `<button id="redeem" class=${styles.exchargeButton}>兑换</button>` +
            `<button id="chargePay" class=${styles.exchargeButton}>充值</button>` +
            `</div>` +
            `</div>` +
            `</div>` +
            `</div>`
        }).show();
            let query = {
                getId(id) {
                    return document.getElementById(id)
                },
                eventListener(obj, type, callback) {
                    return obj.addEventListener(type, callback);
                }
            }
            let amount = query.getId('amount');
            let diamond = query.getId('diamond');
            let redeem = query.getId('redeem');
            let chargePay = query.getId('chargePay');
            query.eventListener(amount, 'blur', () => {
                    diamond.value = Number(amount.value) * userData.exchangeRate;
                }
            )
            query.eventListener(chargePay, 'click', () => {
                    let payUrl = window.open();
                    payUrl.location.href = window.OpenAPI.link.pay;
                }
            )
            query.eventListener(redeem, 'click', () => {
                    that.props.commonActions.getRedeemDate(Number(amount.value));
                    $('.d-dialog,.d-shadow').remove();
                }
            )
    }
    render(){
        let userData = this.props.userInfoData;
        let attachStyle={
            marginLeft:"10px",
            marginRight:"10px"
        };
        let userStatus = window.OpenMenu;
        let scoreRate = userData.score > Math.pow(10,4) ? Math.trunc(userData.score / Math.pow(10,4)) + '万' : userData.score;
        let exchangeRate = userData.score * userData.exchangeRate;
        let newExchangeRate = exchangeRate > Math.pow(10,4) ? Math.trunc(exchangeRate  / Math.pow(10,4)) + '万' : exchangeRate;

        return (
            <div className={ userStatus !== 0 ? `${styles.headerUser} ${styles.headerWidth}` : `${styles.headerUser}`}>
                {userStatus !== 0 ?
                    <div className={ styles.headerFlex }>
                        <span className={`${styles.headerUserImg} ${styles.headerUserLeft}`}>
                    <img src={
                        (typeof userData.headimg == "undefined" || userData.headimg == "0" || userData.headimg == "null" || userData.headimg === "" ) ? window.CDN_HOST + "/roomh5/build/images/" + (userData.sex == 1? "user_male" : "user_female") + ".png" : window.IMG_HOST + "/" + userData.headimg
                    } />
                </span>
                        <UserPanel
                            userInfo={ userData }
                            attachStyle={ attachStyle }
                        ></UserPanel>
                        <div className={ styles.headerUserBalance }>
                            <p className={ styles.colP }>
                                蜜桃余额: <span className={styles.headerUserMoney}>{ userData.points }</span>
                                <IconCommon iconType="diamond" iconClass="diamondIcon"></IconCommon>
                            </p>
                            <p className={ styles.colP }>
                                { userData.coopPrefix }余额: <span className={`${styles.headerUserMoney} ${styles.txtBlance}`} title={ `余额` + userData.score }>{ scoreRate }</span>
                                { userData.moneyName } =  <span className={`${styles.headerUserMoney} ${styles.txtBlance}`} title={ `余额` + exchangeRate }>{ newExchangeRate }</span><IconCommon iconType="diamond" iconClass="diamondIcon"></IconCommon>
                            </p>
                        </div>
                        {
                            userData.ruled===-1 ? <div className={ styles.headerUserBtnBox }>
                                <Button text="登录" type="round" size="small" onHandleClick={ ()=>{this.handleClickLogin().bind(this) }} ></Button>
                                <Button text="注册" type="round" size="small" onHandleClick={ ()=>{this.handleClickRegister().bind(this) }} ></Button>
                            </div> :""
                        }
                        <div className={ styles.headerButtonBox }>
                        <Button text="充值" type="round" size="small" buttonClass={ styles.headerButtonExchange }onHandleClick={ this.handleClickCharge.bind(this) }></Button>
                        <Button text="兑换" type="round" size="small" buttonClass={ styles.headerButtonExchange }onHandleClick={ this.handleClickExchange.bind(this) }></Button>
                        </div>
                    </div> : <div>
                        <span className={styles.headerUserImg}>
                    <img src={
                        (typeof userData.headimg == "undefined" || userData.headimg == "0" || userData.headimg == "null" || userData.headimg === "" ) ? window.CDN_HOST + "/roomh5/build/images/" + (userData.sex == 1? "user_male" : "user_female") + ".png" : window.IMG_HOST + "/" + userData.headimg
                    } />
                </span>
                        <UserPanel
                            userInfo={ userData }
                            attachStyle={ attachStyle }
                        ></UserPanel>

                        <span className={styles.headerUserMoney}>{ userData.points }</span>
                        <IconCommon iconType="diamond" iconClass="diamondIcon"></IconCommon>

                        {
                            userData.ruled===-1 ? <div className={ styles.headerUserBtnBox }>
                                <Button text="登录" type="round" size="small" onHandleClick={ ()=>{this.handleClickLogin().bind(this) }} ></Button>
                                <Button text="注册" type="round" size="small" onHandleClick={ ()=>{this.handleClickRegister().bind(this) }} ></Button>
                            </div> :""
                        }
                        <Button text="充值" type="round" size="small" onHandleClick={ this.handleClickCharge.bind(this) } ></Button>
                        <a id="oldRoom" href= { "/"+userData.roomid } className={ styles.headerUserOldHttp } target="_blank">旧版入口</a>
                    </div>
                }
            </div>
        )
    }
}

export default connect(mapStateToProps, mapDispatchToProps)(HeaderUserInfo);