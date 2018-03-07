<template>
    <div class="wrap" ref="wrap">
        <div class="endlive">
            <router-link :to="goIndex" class="quitroom"></router-link>
            <div class="content1">直播提示</div>
            <div class="dltxt">推荐下载蜜桃直播，主播开播准时通知，精彩不再错过</div>
            <div class="app-wrap">
            </div>
            <div class="btns">
                <a :href="downloadUrl" class="home">下载蜜桃直播</a>
                <a :href="downloadUrl" class="home opacity">去看看其他主播</a>
            </div>
        </div>

    </div>
</template>

<script>
    import api from '../api/api'
    export default {
        name: "download",
        data() {
            return {
                goIndex: '/home/recommend',
                downloadUrl: ''
            }
        },
        methods: {
            wrap() {
                const clientHeight = document.documentElement.clientHeight;
                this.$refs.wrap.style.cssText = 'height: ' + clientHeight + 'px;overflow:hidden;';

            },
            getAppDownload() {
                let data = {
                    _t: new Date().getTime()
                }

                api.getUserInfo(data).then((res) =>{
                    this.downloadUrl = JSON.parse(res.data.qrcode_img)[0].url;
                })
            }
        },
        mounted() {
            this.wrap();
            this.getAppDownload();
        }
    }
</script>

<style lang="scss">
    .quitroom {
        position: absolute;
        z-index: 99;
        border-radius: 50%;
        width: 1.09375rem;
        height: 1.09375rem;
        top: 0;
        right: 0;
        background: url(../../static/images/home.png) 0 .25rem no-repeat;
        background-size: .78125rem .78125rem;
    }
    .wrap {
        position: relative;
        -webkit-overflow-scrolling: touch;
        width: 10rem;
        overflow: hidden;
        .endlive{
            width: 100%;
            height: 100%;
            background: url(../../static/images/live_bg.jpg) no-repeat;
            background-attachment: fixed;
            background-size: 100% 100%;
            position: relative;
            overflow: scroll;
            position:  absolute;
            top: 0;
            left: 0;
            display: block;
            z-index: 9999;
            .content1 {
                padding-top: .9375rem;
                font-size: .65625rem;
                color: #fff;
                text-align: center;
                padding-bottom: .3125rem;
            }
            .dltxt {
                text-align: center;
                color: #fff;
                padding: 0 .3125rem;
            }
            .app-wrap{
                width: 2.125rem;
                height: 2.125rem;
                margin: 1rem auto;
                padding-top: .9375rem;
                background: url(../../static/images/logo.png) no-repeat -.625rem center;
                background-size: contain;
                background-position: 0 0;
            }
            .btns {
                font-size: .40625rem;
                color: #fff;
                a {
                    display: block;
                    width: 8.4375rem;
                    height: 1.25rem;
                    color: #fff;
                    line-height: 1.25rem;
                    text-align: center;
                    margin: 0 auto .75rem;
                    background: #fa2461;
                    border-radius: .26875rem;
                    &.opacity {
                        background: hsla(0,0%,100%,.3);
                    }
                }
            }
        }
    }
</style>