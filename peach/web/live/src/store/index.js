/**
 *   Created by zeal on 2017/11/30
 */
import Vue from 'vue'
import Vuex from 'vuex'
import storage from './modules/index.js'

Vue.use(Vuex)

const store = new Vuex.Store({
    strict: true,  // process.env.NODE_ENV !== 'production',
    modules:{
        storage
    }
})

export default store