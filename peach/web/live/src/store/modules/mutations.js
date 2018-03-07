/**
 *   Created by zeal on 2017/11/30
 */
import * as types from './types'

export const mutations = {
    [types.USER_INFO] (state, {list: res}) {
        state.userInfo = res
    },
    [types.CATEGORY_LIST_OPTIONS] (state,{list:res}){
        state.categoryList = res
    }
}