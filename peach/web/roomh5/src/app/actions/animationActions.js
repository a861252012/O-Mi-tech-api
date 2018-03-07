/**
 * @description: 动画Action类
 * @author: Seed
 * @date: 2017/3/3
 */

/************************队列型动画************************/

/*每个action 的 status 存储状态代码...   
      0 => 飞屏进行

      3 => 飞屏闲置
*/


/*
      文字飞屏
 */

/* 新增队列 */
export const ADD_QUEUE = "ADD_QUEUE";
export const addQueue = (data) =>{
      return {
            type:ADD_QUEUE,
            data:Object.assign({},{
                  data:data.data
            }),
      }
}
/*写入缓存数据 */
export const ADD_TEMPORY_QUEUE = "ADD_TEMPORY_QUEUE";
export const addTemporyQueue = (data) =>{
      return {
            type:ADD_TEMPORY_QUEUE,
            temporyData:data
      }
}

/*更改队列状态 */
export const CHANGE_QUEUE_STATUS = "CHANGE_QUEUE_STATUS";
export const changeQueueStatus = (statusCode) => {
      return {
            type:CHANGE_QUEUE_STATUS,
            data:Object.assign({},{
                  status:statusCode
            })
      }
}

//将缓存队列迁移到正式队列,并且将缓存队列清空
export const TRANSFER_DATA = "TRANSFER_DATA";
export const transferData = (data) =>{
      return {
            type:TRANSFER_DATA,
            data:data
      }
}

/*
      坐骑飞屏
 */

/* 新增队列 */
export const RIDE_ADD_QUEUE = "RIDE_ADD_QUEUE";
export const rideAddQueue = (data) =>{
      return {
            type:RIDE_ADD_QUEUE,
            data
      }
}

/*写入缓存数据 */
export const RIDE_ADD_TEMPORY_QUEUE = "RIDE_ADD_TEMPORY_QUEUE";
export const rideAddTemporyQueue = (data) =>{
      return {
            type:RIDE_ADD_TEMPORY_QUEUE,
            temporyData:data
      }
}

/*更改队列状态 */
export const RIDE_CHANGE_QUEUE_STATUS = "RIDE_CHANGE_QUEUE_STATUS";
export const rideChangeQueueStatus = (statusCode) => {
      return {
            type: RIDE_CHANGE_QUEUE_STATUS,
            data: statusCode
      }
}

//将缓存队列迁移到正式队列,并且将缓存队列清空
export const RIDE_TRANSFER_DATA = "RIDE_TRANSFER_DATA";
export const rideTransferData = (data) =>{
      return {
            type:RIDE_TRANSFER_DATA,
            data:data
      }
}

/*
     （swf格式）礼物飞屏
 */

/* 新增队列 */
export const GIFT_ADD_QUEUE = "GIFT_ADD_QUEUE";
export const giftAddQueue = (data) =>{
      return {
            type:GIFT_ADD_QUEUE,
            data
      }
}

/*写入缓存数据 */
export const GIFT_ADD_TEMPORY_QUEUE = "GIFT_ADD_TEMPORY_QUEUE";
export const giftAddTemporyQueue = (data) =>{
      return {
            type:GIFT_ADD_TEMPORY_QUEUE,
            temporyData:data
      }
}

/*更改队列状态 */
export const GIFT_CHANGE_QUEUE_STATUS = "GIFT_CHANGE_QUEUE_STATUS";
export const giftChangeQueueStatus = (statusCode) => {
      return {
            type:GIFT_CHANGE_QUEUE_STATUS,
            data:Object.assign({},{
                  status:statusCode
            })
      }
}

//将缓存队列迁移到正式队列,并且将缓存队列清空
export const GIFT_TRANSFER_DATA = "GIFT_TRANSFER_DATA";
export const giftTransferData = (data) =>{
      return {
            type:GIFT_TRANSFER_DATA,
            data:data
      }
}

/************************队列型动画************************/

/************************动画设置************************/
export const TOGGLE_ANIMATION_STATE = "TOGGLE_ANIMATION_STATE";
export const toggleAnimationState = () => {
      return {
            type: TOGGLE_ANIMATION_STATE
      }
}

export const CLEAR_ALL_ANIMATION = "CLEAR_ALL_ANIMATION";
export const clearAllAnimation = () => {
      return {
            type: CLEAR_ALL_ANIMATION
      }
}