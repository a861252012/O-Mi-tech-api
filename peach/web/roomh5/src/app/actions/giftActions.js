/**
 * @description: 礼物Action类
 * @author: Young
 * @date: 2017/2/20
 *
 */

//引入ajax请求数据
import HttpRequest from "../httpRequest.js"
//引入FlashMiddleWare
import * as FMW from "../flashMiddleWare.js";

export const SEND_GIFT = "SEND_GIFT";
export const RECEIVE_GIFT = "RECEIVE_GIFT";

export const UPDATE_GIFT_LIST = "UPDATE_GIFT_LIST";
export const UPDATE_GIFT_DATA = "UPDATE_GIFT_DATA";
export const UPDATE_GIFT_BOARD_LIST = "UPDATE_GIFT_BOARD_LIST";
export const UPDATE_GIFT_BOARD_TABINDEX = "UPDATE_GIFT_BOARD_TABINDEX";
export const UPDATE_GIFT_BOARD_SLIDERINDEX = "UPDATE_GIFT_BOARD_SLIDERINDEX";
export const UPDATE_GIFT_CURRENT_SEND = "UPDATE_GIFT_CURRENT_SEND";
export const UPDATE_GIFT_SELECT_NUMBER = "UPDATE_GIFT_SELECT_NUMBER";
export const UPDATE_GIFT_INPUT_NUMBER = "UPDATE_GIFT_INPUT_NUMBER";
export const GIFT_SELECT_PANEL_STATUS = "GIFT_SELECT_PANEL_STATUS";
export const UPDATE_GIFT_FORM_STATUS = "UPDATE_GIFT_FORM_STATUS";
export const UPDATE_INPUT_GIFT_SEND_NUMBER = "UPDATE_INPUT_GIFT_SEND_NUMBER";
export const UPDATE_GIFT_CURRENT_IMG = "UPDATE_GIFT_CURRENT_IMG";
export const UPDATE_GIFT_CURRENT_LUXURY = "UPDATE_GIFT_CURRENT_LUXURY";
export const UPDATE_GIFT_CURRENT_CAROUSEL = "UPDATE_GIFT_CURRENT_CAROUSEL";
export const UPDATE_GIFT_CURRENT_GIFTSET = "UPDATE_GIFT_CURRENT_GIFTSET";
export const UPDATE_GIFT_CURRENT_GENERAL = "UPDATE_GIFT_CURRENT_GENERAL";
export const UPDATE_DOUBLE_CLICK = "UPDATE_DOUBLE_CLICK";
export const UPDATE_ANIMATION_ROUTES = "UPDATE_ANIMATION_ROUTES";

/*更新礼物列表 */
export const UPDATE_CHAT_GIFT_LIST = "UPDATE_CHAT_GIFT_LIST";

/* 更新豪华礼物数据 */
export const updateCurrentLuxury = (luxuryItem) => {
  return {
    type: UPDATE_GIFT_CURRENT_LUXURY,
    luxuryItem
  }
}

/*
 ( 图片格式 ) 动画飞屏..
 */
export const imgGiftAddQueue =( data )=>{
  return {
    type: UPDATE_GIFT_CURRENT_IMG,
    data
  }
}

export const updateCurrentCarousel = (carouselItem) => {
  return {
    type: UPDATE_GIFT_CURRENT_CAROUSEL,
    carouselItem
  }
}

export const updateCurrentGiftSet = (giftSetItem) => {
  return {
    type: UPDATE_GIFT_CURRENT_GIFTSET,
    giftSetItem
  }
}

export const updateCurrentGeneral = (generalItem) => {
  return {
    type: UPDATE_GIFT_CURRENT_GENERAL,
    generalItem
  }
}

export const updateGiftsList = (giftSendItem) => {
  return {
    type: UPDATE_GIFT_LIST,
    giftSendItem
  }
}

/**
 * ajax 获取礼物列表 (Redux => giftList)
 * @returns {function(*)}
 */
export const fetchUpdateGiftList = () => {
  return (dispatch) => {
    HttpRequest.getGiftListData(function (giftData) {
      //40001数据由于后端接口定义不统一,需要前端重组数据
      giftData.map((item) => {
        item.vip = parseInt(item.vipLv, 10);
        item.sendHidden = parseInt(item.hidden, 10);
      });
      //将数组reverse翻转按正常方式排序
      dispatch(updateGiftsList(giftData.reverse()))
    })
  }
}

/**
 * 发送消息
 */
export const sendGift = (giftInfo) => {
  return () => {
    FMW.callFlash(Object.assign({cmd: 40001}, giftInfo));
  }
}

/**
 * 接收消息
 */
export const receiveGift = (giftId) => {
  return {
    type: RECEIVE_GIFT,
    giftId
  }
}


/**
 * 更新礼物列表
 */
export const updateGiftBoardList = (giftBoard) => {
  return {
    type: UPDATE_GIFT_BOARD_LIST,
    giftBoard
  }
}

/**
 * 更新礼物范式化数据
 */
export const updateGiftData = (giftData) => {
  return {
    type: UPDATE_GIFT_DATA,
    giftData
  }
}

/**
 * 获取board礼物列表
 */
export const fetchGiftBoardList = () => {
  return (dispatch) => {
    HttpRequest.getGiftBoardList((data) => {
      let giftBoard = {};
      let giftData = {};
      data.map((category, index) => {
        giftBoard[index + 1] = category.items;
        category.items.map((item) => {
          giftData[item.gid] = item;
        });
      });

      //礼物展示列表数据
      dispatch(updateGiftBoardList(giftBoard));
      //范式化
      dispatch(updateGiftData(giftData));
    })

  }
}
//获取tabIndex 进行分tab切换
export const updateGiftBoardTabIndex = (tabIndex) => {
  return {
    type: UPDATE_GIFT_BOARD_TABINDEX,
    tabIndex
  }
}

//获取sliderIndex 进行翻页切换
export const updateGiftBoardSliderIndex = (sliderIndex) => {
  return {
    type: UPDATE_GIFT_BOARD_SLIDERINDEX,
    sliderIndex
  }
}

//获取到选定礼物数据
export const updateGiftCurrentSend = (giftInfo) => {
  return {
    type: UPDATE_GIFT_CURRENT_SEND,
    giftInfo
  }
}

//获取礼物数量
//input框获取礼物数量
export const updateGiftInputNumber = (inputNumber) => {
  return {
    type: UPDATE_GIFT_INPUT_NUMBER,
    inputNumber
  }
}
//获取到礼物数量控制面板显示与消失的状态
export const updateGiftSelectPanelStatus = (giftSelectPanelStatus) => {
  return {
    type: GIFT_SELECT_PANEL_STATUS,
    giftSelectPanelStatus
  }
}

//获取动画路径
export const loadAnimationRoutes = () => {
  return (dispatch) => {
    HttpRequest.getAnimationRoutes((animationList) => {
      dispatch(getAnimationRoutes(animationList));
    });
  }
}

//讲路径放入redux
export const getAnimationRoutes = (animationList) => {
  return {
    type: UPDATE_ANIMATION_ROUTES,
    animationList
  }
}