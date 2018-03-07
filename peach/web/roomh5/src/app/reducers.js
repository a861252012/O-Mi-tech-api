/**
 * reducers 指明应用如何更新 state
 * 不要在reducers里面做如下操作：
 * 1. 修改传入参数
 * 2. 执行有副作用的操作，API请求或者路由跳转
 * 3. 调用非纯函数，如Date.now()
 */

import {combineReducers} from "redux";
import Common from "./utils/Common.js";

/*********************聊天部分*******************/
import {
    UPDATE_CHAT_LIST,
    UPDATE_CHAT_GIFT_LIST,
    UPDATE_NOTICE,
    CHANGE_EXPRESSION,
    UPDATE_KW,
    AT_OTHER_PERSON,
} from './actions/chatActions.js';

/******************** 用户部分 ******************/
// import {
//     UPDATE_USER_CURRENT_ENTRANCE,
//     UPDATE_USER_CURRENT_EXIT
// } from './actions/userActions.js';

/*********************礼物部分*******************/
import {
    UPDATE_GIFT_LIST,
    UPDATE_GIFT_BOARD_LIST,
    UPDATE_GIFT_DATA,
    UPDATE_GIFT_BOARD_TABINDEX,
    UPDATE_GIFT_BOARD_SLIDERINDEX,
    UPDATE_GIFT_CURRENT_SEND,
    UPDATE_GIFT_CURRENT_IMG,
    UPDATE_GIFT_CURRENT_LUXURY,
    UPDATE_GIFT_CURRENT_CAROUSEL,
    UPDATE_GIFT_CURRENT_GIFTSET,
    UPDATE_GIFT_CURRENT_GENERAL,
    UPDATE_GIFT_INPUT_NUMBER,
    GIFT_SELECT_PANEL_STATUS,
    UPDATE_ANIMATION_ROUTES,
} from './actions/giftActions.js';

/*********************common部分*******************/
import {
    UPDATE_USER_LIST,
    UPDATE_LIVEHALL_LIST,
    UPDATE_USER_INFO,
    UPDATE_USER_POINT,
    ATTACH_USER_INFO,
    UPDATE_ACTIVITY_LIST,

    GENERATE_GIFT_DAY_USER_DATA,
    GENERATE_GIFT_WEEK_USER_DATA,
    UPDATE_DAY_RANK_POINT,

    REMOVE_USER_LIST,
    UPDATE_LIMIT_ROOM,
    SET_USER_PANEL,
    SET_CHECK_MSG,
    USER_PANEL_STATUS,
    CHECK_USER_INFO,
    GET_FOCUS_INFO,
    UPDATE_RANK_DAY_TOTAL,
    TOGGLE_DIALOG,
    OPEN_DIALOG,
    CLOSE_DIALOG,
    SET_VERSION
} from './actions/commonActions.js';

/*********************动画部分*******************/

import {
    ADD_QUEUE,
    ADD_TEMPORY_QUEUE,
    CHANGE_QUEUE_STATUS,
    TRANSFER_DATA,
    RIDE_ADD_QUEUE,
    RIDE_ADD_TEMPORY_QUEUE,
    RIDE_CHANGE_QUEUE_STATUS,
    RIDE_TRANSFER_DATA,
    GIFT_ADD_QUEUE,
    GIFT_ADD_TEMPORY_QUEUE,
    GIFT_CHANGE_QUEUE_STATUS,
    GIFT_TRANSFER_DATA,
    TOGGLE_ANIMATION_STATE,
    CLEAR_ALL_ANIMATION
} from './actions/animationActions.js';

/*********************用户部分 Young *******************/


/*********************聊天部分 Seed *******************/

//聊天公共
const chat = (state = {list:[]}, action) =>{
    switch(action.type){
        //返回新的聊天列表
        case UPDATE_CHAT_LIST:
            let newList = state.list.concat(action.data);
            return Object.assign({},state,{list:newList});
        default:
            return state;
    }
}

//房间公告
const chatRoomNotice = (state = {}, action) =>{
    switch(action.type){
        case UPDATE_NOTICE:
            return action.noticeContent;
        default:
            return state;
    }
}

//表情控件开关
const chatExpression = (state = false , action) =>{
    switch(action.type){
        case CHANGE_EXPRESSION:
            return action.expression
        default:
            return state;
    }
}

//替换关键字 (字符串)
const kw = (state = "",action ) =>{
    switch (action.type){
        case UPDATE_KW:
            return Object.assign({},state,action.kwContent)
        default:
            return state;
    }
}
// @ 别人..
const chatAt = (state={ userName:false },action )=>{
    switch (action.type){
        case AT_OTHER_PERSON:
            return Object.assign({},state,{userName:action.userName})
        default:
            return false;
    }
}


/**************************** 动画特效部分 Seed  *************************/

//文字飞屏（status为3，飞屏队列闲置，status为0，队列进行，temporyData存储还未进行的飞屏数据）
const flyScreenOfText = (state = {status:3, data:[], temporyData:[]} , action )=>{
    switch(action.type){
        //新增一条队列
        case ADD_QUEUE:
            return Object.assign({},state,action.data);

        //新增一条缓存队列
        case ADD_TEMPORY_QUEUE:
            let temporyData = [...state.temporyData, ...[action.temporyData]];
            return Object.assign({},state,{
                temporyData: temporyData
            });

        //将缓存队列迁移到正式队列,并且将缓存队列清空
        case TRANSFER_DATA:
            return Object.assign({},state,action.data);

        //更改队列状态
        case CHANGE_QUEUE_STATUS:
            return Object.assign({},state,action.data);

        default:
            return state;
    }
}

//坐骑飞屏
const flyScreenOfRiding = (state = { status:3, data:[], temporyData:[]} , action )=> {
    switch(action.type){
        //新增一条队列
        case RIDE_ADD_QUEUE:
            return Object.assign({},state,{
                data:[action.data]
            });

        //新增一条缓存队列
        case RIDE_ADD_TEMPORY_QUEUE:
            let temporyData = [...state.temporyData, ...[action.temporyData]];
            return Object.assign({},state,{
                temporyData: temporyData
            });

        //将缓存队列迁移到正式队列,并且将缓存队列清空
        case RIDE_TRANSFER_DATA:
            return Object.assign({},state,action.data);

        //updateDayRankPoint
        //更改队列状态
        case RIDE_CHANGE_QUEUE_STATUS:
            return Object.assign({}, state, {
                status: action.data
            });

        //清空队列
        case CLEAR_ALL_ANIMATION:
            return Object.assign({}, state, {
              temporyData: []
            });

        default:
            return state;
    }
}

//(swf 格式)礼物飞屏
const flyScreenOfGift = (state = {status:3, data:[], temporyData:[]} , action )=> {
    switch(action.type){
        //新增一条队列
        case GIFT_ADD_QUEUE:
            return Object.assign({},state,{
                data:[action.data]
            });

        //新增一条缓存队列
        case GIFT_ADD_TEMPORY_QUEUE:
            let temporyData = [...state.temporyData, ...[action.temporyData]];
            return Object.assign({},state,{
                temporyData: temporyData
            });

        //将缓存队列迁移到正式队列,并且将缓存队列清空
        case GIFT_TRANSFER_DATA:
            return Object.assign({},state,action.data);

        //updateDayRankPoint
        //更改队列状态
        case GIFT_CHANGE_QUEUE_STATUS:
            return Object.assign({},state,action.data);

        //清空队列
        case CLEAR_ALL_ANIMATION:
            return Object.assign({}, state, {
              temporyData: []
            });

        default:
            return state;
    }
}

//是否开启动画
const animationState = (state = true, action) => {
    switch (action.type){
      case TOGGLE_ANIMATION_STATE:
          return !state;
      default:
          return state;
    }
}

/**************************** 礼物部分 Young *************************/
const initGifts = {
    //礼物board数据
    giftBoard: {},
    //礼物数据
    giftData: {},
    //送礼列表
    giftList: [],
    //礼物路径数据
    animationRouteData: {},
    //礼物路径
    animationRouteList: []
}

const gifts = (state = initGifts, action) => {
    switch (action.type){

        case UPDATE_GIFT_BOARD_LIST:
            return Object.assign({}, state, {
                giftBoard: action.giftBoard
            });

        case UPDATE_GIFT_DATA:
            return Object.assign({}, state, {
                giftData: action.giftData
            });

        case UPDATE_GIFT_LIST:
            //从ajax接口过来的数据结构:
            // {
            //     created:"11:07",
            //     gid:"310002",
            //     gname:"么么哒",
            //     gnum:"3",
            //     hidden:"1",
            //     richLv:"23",
            //     sendName:"young1",
            //     sendUid:"2650045",
            //     vipLv:"1107"
            // }

            //从40001过来的数据结构:
            //   cmd:40001
            //   created:"12:14"
            //   gid:311026
            //   gnum:1
            //   goodCategory:1
            //   icon:1107
            //   lv:12
            //   price:1
            //   recHidden:0
            //   recIcon:0
            //   recLv:10
            //   recName:"哈士奇RABY1"
            //   recRichLv:24
            //   recRuled:3
            //   recUid:2650010
            //   richLv:23
            //   roomid:2650010
            //   ruled:0
            //   sendHidden:1
            //   sendName:"young1"
            //   sendUid:2650045

            let concatGiftList = [...state.giftList, ...action.giftSendItem];
            concatGiftList.map((item)=>{
                item.cmd = 40001;
            });
            return Object.assign({}, state, {
                giftList: concatGiftList
            });

        case UPDATE_ANIMATION_ROUTES:
            let animationList = {};
            let animationKeys = [];
            $(action.animationList).find('item').each(function(i, e){
                const text = $.trim($(e).text());
                const num = $(e).attr("num");
                const name = $(e).attr("name");
                const isVip = $(e).attr("isVIp") == "true" ? true : false;
                const composeArray = [];

                text.split(",").map((m)=>{
                    const coordinate = m.split("_");
                    composeArray.push({
                        x: coordinate[0],
                        y: coordinate[1],
                        scaleX: coordinate[2],
                        scaleY: coordinate[3],
                        rotate: coordinate[4]
                    });
                });

                animationList[num] = composeArray;
                animationKeys.push({
                    name: name,
                    num: Number(num),
                    isVip: isVip
                });
            });

            return Object.assign({}, state, {
                animationRouteData: animationList,
                animationRouteList: animationKeys
            });

        default:
            return state;

    }
}

//初始tabIndex及sliderIndex状态值
const initGiftBoardState = {
    tabIndex: 0,
    sliderIndex: {
        category1: 0,
        category5: 0,
        category2: 0,
        category3: 0,
        category4: 0
    }
}
//tab切换及翻页功能
const giftBoardState = (state = initGiftBoardState, action) => {
    switch (action.type){
        case UPDATE_GIFT_BOARD_TABINDEX:
            return Object.assign({}, state, { tabIndex: action.tabIndex });
        case UPDATE_GIFT_BOARD_SLIDERINDEX:
            return Object.assign({}, state, { sliderIndex: action.sliderIndex });
        default:
            return state;
    }
}

/**  发送礼物功能  **/

//初始化礼物数据为空
const initGiftCurrent = {
    //当前选择
    select:{
        "gid":'',
        "price":'',
        "category":'',
        "name":"",
        "desc":"",
        "sort":'',
        "time":'',
        "playType":"",
        "type":"",
        "isNew":'',
        "gnum": 1
    },
    //数量
    inputNumber: 1,
    //礼物路径面板状态
    giftSelectPanelStatus: false,


    //普通图片礼物列表
    giftImgList: [],
    //轮播礼物列表
    carouselList: [],
    //动画路径礼物列表
    giftSetList: [],

    //礼物（包含所有礼物）
    general: {
        price: 0,
        gnum: 1,
    },
    //豪华礼物
    luxury: {},

    //用户日数据
    userDataDay: {},
    //用户周数据
    userDataWeek: {},
    //用户当日赠送总数
    userDayTotal: 0
}

const giftCurrentState = (state = initGiftCurrent, action) => {
    switch (action.type){
        case UPDATE_GIFT_CURRENT_SEND:
            return Object.assign({}, state, {
                select: action.giftInfo
            });

        case UPDATE_GIFT_INPUT_NUMBER:
            return Object.assign({}, state, {
                inputNumber: action.inputNumber
            });

        case GIFT_SELECT_PANEL_STATUS:
            return Object.assign({}, state, {
                giftSelectPanelStatus: action.giftSelectPanelStatus
            });

        case UPDATE_GIFT_CURRENT_GENERAL:
            return Object.assign({}, state, {
                general: action.generalItem
            });

        case UPDATE_GIFT_CURRENT_LUXURY:
            return Object.assign({}, state, {
                luxury: action.luxuryItem
            });

        case UPDATE_GIFT_CURRENT_IMG:
            let newData = [...state.giftImgList, ...[action.data]];
            return Object.assign({}, state, {
                giftImgList: newData
            });

        case UPDATE_GIFT_CURRENT_CAROUSEL:
            let carouselList = state.carouselList.concat({
                gid: action.carouselItem.gid,
                sendName: action.carouselItem.sendName,
                sendHidden: action.carouselItem.sendHidden,
                gnum: action.carouselItem.gnum
            });

            return Object.assign({}, state, {
                carouselList: carouselList
            });

        case UPDATE_GIFT_CURRENT_GIFTSET:
            let giftSetList = state.giftSetList.concat({
                gid: action.giftSetItem.gid,
                gnum: action.giftSetItem.gnum
            });

            return Object.assign({}, state, {
                giftSetList: giftSetList
            });

        case GENERATE_GIFT_DAY_USER_DATA:
            return Object.assign({}, state, {
                userDataDay: action.userDataDay
            });

        case GENERATE_GIFT_WEEK_USER_DATA:
            return Object.assign({}, state, {
                userDataWeek: action.userDataWeek
            });

        //更新单条用户数据
        case UPDATE_DAY_RANK_POINT:
            let composeDayData = Object.assign({}, state.userDataDay, action.dayItem);
            //score
            return Object.assign({}, state, {
                userDataDay: composeDayData
            });

        //更新排行版日总计
        case UPDATE_RANK_DAY_TOTAL:
            return Object.assign({}, state, {
                userDayTotal: action.rankTotal.total
            });

        case CLEAR_ALL_ANIMATION:
            return Object.assign({}, state, {
                giftImgList: [],
                carouselList: [],
                giftSetList: []
            });

        default:
            return state;
    }
}


/**************************** 直播大厅部分**************************/
const initLiveData={
    "cmd":"",
    "items":[],
    "total":""
}

const siderLiveHall = (state = initLiveData, action) => {
    switch(action.type){
        case UPDATE_LIVEHALL_LIST:
            return Object.assign({},state,action.LiveHallList)
        default:
            return state;
    }
}
/**************************** 在线观众部分**************************/

const initUsers = {
    userData: {},
    onlineList: [],
    managerList: [],
    vipList: [],
    parkList: [],
}

const users = (state = initUsers, action) => {
    switch (action.type){

        case UPDATE_USER_LIST:
            /**
             * 传入action格式：{ uid: object data } => { 10000: { mame: "test", vip: 1103} }
             */
            //生成userData数据
            let userData = Object.assign({}, state.userData);
            let userItem = action.userData;

            //数据覆盖
            Object.keys(userItem).map((key) => {

                if(state.userData[key]){
                    //如果原数据中存在
                    userData[key] = Object.assign({}, state.userData[key], userItem[key]);

                }else{
                    //如果原数据中不存在
                    userData[key] = userItem[key];
                }

                //标记为在房间
                // userData[key] = Object.assign({}, userData[key], {
                //     inRoom: true
                // })
            });

            //在线数据数组
            let onlineArr = [];
            let onlineObj = {};
            //管理员数据数组
            let managerArr = [];
            let managerObj = {};
            //vip数据数组
            let vipArr = [];
            let vipObj = {};
            //parking
            let parkArr = [];
            let parkObj = {};

            //判断主播是否在线
            let isAnchorOnline=false;

            Object.keys(userData).map((key)=>{
                //ruled == 3 主播， ruled == 2 管理员
                if(!userData[key].hidden && userData[key].inRoom) {
                  if (userData[key].ruled == 3 || userData[key].ruled == 2) {
                    managerObj[key] = userData[key];
                  } else {
                    onlineObj[key] = userData[key];
                  }
                }

                //vip列表
                if(userData[key].vip !== 0 && !userData[key].hidden && userData[key].inRoom){
                    vipObj[key] = userData[key];
                }

                //停车场, 神秘人可以在停车场显示车辆
                if(userData[key].car !== 0 && userData[key].inRoom){
                    parkObj[key] = userData[key];
                }

                //判断主播是否在线
                if(parseInt(key)===window.ROOMID){
                    isAnchorOnline=true;
                }
            });

            onlineArr = Common.convertObjToArray(onlineObj, "vip");
            managerArr = Common.convertObjToArray(managerObj, "vip");
            /**
             * 将主播放在第一个
             */

            if(isAnchorOnline===true){
                //从数组中移除主播
                managerArr = Common.arrayRemoveElement(managerArr, window.ROOMID);
                //拼接到头部
                managerArr = [...[window.ROOMID], ...managerArr];
            }

            vipArr = Common.convertObjToArray(vipObj, "vip");
            parkArr = Common.convertObjToArray(parkObj, "vip");

            return Object.assign({}, {
                userData: userData,
                onlineList: onlineArr,
                managerList: managerArr,
                vipList: vipArr,
                parkList: parkArr
            });

        case REMOVE_USER_LIST:

            let removeUserData = {};
            let removeId = action.userItem.uid;

            //如果离开的用户在列表中
            if(state.userData[removeId]){
                //inRoom 用于标记是否在房间里面
                //不在房间里面数据也会在userData里面, inRoom标记为false, 该标记可以处理游客问题
                let userItem = Object.assign({}, state.userData[removeId], {
                    inRoom: false
                });

                removeUserData = Object.assign({}, state.userData, {
                    [removeId]: userItem
                })
            }
            //如果用户离开时不在userData中(如主播刚刷新的情况下, "游客"就离开)
            else{
                removeUserData = state.userData;
            }

            let removeOnlineArr = [...[], ...state.onlineList];
            let removeManagerArr = [...[], ...state.managerList];
            let removeVIPArr = [...[], ...state.vipList];
            let removeParkArr = [...[], ...state.parkList];

            return Object.assign({}, {
                userData: removeUserData,
                onlineList: Common.arrayRemoveElement(removeOnlineArr, removeId),
                managerList: Common.arrayRemoveElement(removeManagerArr, removeId),
                vipList: Common.arrayRemoveElement(removeVIPArr, removeId),
                parkList: Common.arrayRemoveElement(removeParkArr, removeId)
            });

        default:
            return state;
    }
}

//用户展开二级菜单相关
//初始化
let initState={
    userDatas:{
        pageX: 0,
        pageY: 0
    },
    checkCurrentUserInfo:{},//查看用户资料信息
    checkMsgStatus:false,//控制用户资料面板的开关
    isUserPanelOpen:false,//控制用户面板的开关
    focusInfo:{}//关注按钮请求的信息
}
//赋值
const spreadUserPanel = (state = initState, action)=>{
    switch (action.type){
        case SET_USER_PANEL:
            return Object.assign({},state,{userDatas:action.data});
        case SET_CHECK_MSG:
            return Object.assign({},state,{checkMsgStatus:action.data});
        case USER_PANEL_STATUS:
            return Object.assign({},state,{isUserPanelOpen:action.isUserPanelOpen});
        case CHECK_USER_INFO:
            //调用flash的方法对ajax返回的数据进行解密输出（输出结果为对象）
            let checkMsg = document.getElementById("videoRoom").decodeUserInfo(action.checkUserData.info)
            return Object.assign({},state,{checkCurrentUserInfo:JSON.parse(checkMsg)});

        case GET_FOCUS_INFO:
            return Object.assign({},state,{focusInfo:action.focusInfo});
        default:
            return state;
    }

}

/**************************** 活动列表数据部分***************/

const initActivityList=[]
const siderActivityList = (state=initActivityList,action)=>{
    switch (action.type){
        case UPDATE_ACTIVITY_LIST:
            return  action.activityList ;
            break;
        default:
            return state;
    }
}
/********************************限制房间数据**********************************/
const limitRoomData=(state={},action)=>{
    switch (action.type){
        case UPDATE_LIMIT_ROOM:
            return Object.assign({},state,action.limitRoomData);
        default:
            return state;
    }
}
/**************************** 用户信息部分**************************/
const initUserInfo={
    "uid":0,
    "vip":0,
    "hidden":0,
    "richLv":0,
    "car":0,
    "score":0,
    "exchangeRate":0,
    "sendName":"",
    "icon":0,
    "lv":0, //和anchorLv一样为主播等级
    "anchorLv": 0, //主播等级
    "chatlimit":false,
    "chatsecond":false,
    "headimg": "0",
    "activityName":"",
}
const userInfo = (state=initUserInfo, action) => {
    switch(action.type){
        case UPDATE_USER_INFO:
            return Object.assign({}, state, action.userInfoData, {
                anchorLv: action.userInfoData.lv //主播等级
            })

        case ATTACH_USER_INFO:
            //附加数据至UserInfo  （用户聊天长度限制 / 时间间隔 / 字体颜色）
            const attachData = action.userInfoData;
            const newUserInfo = Object.assign({},state,{
                aschateffect:attachData.aschateffect,//(是否有聊天特效(0无,1有)
                chatsecond:attachData.chatsecond,   //(聊天文字时间限制,单位为秒,例：5（代表5秒一次）),
                chatlimit:attachData.chatlimit,     //(聊天文字长度限制单位为个,例：5(一次仅能输出5个文字))
                hasvipseat:attachData.hasvipseat,   //(房间是否有贵宾席(0无,1有)),
                nochatlimit:attachData.nochatlimit, //(禁言别人的权限,0 无权限,大于0的正整数代表每天可以禁言次数；例：  5（每天可禁言5次）,10（每天可禁言10次）)
                letout:attachData.letout,           //(踢人的权限,0 无权限,大于0的正整数 代表每天可以踢人次数；例：  5（每天可踢5次），10（每天可踢10次）)
                color:attachData.color,             //(#FFFFFF这样的字符串)，为空字符则不做颜色修饰 —>
                limitRoomCount:attachData.limitRoomCount    //今日还能进入限制房间数量
            });
            return newUserInfo;

        case UPDATE_USER_POINT:
            return Object.assign({}, state, {
                points: action.point
            });
        default:
            return state;
    }
}

//原始数据
const originalData = (state = {}, action)=>{
    switch (action.type){
        default:
            return state;
    }
}

//dialog状态
const initDialogState = {
    hall: {
        open: false, //是否显示
        only: true  //显示唯一
    },
    online: {
        open: false,
        only: true
    },
    activity: {
        open: false,
        only: true
    },
    game: {
        open: false,
        only: true
    }
}
const dialogState = (state = initDialogState, action)=>{
    switch (action.type){
        case TOGGLE_DIALOG:
            return Object.assign({}, state, {
                [action.key]: Object.assign({}, state[action.key], {
                    open: !state[action.key].open
                })
            });

        case OPEN_DIALOG:
            return Object.assign({}, state, {
                [action.key]: Object.assign({}, state[action.key], {
                    open: true
                })
            });

        case CLOSE_DIALOG:
            return Object.assign({}, state, {
                [action.key]: Object.assign({}, state[action.key], {
                    open: false
                })
            });

        default:
            return state;
    }
}

const version = (state = "", action) => {
    switch (action.type){
      case SET_VERSION:
          return action.version;
          break;
      default:
          return state;
    }
}

const reducers = combineReducers({
    /**************************** 聊天框区域 Seed *******************/
    chat,           //聊天公共
    chatExpression, //表情控件
    chatRoomNotice, //房间公告
    chatAt,         //艾特别人..

    /**************************** 动画特效部分 Seed *******************/
    flyScreenOfText, //文字飞屏
    flyScreenOfRiding,//坐骑飞屏
    flyScreenOfGift, //(swf格式)礼物飞屏
    animationState,

    /**************************** 礼物部分 Young *******************/

    gifts, //礼物数据
    giftBoardState, //礼物UI状态列表切换及翻页
    giftCurrentState, //当前礼物状态
    /**************************** 礼物部分**************************/

    /**************************** 公共集 Merci *********************/
    users, //房间用户数据
    originalData, //原始数据
    siderLiveHall,//左侧直播大厅数据
    userInfo,//头部用户信息数据
    siderActivityList,//活动列表数据
    limitRoomData,
    spreadUserPanel, //用户二级展开菜单
    dialogState, //弹窗
    version
    /**************************** 公共集 Young *********************/
});

/**
 * reducer组件
 **/
export default reducers;
