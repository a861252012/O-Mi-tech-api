/**
 * @description: 聊天Action类
 * @author: Seed
 * @date: 2017/2/20
 */

import HttpRequest from "../httpRequest.js"
import * as FMW from "../flashMiddleWare.js";
import ChatCommon from "../utils/Chat.js";


/*发送一条聊天信息*/
export const sendChatMessage = (json ) => {
    return (dispatch) => {
        FMW.callFlash(json)
    }
}

/* @其他人.*/
export const AT_OTHER_PERSON = "AT_OTHER_PERSON";
export const atOtherPerson = (userName)=>{
    return {
        type:AT_OTHER_PERSON,
        userName
    }
}

/*更新聊天列表*/
export const UPDATE_CHAT_LIST = 'UPDATE_CHAT_LIST';
export const updateChatList = (data) =>{
    return {
        type: UPDATE_CHAT_LIST,
        data
    }
}

/*更新房间公告 */
export const UPDATE_NOTICE = "UPDATE_NOTICE";
export const udpateNotice = (noticeContent)=>{
    return {
        type:UPDATE_NOTICE,
        noticeContent
    }
}

/*更新聊天关键字屏蔽列表*/
export const UPDATE_KW = "UPDATE_KW";
export const updateKw = ( kwContent ) => {
    return {
        type:UPDATE_KW,
        kwContent
    }
}

/*获取聊天关键字屏蔽列表*/
export const getChatKeywords = ( callback )=>{
    return (dispatch) => {
        HttpRequest.getChatKeywords( callback );
    }

}