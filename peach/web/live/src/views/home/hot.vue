<template>
    <div class="home">
        <div class="category-list">
            <div v-if="emptyData != true" class="list">
                <loading class="is-deactive" :class="{'is-active': displayLoading}">
                    <load-label>
                        <span class="mod-indicator-text">加载中...</span>
                    </load-label>
                </loading>
                <ul>
                    <li v-for="(item, index) in categoryList">
                        <router-link :to="{path:'/living/' + item.rid, query:{username: item.username, avatar: imgUrl + '/' + item.headimg}}">
                            <span class="head-img">
                                <img :src="item.headimg != '' ? imgUrl + '/' + item.headimg : './static/images/default.jpg'">
                            </span>
                            <div :class="['status',item.live_status != 0 ? 'live' : 'reset']">
                                <span>{{ item.live_status != 0 ? '直播' : '休息' }}</span>
                            </div>
                            <div class="refbox">
                                <div class="user">
                                    <div class="name">{{ item.username }}</div>
                                    <div class="grade">
                                        <div class="ui-grade">
                                            <i :class="'AnchorLevel' + item.lv_exp"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="loc">
                                    <i>热门</i>
                                </div>
                            </div>
                        </router-link>
                    </li>
                </ul>
            </div>
            <div v-else class="list-wrapper">
                <div class="empty-follow">
                    <span class="no-live">获取主播数据失败</span>
                    <span class="to-see" @click="refreshData()">请点击重新刷新一次</span>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import {Loading, LoadLabel} from '../../components/loading'
    import api from '../../api/api'

    export default {
        name: 'hot',
        data() {
            return {
                displayLoading: false,
                categoryList:[],
                emptyData: false
            }
        },
        components: {
            Loading,
            LoadLabel
        },
        methods: {
            getList() {
                let data = {
                    _t: new Date().getTime()
                }

                this.displayLoading = true;

                api.getCategory(data).then(res => {
                    this.displayLoading = false;

                if(res.data == '') {
                    this.emptyData = true;
                    return
                }
                setTimeout(() => {
                        // let list = res.data.rooms.filter(type => type.live_status != 0);
                        //     this.categoryList = list;
                        this.imgUrl = document.getElementsByClassName('footer-warp')[0].getAttribute('img-url')
                        this.categoryList = res.data.rooms.slice(0, 20);
                    }, 1000);
                }).catch((error) =>{
                    this.emptyData = true;
                })
            },
            refreshData() {
                this.getList();
            }
        },
        mounted() {
            //this.$store.dispatch('getCategoryList');
            //this.$store.dispatch('getUserInfo');
            this.getList()
        },
        computed: {
            // getCategoryList() {
            //     return this.$store.getters.categoryList;
            // },
            // getUserInfo() {
            //     return this.$store.getters.userInfo;
            // }
        }
    }
</script>
