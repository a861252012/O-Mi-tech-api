/**
 *   Created by zeal on 2017/11/30
 */
import {actions} from './actions'
import {getters} from './getters'
import {mutations} from './mutations'


const state = {
    _t: new Date().getTime(),
    login: false,
    userInfo: {},
    categoryList: {}
}

export default {
    state,
    getters,
    actions,
    mutations
}