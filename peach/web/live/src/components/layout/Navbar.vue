<template>
    <div class="navbar" :key="key" v-if="($route.name == 'living' || $route.name == 'download' || $route.name == 'search' ? isShow : !isShow)">
        <div class="navbar-brand"></div>
        <div class="navbar-nav-tab">
            <ul class="navbar-nav">
                <li v-for="(item, index) in navList"
                    :class="{'selected': activeIndex === index}"
                    @click="tabSelected(index)">
                    <router-link :to="item.path">{{item.title}}
                    </router-link>
                </li>
            </ul>
        </div>
        <div class="navbar-search" @click="goSearch()"></div>
    </div>
</template>
<script>

    export default {
        name: 'navbar',
        data() {
            return {
                activeIndex: 0,
                navList:[
                    {
                        title: '推荐',
                        path: '/home/recommend',
                        name: 'recommend'
                    },
                    {
                        title: '热门',
                        path: '/home/hot',
                        name: 'hot'
                    },
                    {
                        title: '发现',
                        path: '/home/look',
                        name: 'look'
                    }
                ],
                isShow: false
            }
        },
        methods: {
            tabSelected(index) {
                this.activeIndex = index;
            },
            goSearch() {
                this.$router.push({path: '/search'});
            }
        },
        computed: {
            key() {
                const _routename = this.$route.name,
                    _route = this.$route,
                    times = new Date().getTime();
                return _routename !== undefined ? _routename + '_' + times : _route + '_' + times;
            }
        },
        mounted() {
           // this.tabSelected(0);
        }
    }
</script>
<style lang="scss">
    .navbar {
        display: -webkit-box;
        font-size: .40625rem;
        color: #c0bec4;
        background-color: #fff;
        height: 1.6875rem;
        line-height: 1.6875rem;
        border-bottom: .03125rem solid #e5e5e5;
        position: fixed;
        left: 50%;
        top: 0;
        width: 10rem;
        z-index: 4;
        margin-left: -5rem;
        .navbar-brand{
            display: inline-block;
            width: 1.25rem;
            height: 1.6875rem;
            background: url(../../assets/images/diamond.png) no-repeat center center;
            background-size: .625rem .625rem;
        }
        .navbar-nav-tab{
            -webkit-box-flex: 1;
            ul {
                display: -webkit-box;
                padding: 0 .3125rem;
                li {
                    display: block;
                    -webkit-box-flex: 1;
                    flex: 1 1 auto;
                    text-align: center;
                    a{
                        color: #c0bec4;
                        display: block;
                        width: 1.6875rem;
                        height: 1.6575rem;
                        -webkit-box-sizing: border-box;
                        box-sizing: border-box;
                        line-height: 1.6575rem;
                        padding: 0 .12rem;
                        text-decoration: none;
                    }
                    &.selected{
                        a, .router-link-active{
                            color: #fa2461;
                            border-bottom: .06rem solid #fa2461;
                        }
                    }
                }
            }
        }
        .navbar-search{
            width: 1.38rem;
            height: 1.6875rem;
            background: url(../../assets/images/search.png) no-repeat 50% 50%;
            background-size: .43875rem .4375rem;
        }
    }
</style>