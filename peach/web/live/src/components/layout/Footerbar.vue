<template>
    <div class="footer-warp" :key="key" v-if="($route.name == 'living' || $route.name == 'download'  ? isShow : !isShow)" :img-url="imgUrls">
        <div class="banner">
            <div class="logo sprite"></div>
            <div class="desc">
                <h3>蜜桃直播</h3>
                <p>华语美女才艺展示</p>
            </div>
            <a :href="downloadUrl" class="godown">
                下载
            </a>
            <!--<button class="close" @click="close()"></button>-->
        </div>
    </div>
</template>
<script>
    import api from '../../api/api'

    export default {
        name: 'footerwarp',
        data() {
            return {
                isShow: false,
                imgUrls: '',
                downloadUrl: ''
            }
        },
        methods: {
            getUserInfo() {
                let data = {
                    _t: new Date().getTime()
                }

                api.getUserInfo(data).then((res) =>{
                    this.imgUrls = res.data.img_url;
                    this.downloadUrl = JSON.parse(res.data.qrcode_img)[0].url;
                })
            },
            close() {
                this.isShow = true;
                localStorage.removeItem('download');
            }
        },
        computed: {
            key() {
                const _routename = this.$route.name,
                    _route = this.$route,
                    times = new Date().getTime();
                return _routename !== undefined ? _routename + '_' + times : _route + '_' + times;
            },
            // getUserInfo() {
            //     return this.$store.getters.userInfo;
            // }
        },
        mounted() {
            //localStorage.setItem('download', 1);
            this.getUserInfo();
            //this.$store.dispatch('getUserInfo');
        }
    }
</script>
<style lang="scss">
    .footer-warp {
        display: -webkit-box;
        font-size: .40625rem;
        color: #c0bec4;
        background-color: rgba(6, 6, 11, .8);
        height: 4em;
        line-height: 1.6875rem;
        position: fixed;
        left: 50%;
        bottom: 0;
        width: 10rem;
        z-index: 4;
        margin-left: -5rem;
        .banner {
            position: fixed;
            display: -webkit-box;
            display: -webkit-flex;
            display: -ms-flexbox;
            display: flex;
            z-index: 1000;
            width: 10rem;
            bottom: 0;
            height: 4em;
            padding: .09rem .1rem;
            -webkit-box-sizing: border-box;
            box-sizing: border-box;
        }
        .logo {
            background: url(../../../static/images/logo.png) no-repeat -.625rem center;
            background-size: contain;
            background-position: 0 0;
            overflow: hidden;
            display: block;
            width: 1rem;
            height: 1rem;
            margin: .2rem .12rem 0 .12rem;
        }
        .desc {
            -webkit-box-flex: 1;
            -webkit-flex: auto;
            -ms-flex: auto;
            flex: auto;
            color: #fff;
            overflow: hidden;
            margin-right: 2.5rem;
            h3 {
                height: .8rem;
                line-height: .8rem;
                font-size: .4256rem;
            }
            p {
                margin-top: .03rem;
                height: .2rem;
                line-height: .2rem;
                font-size: .4256rem;
            }
        }
        .godown {
            width: 1.425rem;
            height: .8rem;
            line-height: .8rem;
            text-align: center;
            font-size: .8em;
            background-color: #fa2461;
            border-radius: .8rem;
            margin: .2625rem .1rem;
            color: #fff;
            padding: .06rem .16rem;
        }
        .close{
            width: .6rem;
            height: .6rem;
            background: none;
            background: url(../../../static/images/close.png) center no-repeat;
            margin: .425rem 0;
        }
    }
</style>