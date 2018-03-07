/**
 * @description: flash中间件接口
 * @author: Young
 * @date: 2017/02/20
 */

import React, { Component } from "react";
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import * as commonAction from './actions/commonActions.js';
import * as chatAction from './actions/chatActions.js';
import * as giftAction from './actions/giftActions.js';
import * as animationActions from './actions/animationActions.js';
import * as userAction from './actions/userActions.js';
import errData from "./data/error.json";

const mapStateToProps = (state) => {
    return {
        userInfoData: state.userInfo,
        giftCurrentState: state.giftCurrentState,
        giftData:state.gifts.giftData,
        flyScreenOfText:state.flyScreenOfText,
        flyScreenOfRiding:state.flyScreenOfRiding,
        flyScreenOfGift:state.flyScreenOfGift,
        animationRouteList: state.gifts.animationRouteList
    }
}
const mapDispatchToProps = (dispatch) => {
    return {
        commonAction: bindActionCreators(commonAction, dispatch),
        chatAction:bindActionCreators(chatAction,dispatch),
        animationActions:bindActionCreators(animationActions,dispatch),
        giftAction:bindActionCreators(giftAction,dispatch),
        userAction: bindActionCreators(userAction, dispatch),
    }
}


class FlashMiddleWare extends Component{
    /**
     * 注释：方法返回后请求信息
     */
    componentDidMount(){
        let that = this;
        window.flashToJsFunction = function(data){
            //数据调试
            //console.log(JSON.stringify(data));

            //策略分片
            switch (data.cmd){

                /***************** common ********************/
                //接收用户数据
                case 10001:
                    //接收用户数据
                    that.updateUserInfo(data);

                    //获取礼物清单数据
                    that.getChatGiftList();

                    //检测限制房间
                    //that.getLimitRoomState(data);

                    //获取在线用户列表，管理员列表，VIP列表
                    that.fetchUserData();

                    //获取动画路径
                    that.loadAnimationRoutes();

                    //触发游戏自动加载
                    that.activeGameDialog(data);

                    //启动登录弹窗
                    that.showLoginDialog(data);

                    break;
                //接收限制房间数据
                case 10011:
                    //前端暂时不做限制房间
                    //that.updateLimitRoomData(data);
                    break;
                //接收直播大厅数据
                case 50001:
                    that.updateLiveHallList(data);
                    break;

                //接收在线观众数据
                case 11001:
                    that.generateUserList(data);
                    break;

                //接收在线管理员数据
                case 11008:
                    that.generateUserList(data);
                    break;
                //反馈错误提示
                // case 15555:
                //     that.updateErrTips(data);
                //     break;

                //接收日排行数据
                case 15001:
                    that.updateRankDayDataList(data);
                    break;

                //接收赠礼增量数据
                case 15002:
                    //更新日排行数据
                    that.updateRankDayItem(data);
                    break;
                //日排行钻石数加总
                case 15005 :
                    that.updateRankDayTotal(data);
                    break;
                //附加数据（用户聊天长度限制 / 时间间隔 / 字体颜色）
                case 10013:
                    that.attachUserInfo(data);
                    break;

                //接收贵宾席排行数据，现从在线用户数据处获取
                //case 18002:
                //    that.updateRankVipList(data);
                //    break;

                //接收停车位用户数据
                //case 17001:
                //    that.updateParkList(data);
                //    break;
                //接收活动列表数据
                case 15004:
                    that.updateActivityList(data);
                    break;
                /***************** common ********************/




                /***************** gift ********************/
                case 40001:

                    let {gid = false, gnum = 1 } = data;
                    let gidType = false;    //动画类型   'type / png '
                    let gidTime = false;    //动画执行   ' 0 / int  ' ( 0 应该对应 png类型 )

                    //获取当前礼物对应的 动画信息
                    if(gid && (typeof that.props.giftData[gid] == "object" )){
                        gidType = that.props.giftData[gid].type;
                        gidTime = that.props.giftData[gid].time;
                    }

                    // (swf)礼物飞屏..
                    if(gidType == "swf") {

                        //通知礼物飞屏组件  | 获取当前队列状态
                        const animationStatus = that.props.flyScreenOfGift.status;

                        switch(animationStatus){
                            //队列动画正在进行中
                            case 0:
                                that.props.animationActions.giftAddTemporyQueue({
                                    actionTime : 6000,
                                    contentText : gid,
                                    giftType:gidType
                                })
                                break;
                            //队列动画闲置中..
                            case 3:
                                that.props.animationActions.giftAddQueue({
                                    actionTime : 6000,
                                    contentText : gid,
                                    giftType:gidType
                                })

                                that.props.animationActions.giftChangeQueueStatus(0)
                                break;
                        }
                    }

                    //（IMG）图片礼物飞屏. + 组合飞屏
                    if(gidType == "png") {

                        let animationRouteFlag = false;

                        //播放路径动画
                        that.props.animationRouteList.map((item)=>{
                            if(gnum == item.num){
                                that.props.giftAction.updateCurrentGiftSet(data);
                                animationRouteFlag = true;
                            }
                        });

                        //播放普通图片懂法
                        if(!animationRouteFlag){
                            let gidObj = {
                                actionTime: 2000,
                                contentText: gid,
                                giftType: gidType,
                            };

                            //发送至action..
                            that.props.giftAction.imgGiftAddQueue(gidObj);
                        }

                    }

                    //更新礼物列表
                    that.updateGiftList(data);
                    //更新最新送礼信息
                    that.updateGiftGeneral(data);
                    break;

                case 40002:
                    //出发礼物轮播
                    that.updateGiftCarousel(data);
                    break;

                case 40003:
                    //奢华礼物上跑道
                    that.updateGiftLuxury(data);
                    break;
                /***************** gift ********************/



                /***************** user edit by young********************/
                case 11006:
                    /**
                     * 设置为管理员
                     * 若设置成功，会返回11006，设置不成功，不会返回任何socket
                     */
                    that.updateUserSetManager(data);
                    $.tips(data.name + "被设置为了管理员");
                    break;

                case 11007:
                    /**
                     * 取消管理员
                     * 若设置成功，会返回11007，设置不成功，不会返回任何socket
                     */
                    that.updateUserCancelManager(data);
                    //$.tips(data.name + "被取消了管理员");
                    break;

                case 10009:
                    //更新用户钻石点数
                    that.updateUserPoint(data);
                    break;

                case 18005:
                    //权限管理 禁言 T人
                    console.log("用户权限设置状态：" + data);
                    //that.updateRemoveRights(data);
                    break;

                /***************** user ********************/



                /***************** chat ********************/
                case 10003:
                //房间公告
                    that.updateRoomNotice(data)
                    break;
                case 11002:
                //用户进入 欢迎信息

                    //如果这位用户有坐骑的话   通知坐骑飞屏
                    if(Number(data.car) && data.car > 0 ){

                    //获取当前队列状态
                    const animationStatus = that.props.flyScreenOfRiding.status;

                    switch(animationStatus){
                        
                        //队列动画正在进行中
                        case 0:
                            that.props.animationActions.rideAddTemporyQueue({
                                actionTime : 6000,
                                contentText : data.car
                            })
                            break;

                        //队列动画闲置中..
                        case 3:
                            that.props.animationActions.rideAddQueue({
                                actionTime : 6000,
                                contentText : data.car
                            })

                            that.props.animationActions.rideChangeQueueStatus(0)
                            break;
                        }
                    }

                    //聊天窗口用户进入 欢迎信息
                    that.updateChatList(data);

                    //更新在线用户，管理员用户，vip窗口
                    that.updateUserList(data);

                    break;

                case 11003:

                    //更新在线用户，管理员用户，vip窗口
                    that.removeUserList(data);
                    break;

                case 30001:
                    that.updateChatList(data);
                    break;
                /***************** reload ******************/
                //主播上播状态
                case 80002:
                    that.updateMobileStatus(data);
                    break;
                /***************** chat ********************/


                /***************** game ********************/
                // case 60005:
                //     //通知开奖结果
                //     that.updateChatList(data);
                //     break;
                case 90003:
                    //接收用户数据
                    that.updateUserInfo(data);
                    break;
                default:
                    break;
            }
        };
    }

    /********************** common ***********************/
    updateUserSetManager(data){
        let item = {
            [data.uid]: data
        }
        this.props.commonAction.updateUserList(item);
    }

    updateUserCancelManager(data){
        data["ruled"] = 0;
        let item = {
            [data.uid]: data
        }
        this.props.commonAction.updateUserList(item);
    }

    loadAnimationRoutes(){
        this.props.giftAction.loadAnimationRoutes();
    }

    //根据headerUserInfo(10001接口)中的gamepop字段来判断游戏窗口是否自动弹出来（即进入直播间就显示出来）
    //如果gamepop='1'，就自动弹出来，否则就手动点击出现游戏窗口
    activeGameDialog(data){

      if( data.gamepop==='1' ){
        this.props.commonAction.openDialog('game')
      }
    }
    //更新用户权限
    //updateRemoveRights(data){
    //    this.props.userAction.setUserRights(data);
    //}

    //生成用户列表
    generateUserList(data){

        let list = {};
        data.items.map((item)=>{
            item["inRoom"] = true;
            list[item.uid] = item;
        });

        this.props.commonAction.updateUserList(list);

    }

    //获取用户信息，在线，管理员，vip
    fetchUserData(){
        this.props.commonAction.fetchOnlineManagerListData();
        this.props.commonAction.fetchOnlineListData();
    }

    removeUserList(data){
        this.props.commonAction.removeUserList(data);
    }

    //更新用户数据
    updateUserList(data){
        data["inRoom"] = true;
        let itemData = {
            [data.uid]: data
        }

        this.props.commonAction.updateUserList(itemData);
    }

    //header用户信息数据
    updateUserInfo(data){
        this.props.commonAction.updateUserInfo(data);
    }
    //limit限制房间数据
    getLimitRoomState(data){
        this.props.commonAction.fetchLimitRoomData(data);
    }
    updateLimitRoomData(data){
        this.props.commonAction.updateLimitRoomData(data)
    }
    //礼物清单数据
    getChatGiftList(){
        this.props.giftAction.fetchUpdateGiftList();
    }

    //附加数据（用户聊天长度限制 / 时间间隔 / 字体颜色）
    attachUserInfo(data){
        this.props.commonAction.attachUserInfo(data)
    }

    //sider直播大厅数据
    updateLiveHallList(data){
        this.props.commonAction.updateLiveHallList(data);
    }

    //日排行数据cmd:15001
    updateRankDayDataList(data){
        let rankList={};
        data.items.map((item)=>{
            rankList[item.uid] = item;
        })
        this.props.commonAction.generateDayUserData(rankList);
    }

    //更新日排行数据cmd:15002
    updateRankDayItem(data){
        this.props.commonAction.updateDayRankPoint({ [data.uid]: data });
    }

    //parking停车位数据
    updateParkList(data){
        this.props.commonAction.updateParkList(data)
    }
    //sider活动列表数据
    updateActivityList(data){
        this.props.commonAction.updateActivityList(data);
    }
    //更新header用户金额信息
    updateUserPoint(generalData){
        let userPoints = generalData.points;
        this.props.commonAction.updateUserPoints(userPoints);
    }

    //更新日排行钻石总数
    updateRankDayTotal(data){
        this.props.commonAction.updateRankDayTotal(data);
    }

    //显示登录弹窗
    showLoginDialog(data){
        if(data.ruled == -1){
            //游客观看超过1分钟显示登录弹窗
            setTimeout(function(){
              User.showLoginDialog();
            }, 60000);

        }
    }
    /********************** common ***********************/

    /********************** reload ***********************/
    //主播上播状态
    updateMobileStatus(data) {
        window._flashVars.roomOrigin == data.origin ? console.log('default') :  location.reload();
    }
    /********************** gift ***********************/

    //更新礼物信息列表(单条数据)
    updateGiftList(data){
        //40001数据由于后端接口定义不统一,需要前端重组数据
        data.vip = data.icon;

        //传参需传入数组，保证与http接口返回数据类型相同
        this.props.giftAction.updateGiftsList([data]);
    }

    //赠送豪华礼物
    updateGiftLuxury(data){
        this.props.giftAction.updateCurrentLuxury(data);
    }

    updateGiftCarousel(data){
        this.props.giftAction.updateCurrentCarousel(data);
    }

    updateGiftGeneral(data){
        this.props.giftAction.updateCurrentGeneral(data);
    }
    /********************** gift ***********************/

    /********************** chat ***********************/
    //更新信息列表 30001
    updateChatList(data){

        let that = this;
        //飞屏处理
        if(data.type == 9){

            //获取当前队列状态
            const animationStatus = this.props.flyScreenOfText.status;

            switch(animationStatus){

                  //队列动画正在进行中
                  case 0:
                        this.props.animationActions.addTemporyQueue({
                              actionName:"FlyScreen",
                              actionTime : 6300,
                              contentText : data.content,
                              sendName : data.sendName,
                            recName: data.recName, //主播名字
                            recUid: data.recUid, //主播id
                            sendHidden: data.sendHidden//是否是神秘人
                        })
                        break;
                  //队列动画闲置中..
                  case 3:
                        this.props.animationActions.addQueue({
                              data:[{
                                    actionName:"FlyScreen",
                                    actionTime : 6300,
                                    contentText : data.content,
                                    sendName : data.sendName,
                                  recName: data.recName, //主播名字
                                  recUid: data.recUid, //主播id
                                    sendHidden: data.sendHidden //是否是神秘人
                              }]
                        })
                        //变更状态为进行中
                        this.props.animationActions.changeQueueStatus(0);
                        break;
            }

        //因游戏设计问题, 将游戏消息延时10s处理
        }else if(data.type == 6){
            setTimeout(function(){
                that.props.chatAction.updateChatList(data);
            }, 10000);
        }else{
            this.props.chatAction.updateChatList(data);
        }
    }

    /********************** chat ***********************/

    /********************** notice ***********************/
    updateRoomNotice(data){
        this.props.chatAction.udpateNotice(data)
    }
    /********************** notice ***********************/


    render(){
        return (<div></div>);
    }
}

export const callFlash = (json) =>{
    let jsonString = JSON.stringify(json);
    document.getElementById("videoRoom")["jsSendObjectToServer"](jsonString);
}

export default connect(mapStateToProps, mapDispatchToProps)(FlashMiddleWare);
