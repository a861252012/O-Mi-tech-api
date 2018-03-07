<template>
    <div class="living-area" ref="textContent" :show="show" :hls="videoSrc">
        <div class="middle-section" :style="{'width': winWidth,'height': winHeight}" :start="isStart">
            <div class="video-section" :style="{'width': winWidth,'height': winHeight}">
                <video id="video_tag_e" class="video-js vjs-default-skin" controls webkit-playsinline playsinline preload="none" data-setup="{}" width="100%" height="100%" :style="fullScreenStatus" :src="videoSrc">
                    <!--<source id="videoSrc" :src="videoSrc" type="application/x-mpegURL">-->
                    <!--<source id="videoSrc" :src="videoSrc" type="video/mp4">-->
                </video>
            </div>
        </div>
        <div class="poster">
            <img src="../../../static/images/video-light.svg" class="btn" v-if="liveStatus =='live_ended'" @click="videoPlay()">
            <div class="live-play-btn" v-if="liveStatus =='live_ended'" @click="videoPlay()"></div>
            <img ref="avatar" v-if="liveStatus =='live_ended'" class="posterpic" :src="videoCover + '?w=' + docWidth + '&h=' + docHeight">
        </div>
        <div class="textstage" id="textstage" :time="timer" v-if="isStart == 'on'">
            <div class="system">
                系统消息: 同学们，100%抓紧%100最后的机会啦!
            </div>
            <living-text :msg="wechatMsg" :classNames="sendClass"></living-text>
        </div>
        <div class="giftbar" v-if="giftIcons != ''">
            <living-gift :giftIcon="giftIcons"></living-gift>
        </div>
        <div class="into-anim">
        </div>
        <div ref="rid" class="path" style="display: none;">{{$route.path}}</div>
        <dialog-alert :mtContent="tipContent" v-if="active"></dialog-alert>
    </div>
</template>

<script>
    import LivingText from './livingText'
    import LivingGift from './livingGift'
    import {dialogAlert} from '../../components/dialog/'
    import api from '../../api/api'
    import * as CryptoJS from 'crypto-js'

    export default {
        name: "living-area",
        props: {
            show: String
        },
        data() {
            return {
                avatarImg: 'http://p.cdn.upliveapps.com/uplive/p/u/2017/8/12/d1c8a84c-b39b-4439-99e4-9ed61b1984c5.jpg',
                fullScreenStatus: 'transform:scale(1.5)',
                docWidth: document.documentElement.clientWidth,
                docHeight: document.documentElement.clientHeight,
                winWidth: 0,
                winHeight: 0,
                liveStatus: 'live_ended',
                sendClass: 'system',
                active: false,
                isStart: 'off',
                tipTitle: '提示',
                tipContent: '',
                videoSrc: '',
                videoCover: '',
                chatWs: '',
                channelId: '',
                timer: 10,
                wechatMsg: [],
                giftIcons: []

            }
        },
        components: {
            LivingText,
            LivingGift,
            dialogAlert
        },
        methods: {
            goDownload() {
                this.$router.push({path: '/download'});
            },
            filterString(str) {
                let pattern = /[`~@||【】]/;
                let rs = '';
                for (var i = 0; i < str.length; i++) {
                    rs = rs + str.substr(i, 1).replace(pattern, '');
                }
                return rs;
            },
            randomPosition(minPosition, maxPosition) {
                return parseInt(Math.random() * (maxPosition - minPosition + 1) + minPosition, 10);
            },
            b64EncodeUnicode(str) {
                return btoa(encodeURIComponent(str).replace(/%([0-9A-F]{2})/g, function (match, p1) {
                    return String.fromCharCode('0x' + p1);
                }));
            },
            getCookie(key) {
                let name = key + "=";
                let ca = document.cookie.split(';');

                for (let i = 0; i < ca.length; i++) {
                    let c = ca[i].trim();
                    if (c.indexOf(name) == 0) return c.substring(name.length, c.length);
                }
                return "";
            },
            encrypt(word, key) {

                key = CryptoJS.enc.Utf8.parse(key);
                let iv = CryptoJS.enc.Utf8.parse('0807060504030201');
                return CryptoJS.AES.encrypt(word, key, {
                    iv: iv,
                    mode: CryptoJS.mode.CBC,
                    padding: CryptoJS.pad.Pkcs7
                }).toString();
            },
            enterTip(name) {
                let online = '<span class="defaulte">[欢迎]</span> ' +
                    '<span class="defaulte">' + name + '</span> ' +
                    '<span class="content">进入了直播间</span>';
                this.wechatMsg.push(online);
            },
            systemTip(type, content, date) {
                let syTip = '';
                syTip += (type != 'admin' ? (date != '' ? '<span class="defaulte">[' + date + ']</span> <span class="defaulte">' + type + ': </span>' : '') : '<span class="defaulte">[系统消息]</span><span>: </span>');
                syTip += '<span class="' + (date != '' ? 'content' : 'defaulte') + '">' + (date != '' ? content : this.filterString(content).replace(/[a-zA-Z]\w{5,17}/, type)) + '</span>';
                this.wechatMsg.push(syTip);
            },
            giftIconTip(gid, gnum, name) {
                let giftTop = this.randomPosition(5, 10);
                let giftIcon = ' <div class="static-gift" style="top:' + giftTop + 'rem;bottom: 0;">' +
                    '<div class="item anim">' +
                    '<div class="left">' +
                    '<span class="ui-img ui-circle" style="width:0.8125rem;height:0.8125rem;" data-width="26" data-height="26">' +
                    '<img src="../../../static/images/avatar.jpg" style="opacity: 1;">' +
                    '</span><span class="username">' + name + '</span>' +
                    '<span class="static">赠送</span>' +
                    '<div class="giftpic">' +
                    '<img src="../../../static/images/gift_material/' + gid + '.png">' +
                    '</div>' +
                    '</div>' +
                    '<div class="anim">' +
                    '<span>X</span>' +
                    '<span>' + gnum + '</span>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
                this.giftIcons.push(giftIcon);
            },
            websocketConnect(rid, chatWs, channelId) {
                let ws = new WebSocket(chatWs + '/?request=' + this.b64EncodeUnicode('{"website":2,"rid":' + rid + ',"channelId":' + channelId + '}'));
                ws.addEventListener('open', (evt) => {
                    ws.send('{"cmd":10000}');
                    let _cmd = setInterval(() => {
                        this.timer--;
                        if (this.timer == 0) {
                            ws.send('{"cmd":9999}');
                            if (typeof this.$refs.textContent === 'undefined') {
                                clearInterval(_cmd);
                                ws.close();
                            }
                            this.timer = 10;
                            return false;
                        }
                    }, 1000);

                    console.log('数据发送中...');
                })
                ws.addEventListener('message', (evt) => {
                    let info = JSON.parse(evt.data)
                    switch (info.cmd) {
                        case 500:
                            this.active = true;
                            this.tipContent = info.msg;
                            ws.close();
                            break;
                        case 9999:
                            console.log('数据保持连接中...');
                            break;
                        case 10000:
                            ws.send('{"roomLimit":"' + this.encrypt(this.getCookie('PHPSESSID') + rid + 'juggg123', info.limit) + '","roomid":' + rid + ',"sid":"","isPulish":false,"pass":"","key":"' + this.getCookie('PHPSESSID') + '","limit":"' + info.limit + '","pulishUrl":"","cmd":10001}');
                            break;
                        case 10001:
                            this.enterTip(info.name);
                            break;
                        // case 10002:
                        //     this.enterTip(info.name);
                        //     break;
                        // case 10008:
                        //     this.enterTip(info.name);
                        //     break;
                        case 11002:
                            this.enterTip(info.name);
                            break;
                        case 15555:
                            if (info.errorCode == null) {
                                this.active = true;
                                this.tipContent = info.msg;
                                ws.close();
                            }
                            ;
                            break;
                        case 30001:
                            this.systemTip(info.sendName, info.content, info.date);
                            break;
                        case 40001:
                            this.giftIconTip(info.gid, info.gnum, info.sendName);
                            break;
                    }

                    console.log('数据已接收...');
                })
                ws.addEventListener('close', (evt) => {
                    console.log('数据连接已关闭...');
                })
                ws.addEventListener('error', (evt) => {
                    this.active = true;
                    this.tipContent = 'Websocket连接发生了错误';
                })

                // 路由跳转时结束websocket链接
                this.$router.afterEach(() => {
                    ws.close();
                })
            },
            wrap() {
                const clientHeight = this.docHeight;
                const avatarType = this.$refs.avatar;
                const headimg = document.getElementsByClassName('headimg')[0];

                const image = new Image();
                image.src = this.avatarImg;
                image.onload = () => {
                    avatarType.style.cssText = 'height: ' + clientHeight + 'px; visibility: visible;background-attachment: fixed';//margin-left: ' + (-clientHeight / 2) + 'px;
                }
                this.videoCover = headimg.getAttribute('cover');
            },
            getVideoHls() {
                let rid = this.$refs.rid.innerHTML.replace(/\/[a-z]{2,6}|\//g, '');
                let data = {
                    _t: new Date().getTime()
                }
                api.getRoomHls(rid, data).then((res) => {
                    this.videoSrc = res.data.hls_addr[0].addr;
                    //this.videoSrc = 'http://d2zihajmogu5jn.cloudfront.net/bipbop-advanced/bipbop_16x9_variant.m3u8';
                    this.chatWs = res.data.chat_ws[0];
                    this.channelId = res.data.room.channel_id;

                }).catch((error) => {
                    this.goDownload();
                })
            },
            videoPlay() {
                let video = document.getElementsByTagName('video')[0];
                let rid = this.$refs.rid.innerHTML.replace(/\/[a-z]{2,6}|\//g, '');
                video.play();
                this.isStart = 'on';
                if(this.isStart == 'on') {
                    this.websocketConnect(rid, this.chatWs, this.channelId);
                }
                this.liveStatus = 'live_loading';
                this.winWidth = '10rem';
                this.winHeight = this.docHeight + 'px';
            }
        },
        mounted() {
            this.getVideoHls();
            this.wrap();
        }
    }
</script>

<style lang="scss">
    .textstage {
        height: 3.125rem;
        width: 10rem;
        position: fixed;
        bottom: 1.5625rem;
        left: 50%;
        margin-left: -5rem;
        background: transparent;
        padding: 0 .3125rem;
        -webkit-box-sizing: border-box;
        overflow: auto;
        z-index: 4;
        text-shadow: 0.03125rem 0 0 #000;
        -webkit-overflow-scrolling: touch;
        font-size: 0.3825rem !important;
        .defaulte {
            color: #f42561;
        }
        .system {
            color: #f42561;
        }
        .item {
            margin-top: .0625rem;
        }
        .chat {
            color: #fff;
        }
        .name {
            color: #d1cfd4;
            display: inline-block;
            margin-right: .0625rem;
        }
        .content, .msg {
            color: #ffe14e;
            display: inline-block;
            margin-right: .0625rem;
        }
    }

    .static-gift-list {
        /*width: 10rem;*/
        /*height: 8rem;*/
        /*margin-top: 2.625rem;*/
        /*position: relative;*/
        /*overflow: hidden;*/
    }

    .static-gift {
        position: fixed;
        height: 1.6875rem;
        width: 10rem;
        left: 50%;
        margin-left: -4.875rem;
        bottom: 5.625rem;
        z-index: 99;
        color: #fff;
        -webkit-animation-name: intoAnim;
        -webkit-animation-timing-function: ease-in-out;
        -webkit-animation-duration: 7s;
        -webkit-transform: translate3d(-10rem, 0, 0);
        .item {
            position: absolute;
            left: 0;
            top: .46875rem;
            clear: both;
            font-size: .40625rem;
            color: #fff;
            line-height: .875rem;
            -webkit-transform: translateZ(0);
            opacity: 1;
            -webkit-transition: all .3s ease-out 1.5s;
            div, span {
                float: left;
            }
            .ui-img {
                margin: .03125rem 0 0 .03125rem;
            }
            .ui-circle img {
                border-radius: 50%;
            }
            .left {
                height: .875rem;
                background: -webkit-gradient(linear, left center, right center, from(#f42561), to(rgba(126, 39, 255, 0)));
                border-radius: .40625rem;
            }
            .username {
                margin-left: .15625rem;
                max-width: 2.8125rem;
                text-overflow: ellipsis;
                overflow: hidden;
                white-space: nowrap;
            }
            .static {
                color: #ff0;
                margin-left: .15625rem;
            }
            .giftpic {
                height: 1.40625rem;
                position: relative;
                top: -.3125rem;
                display: -webkit-box;
                -webkit-box-pack: center;
                -webkit-box-align: center;
                text-align: center;
                img {
                    vertical-align: middle;
                    width: 1.0666rem;
                    height: 1.0666rem;
                }
            }
            .anim {
                margin-left: .25rem;
                color: #ffe14e;
                text-shadow: 0.03125rem 0 0 #ff9000, 0 0.03125rem 0 #ff9000, -0.03125rem 0 0 #ff9000, 0 -0.03125rem 0 #ff9000;
                font-size: .5625rem;
                -webkit-animation-name: staticGift;
                -webkit-animation-duration: .3s;
                -webkit-animation-timing-function: ease-in-out;
                .num {
                    margin-left: .09375rem;
                    font-size: .875rem;
                }
            }
        }
    }

    @-webkit-keyframes intoAnim {
        0% {
            -webkit-transform: translate3d(10rem, 0, 0)
        }

        to {
            -webkit-transform: translate3d(-4.375rem, 0, 0)
        }
    }
</style>