/**
 *   Created by zeal on 2017/11/30
 */
import Vue from 'vue'
import App from './App.vue'
import router from './router'
import store from './store'
import {sync} from 'vuex-router-sync'
import NProgress from 'vue-nprogress'



Vue.use(NProgress)
sync(store, router)

const nprogress = new NProgress({ parent: '.nprogress-container' })

const app = new Vue({
    store,
    router,
    nprogress,
    ...App
})

export {app, router, store}