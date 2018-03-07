/**
 *   Created by zeal on 2017/11/30
 */
import api from '../../api/config'
import axios from 'axios'


export const actions = {
    getCategoryList({commit, state}) {
        api.get('/videoList', {_t: state._t}).then(res => {
            commit('CATEGORY_LIST_OPTIONS', {list: res})
        })
    },
    getUserInfo({commit, state}) {
        api.get('/indexinfo', {_t: state._t}).then(res => {
            commit('USER_INFO', {list: res})
        })
    }
}