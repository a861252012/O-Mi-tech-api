/**
 * @description: 用户相关Action类
 * @author: Young
 * @date: Created by young on 2017/3/22.
 */

import HttpRequest from "../httpRequest.js";
import * as FMW from "../flashMiddleWare.js";

/**
 * 普通人没有如下权限
 * 管理员（T人，禁言）
 * 主播（设置管理员，取消管理员，T人，禁言）
 */

//export const SET_USER_RIGHTS = "SET_USER_RIGHTS";

//设为管理员
export const callSetManager = (uid)=>{
    return ()=>{
        FMW.callFlash({ cmd: 11006, uid: uid })
    }
}

//取消管理员
export const callCancelManager = (uid)=>{
    return ()=>{
        FMW.callFlash({ cmd: 11007, uid: uid })
    }
}

//T出房间
export const callRemoveFromRoom = (uid)=>{
    return ()=>{
        FMW.callFlash({ cmd: 18005, uid: uid, type: 0 });
    }
}

//禁言房间
export const callBanToPost = (uid) => {
    return ()=>{
        FMW.callFlash({ cmd: 18005, uid: uid, type: 1 })
    }
}

//redux房间权限设置
//export const setUserRights = (userItem) => {
//    return {
//        type: SET_USER_RIGHTS,
//        userItem
//    }
//}

//发送开通贵族通知
export const sendVIPMessage = (data) => {
  return () => {
      let openVIPData = Object.assign({ cmd: 18001 }, data);
    FMW.callFlash(Object.assign(openVIPData));
  }
}