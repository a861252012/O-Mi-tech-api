/**
 * @description: 公共Action类
 * @author: Merci
 * @date: 2017/2/24
 * 
 */

import HttpRequest from "../httpRequest.js";
import * as FMW from "../flashMiddleWare.js";

export const UPDATE_USER_POINT = "UPDATE_USER_POINT";
export const ATTACH_USER_INFO = "ATTACH_USER_INFO";


export const GENERATE_GIFT_DAY_USER_DATA = "GENERATE_GIFT_DAY_USER_DATA";
export const GENERATE_GIFT_WEEK_USER_DATA = "GENERATE_GIFT_WEEK_USER_DATA";
export const UPDATE_DAY_RANK_POINT = "UPDATE_DAY_RANK_POINT";

export const UPDATE_USER_LIST = "UPDATE_USER_LIST";
export const UPDATE_LIVEHALL_LIST="UPDATE_LIVEHALL_LIST";

export const REMOVE_USER_LIST = "REMOVE_USER_LIST";

export const CHECK_USER_INFO = "CHECK_USER_INFO";

export const SET_USER_PANEL = "SET_USER_PANEL";
export const SET_CHECK_MSG = "SET_CHECK_MSG";
export const USER_PANEL_STATUS = "USER_PANEL_STATUS";
export const GET_FOCUS_INFO = "GET_FOCUS_INFO";

export const TOGGLE_DIALOG = "TOGGLE_DIALOG";
export const CLOSE_DIALOG = "CLOSE_DIALOG";
export const OPEN_DIALOG = "OPEN_DIALOG";

export const SET_VERSION = "SET_VERSION";

export const removeUserList = (userItem) => {
	return {
		type: REMOVE_USER_LIST,
		userItem
	}
}


/* 日排行榜用户 */
export const generateDayUserData = (userDataDay) => {
	return {
		type: GENERATE_GIFT_DAY_USER_DATA,
		userDataDay
	}
}
/* 更新日排行榜用户数据 */
export const updateDayRankPoint = (dayItem) => {
    return {
        type: UPDATE_DAY_RANK_POINT,
        dayItem
    }
}
/* 周排行榜用户 */
export const generateWeekUserData = (userDataWeek) => {
	return {
		type: GENERATE_GIFT_WEEK_USER_DATA,
		userDataWeek
	}
}


/*********更新日排行钻石总数**********/
export const UPDATE_RANK_DAY_TOTAL="UPDATE_RANK_DAY_TOTAL";
export const updateRankDayTotal = (rankTotal)=>{
	return{
		type: UPDATE_RANK_DAY_TOTAL,
		rankTotal
	}
}

//获取周排行数据
export const fetchRankWeekListData=()=>{
    return (dispatch)=>{
        HttpRequest.getRankWeekData(function(rankWeekData){

			let weekUserData = {};
			let weekRank = [];

			rankWeekData.map((item) => {
				item.uid = parseInt(item.uid, 10); //转换为整形，避免数据读取出错
				weekUserData[item.uid] = item;
				weekRank.push(item.uid);
			})

			//新生成的用户数据
			dispatch(generateWeekUserData(weekUserData));
        })
    }
}

/*直播大厅数据 */
export const updateLiveHallList = (LiveHallList)=> {
	return {
		type: UPDATE_LIVEHALL_LIST,
		LiveHallList
	}
}

export const fetchLiveHallListData=()=>{
    return (dispatch) => {
		FMW.callFlash({ cmd: 50001 })
    }
}

/*****************限制房间处理**********************/
export const UPDATE_LIMIT_ROOM="UPDATE_LIMIT_ROOM";
export const updateLimitRoomData=(limitRoomData)=>{
	return{
		type:UPDATE_LIMIT_ROOM,
        limitRoomData
	}
}
export const fetchLimitRoomData=(data)=>{
	return ()=>{
		FMW.callFlash({ cmd:10011, roomid:data.roomid })
	}
}


export const updateUserList = (userData)=>{
	return {
		type: UPDATE_USER_LIST,
		userData
	}
}

export const fetchOnlineListData=()=>{
    return ()=>{
        FMW.callFlash({ cmd: 11001 })
    }
}

export const fetchOnlineManagerListData=()=>{
    return ()=>{
        FMW.callFlash({ cmd: 11008 })
    }
}

/******************** 用户信息 ********************/ 
export const UPDATE_USER_INFO="UPDATE_USER_INFO";

export const updateUserInfo = (userInfoData)=> {
	return {
		type: UPDATE_USER_INFO,
		userInfoData
	}
}

/********************活动列表数据***********************/
export const UPDATE_ACTIVITY_LIST="UPDATE_ACTIVITY_LIST";
export const updateActivityList=(activityListData)=>{
    let activityList = activityListData.items;
    return{
        type: UPDATE_ACTIVITY_LIST,
        activityList
    }
}

export const fetchActivityListData=()=>{
    return ()=>{
        FMW.callFlash({ cmd: 15004 })
    }
}


//附加数据至UserInfo  （用户聊天长度限制 / 时间间隔 ）
export const attachUserInfo = (userInfoData)=>{
	return {
		type: ATTACH_USER_INFO,
		userInfoData
	}
}

//展开用户二级菜单面板
export const setUserPanel = (data)=>{
	return {
		type:SET_USER_PANEL,
		data
	}
}

//显示用户资料面板
export const setCheckMsg = (data)=>{
	return {
		type:SET_CHECK_MSG,
        data
	}
}

//关闭用户二级菜单面板.. 或者资料面板.
export const setUserPanelStatus = (isUserPanelOpen) => {
	return {
		type: USER_PANEL_STATUS,
        isUserPanelOpen
	}
}

//点击查看资料时获取用户信息
export const checkUserInfo=( checkUserData )=>{
    return {
        type:CHECK_USER_INFO,
        checkUserData
    }
}
export const fetchCheckUserInfo=(uid)=>{
    return (dispatch)=>{
        HttpRequest.getCheckUserInfo(uid,function(checkUserData){
            //新生成的用户数据
            dispatch(checkUserInfo(checkUserData));
        })
    }
}

//点击用户资料弹窗后点击关注获得关注信息
export const getFocusMsg=( focusInfo )=>{
	return {
		type : GET_FOCUS_INFO,
        focusInfo
	}
}
export const fetchFocusInfo=(pid,ret)=>{
	return ( dispatch )=>{
		HttpRequest.getFocusInfo(pid,ret,function (focusInfo) {
			dispatch( getFocusMsg(focusInfo) )
        })
	}
}
//修改header用户金额信息
export const updateUserPoints = (point)=> {
	return {
		type: UPDATE_USER_POINT,
		point
	}
}

//dialog状态toggle
export const toggleDialogOpen = (key)=> {
	return {
		type: TOGGLE_DIALOG,
		key
	}
}

export const openDialog = (key)=>{
	return {
		type: OPEN_DIALOG,
		key
	}
}

export const closeDialog = (key)=>{
    return {
        type: CLOSE_DIALOG,
        key
    }
}

//版本号控制
export const setVersion = (version) => {
	return {
		type: SET_VERSION,
		version
	}
}

//积分兑换
export const getRedeemDate=(score)=>{
    return (dispatch) => {
        FMW.callFlash({ cmd: 90003 , score: score})
    }
}