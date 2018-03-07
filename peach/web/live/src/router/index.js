import Vue from 'vue'
import Router from 'vue-router'
import Home from '../views/home.vue'
import Recommend from '../views/home/recommend.vue'
import Hot from '../views/home/hot.vue'
import Look from '../views/home/look.vue'
import Live from '../views/living.vue'
import Download from '../views/download.vue'
import Search from '../views/search.vue'

Vue.use(Router)

export default new Router({
    //mode: 'history',
    routes: [
        {
            path: '/',
            name: 'home',
            component: Recommend,
        },
        {
            path: '/home',
            name: 'home',
            component: Home,
            children:[
                {
                    path: 'Recommend',
                    name: 'recommend',
                    component: Recommend
                },
                {
                    path: 'hot',
                    name: 'hot',
                    component: Hot
                },
                {
                    path: 'look',
                    name: 'look',
                    component: Look
                }
            ]
        },
        {
            path: '/living/:rid',
            name: 'living',
            component: Live
        },
        {
            path: '/download',
            name: 'download',
            component: Download
        },
        {
            path: '/search',
            name: 'search',
            component: Search
        }
    ]
})
