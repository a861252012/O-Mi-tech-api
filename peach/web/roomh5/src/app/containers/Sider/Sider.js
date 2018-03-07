/**
 * @description:左侧边栏排列图标功能实现
 * @author: Merci
 * @date:2017/2/8
 */

import React, { Component } from "react";
import Draggable from "react-draggable";
import IconCommon from "../../components/Common/IconCommon/IconCommon.js";
import Dialog from "../../components/Common/Dialog/Dialog.js";
import LiveHall from "../../components/LiveHall/LiveHall.js";
import UserList from "../../components/UserList/UserList.js";
import ActivityListTab from "../../components/ActivityList/ActivityList.js";
import Common from "../../utils/Common.js";
import FlashGame from "../../components/FlashGame/FlashGame.js";
import styles from "./sider.css";

import {bindActionCreators} from "redux";
import {connect} from "react-redux";
import * as actions from "../../actions/commonActions.js";

const mapDispatchToProps=(dispatch)=>{
    return {
        actions:bindActionCreators(actions,dispatch)
    }
}

const mapStateToProps=(state)=>{
    return {
        userInfo: state.userInfo,
        dialogState: state.dialogState
    }
}

class RoomSider extends Component {

    //dialog 的显示关闭控制
    toggleDialog(key) {

        if( ( key === "hall" ) && !$.isEmptyObject(window.OpenAPI.link) ){

            window.open(window.OpenAPI.link.hall);

        }else{

            Object.keys(this.props.dialogState).map((item)=>{
                //互斥
                if(key == item){
                    this.props.actions.toggleDialogOpen(key);
                }else{
                    if(this.props.dialogState[item].only){
                        this.props.actions.closeDialog(item);
                    }
                }
            });

            if(!this.props.dialogState[key].open == true){
                switch (key){

                    case "activity":
                        this.props.actions.fetchActivityListData();
                        break;

                    case "hall":
                        this.props.actions.fetchLiveHallListData();
                        break;

                    default:
                        break;
                }
            }
        }
    }
    //关闭弹窗
    closeDialog(key) {
        this.props.actions.closeDialog(key);
    }

    // 跳转充值页面
    handleClickCharge(){
        Common.handleBtnCharge();
    }
    //跳转商城页面
    handleClickShopMall(){
        //调用的page-h5的js
        gomarket();
    }
    
    render() {

        let hallStyles = {
            width: "850px",
            height: "500px",
            top: "60px",
            right: "56px"
        };

        let onlineStyles = {
            width: "302px",
            height: "600px",
            top: "60px",
            right: "56px"
        };

        let activityDialog={
            width: "610px",
            height: "310px",
            top: "350px",
            right: "56px"
        };

        let gameStyles={
            width: "500px",
            height: "480px",
            background: "transparent",
            right: "56px"
        };


        //判断activityName是否存在，来设定活动图标的display值，并设定初始值，以便加载时图标会闪现
        let activityStyle={display:"none"};
        if(this.props.userInfo.activityName!==""){
            activityStyle={display:"block"}
        }

        //判断是否有XO路径存在，如果有存在，就不显示游戏图标
        let gameStyle = {};

        // if($.isEmptyObject(window.OpenAPI.link)){
        //     gameStyle={ display:"block" }
        // }else {
        //     gameStyle={ display:"none" }
        // }
        let userData = window.OpenMenu;

        gameStyle = { display: (userData !== 0 ? "none" : "block") };

        return (
            <div className={ styles.container }>
                <div className={ styles.inner }>
                    <div className={styles.siderItem} onClick={ (e)=> { this.toggleDialog("hall") }}>
                        <IconCommon iconType="sider" iconClass="sider_item_home"></IconCommon>
                        <div className={styles.iconText}>大厅</div>
                    </div>
                    <div className={styles.siderItem} onClick={()=>{ this.handleClickCharge() }}>
                        <IconCommon iconType="sider" iconClass="sider_item_bag"></IconCommon>
                        <div className={styles.iconText}>充值</div>
                    </div>
                    <div className={styles.siderItem} onClick={()=>{ this.handleClickShopMall() }}>
                        <IconCommon iconType="sider" iconClass="sider_item_shoppingcar"></IconCommon>
                        <div className={styles.iconText}>商城</div>
                    </div>
                    <div className={styles.siderItem} onClick={(e)=> { this.toggleDialog("online") }}>
                        <IconCommon iconType="sider" iconClass="sider_item_userInfo" ></IconCommon>
                        <div className={styles.iconText}>在线</div>
                    </div>

                    <div className={ styles.siderItem } onClick={(e)=> { this.toggleDialog("game") }}>
                        <IconCommon iconType="sider" iconClass="sider_item_game" ></IconCommon>
                        <div className={styles.iconText}>游戏</div>
                    </div>

                    <div className={ styles.siderItem } onClick={(e)=> { this.toggleDialog("app") }}>
                        <IconCommon iconType="sider" iconClass="sider_item_app" ></IconCommon>
                        <div className={ styles.txt }>
                            <img id="J_menuQrCode" className={ styles.imgCode }/>
                            <p className={ styles.qrCodep }>扫描下载APP</p>
                        </div>
                        <div className={styles.iconText}>下载</div>
                    </div>

                    <div
                        className={styles.siderItem}
                        onClick={(e)=> { this.toggleDialog("activity") }}
                        style={ activityStyle }
                    >
                        <IconCommon iconType="sider" iconClass="sider_item_activity" ></IconCommon>
                        <div className={styles.iconText}>活动</div>
                    </div>

                    <div className={ styles.mallDialog } id="mallDialog">
                        <Dialog dialogTitle="直播大厅"
                                dialogTitleSmall="美女直播"
                                dialogStyles={ hallStyles }
                                open={ this.props.dialogState.hall.open }
                                onRequestClose={ () => { this.closeDialog("hall") } }
                        >
                            <LiveHall></LiveHall>
                        </Dialog>
                    </div>
                    <div className={styles.onlineDialog} id="onlineDialog" >
                        <Dialog dialogStyles ={ onlineStyles }
                                open={ this.props.dialogState.online.open }
                                onRequestClose={ () => { this.closeDialog("online") }}
                        >
                            <UserList></UserList>
                        </Dialog>
                    </div>
                    <div className={ styles.activityContainer }>
                        <Dialog dialogStyles={ activityDialog }
                                 open={ this.props.dialogState.activity.open }
                                 onRequestClose={ () => { this.closeDialog("activity") }}
                                 theme="purple"
                        >
                            <ActivityListTab></ActivityListTab>
                        </Dialog>
                    </div>
                    <div className={ styles.activityContainer }>
                            <Draggable >
                                <div style={{ position:"absolute", zIndex:"100", bottom:"550px"}}>
                                    <Dialog dialogStyles = { gameStyles }
                                            open={ this.props.dialogState.game.open }
                                            dialogCloseClass={
                                              styles.closeGameDialog
                                            }
                                            onRequestClose={ () => { this.closeDialog("game") }}
                                    >
                                        <FlashGame />
                                    </Dialog>
                                </div>
                            </Draggable>
                    </div>
                </div>
            </div>
        )
    }
}

export default connect(mapStateToProps, mapDispatchToProps)(RoomSider);