<template>
    <div class="wrap" ref="wrap" :timer="timer">
        <div class="live">
            <living-area :show="isShow"></living-area>
            <div class="topbar">
                <div class="curhost">
                    <div class="base">
                        <div class="imgwrap">
                            <div class="ui-img ui-circle">
                                <img class="headimg" :src="$route.query.avatar" :cover="$route.query.avatar" :alt="$route.query.username">
                            </div>
                        </div>
                        <div class="peoples">
                            <p class="nickname">{{ $route.query.username }}</p>
                            <p class="count">{{ $route.params.rid }}</p>
                        </div>
                        <span class="follow">关注</span>
                    </div>
                    <router-link :to="goIndex" class="quitroom"></router-link>
                </div>
            </div>
            <div class="footbar">
                <div>
                    <span class="chatsend"></span>
                    <span class="giftsend" @click="hideGiftTip()"></span>
                    <recent-gift></recent-gift>
                    <div class="gift-guide" ref="gift">送个礼物, 让主播开心一下吧</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import api from '../api/api'
    import {RecentGift, livingArea} from '../components/living/'

    export default {
        name: "living",
        data() {
            return {
                docWidth: document.documentElement.clientWidth,
                docHeight: document.documentElement.clientHeight,
                goIndex: '/home/recommend',
                tip: false,
                isShow: 'none',
                timer: 60
            }
        },
        methods: {
            wrap() {
                const clientHeight = this.docHeight;

                this.$refs.wrap.style.cssText = 'height: ' + clientHeight + 'px;overflow:hidden;';

            },
            hideGiftTip() {
                this.$refs.gift.style.display = 'none';
            },
            endLive() {

                let live = setInterval(() => {
                    this.timer--;

                    if (this.timer == 0) {
                        this.isShow = 'block';

                        if(this.isShow == 'block') {
                            this.$router.push({path: '/download'});
                        }

                        clearInterval(live);
                        this.timer = 60;
                        return false;
                    }
                }, 1000);

                let matches = document.querySelector(".footbar");

                matches.addEventListener('click',() => {
                    this.isShow = 'block';

                    if(this.isShow == 'block') {
                        this.$router.push({path: '/download'});
                    }
                    clearInterval(live);
                })
            }
        },
        components: {
            RecentGift,
            livingArea
        },
        mounted() {
            this.wrap();
            //this.endLive();
        }
    }
</script>
<style lang="scss">
    .ui-grade {
        position: relative;
        display: inline-block;
        vertical-align: middle;
        box-sizing: border-box;
        padding-left: .09375rem;
        margin: 0 .0625rem;
    }

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
        .middle-section {
            position: relative;
            overflow: hidden;
            width: 10rem;
            height: 10rem;
            background-repeat: no-repeat;
            background-position: center;
            background-size: cover;
            .opacity {
                filter: alpha(opacity = 0);
                opacity: 0;
            }
            .video_section{
                video {
                    background: none;
                }
            }
        }
        .poster {
            z-index: 3;
            .btn {
                position: absolute;
                width: 2.5rem;
                height: 2.5rem;
                left: 50%;
                top: 50%;
                margin: -1.25rem 0 0 -1.25rem;
                z-index: 2;
            }
            .posterpic {
                max-width: inherit;
                visibility: hidden;
                position: relative;
                //left: 50%;
            }
        }
        .into-anim {
            position: fixed;
            z-index: 100;
            bottom: 5rem;
            left: 0;
            width: 4.375rem;
            height: 3.4375rem;
            color: #fff;
            -webkit-animation-name: intoAnim;
            -webkit-animation-timing-function: ease-in-out;
            -webkit-animation-duration: 7s;
            -webkit-transform: translate3d(-10rem,0,0);

            .carpic {
                width: 4.375rem;
                height: 2.5rem
            }

            .user {
                overflow: hidden;
                height: .9375rem;
                direction: ltr
            }

            .ui-img {
                float: left;
                margin-right: .15625rem;
                width: .84375rem;
                margin-left: .3125rem
            }

            .name {
                float: left;
                width: 3.0625rem;
                line-height: .875rem;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap
            }
        }

        .topbar {
            position: fixed;
            width: 10rem;
            height: .875rem;
            left: 50%;
            top: 0;
            margin-left: -5rem;
            z-index: 3;
            .base {
                position: absolute;
                left: .15625rem;
                top: .25rem;
                z-index: 44;
                width: 3.59375rem;
                height: .875rem;
                padding-right: .15625rem;
                background: rgba(0, 0, 0, .3);
                border-radius: .4375rem;
                overflow: hidden;
                .imgwrap {
                    width: .8125rem;
                    height: .8125rem;
                    float: left;
                    .ui-img {
                        display: inline-block;
                        width: 0.8125rem;
                        height: 0.8125rem;
                        margin: .03125rem 0 0 .03125rem;
                        background: #fff;
                        background-size: cover;
                        float: left;
                        border-radius: 50%;
                        img {
                            width: .8125rem;
                            height: .8125rem;
                            border-radius: 50%;
                        }
                    }
                }
                .peoples {
                    width: 1.5625rem;
                    height: .875rem;
                    color: #fff;
                    font-size: .3125rem;
                    text-align: center;
                    float: left;
                    overflow: hidden;
                    margin-top: .0625rem;
                    font-size: .25rem;
                    .nickname {
                        overflow: hidden;
                        text-overflow: ellipsis;
                        white-space: nowrap;
                        height: .4375rem;
                        margin-left: .09375rem;
                        line-height: .4375rem;
                    }
                    .count {
                        font-size: .28125rem;
                        height: .4375rem;
                        position: relative;
                        top: -.0625rem;
                    }
                }
                .follow {
                    padding: 0 .09375rem;
                    width: 1.03125rem;
                    height: .625rem;
                    line-height: .625rem;
                    color: #fff;
                    background: #fa2461;
                    border-radius: .3125rem;
                    text-align: center;
                    font-size: .3125rem;
                    float: left;
                    margin-top: .125rem;
                }
            }

        }

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

        .footbar {
            position: fixed;
            bottom: 0;
            width: 10rem;
            height: 1.5rem;
            z-index: 199;
            left: 50%;
            margin-left: -5rem;
            .giftsend {
                float: right;
                width: 1.5rem;
                height: 1.5rem;
                background: url(../../static/images/gift.png) no-repeat 50%;
                background-size: 1.0625rem 1.0625rem;
                margin-right: .15625rem;
            }
            .chatsend {
                float: left;
                width: 1.5rem;
                height: 1.5rem;
                background: url(../../static/images/msg.png) no-repeat 50%;
                background-size: 1.0625rem 1.0625rem;
            }
            .recent-gift {
                float: right;
                width: 5.1875rem;
                height: 1.5rem;
                ul {
                    overflow: hidden;
                    padding-left: .4375rem;
                    li {
                        float: left;
                        width: 1.25rem;
                        height: 1.5rem;
                        margin-left: .25rem;
                        display: -webkit-box;
                        -webkit-box-pack: center;
                        -webkit-box-align: center;
                        text-align: center;
                        img {
                            width: .75rem;
                            height: .75rem;
                        }
                    }
                }
            }
            .gift-guide {
                width: 6.25rem;
                height: .875rem;
                line-height: .875rem;
                text-align: center;
                color: #3a3a3a;
                font-size: .375rem;
                background-color: #fff;
                position: absolute;
                right: .15625rem;
                top: -.9375rem;
                border-radius: .46875rem;
                -webkit-animation: giftGuide 2s ease-in infinite;
                &:after {
                    content: " ";
                    border: .1875rem solid;
                    position: absolute;
                    right: .53125rem;
                    bottom: -.34375rem;
                    border-color: #fff transparent transparent;
                }
            }
        }
    }

    @-webkit-keyframes intoAnim {
        0% {
            -webkit-transform: translate3d(10rem,0,0)
        }

        to {
            -webkit-transform: translate3d(-4.375rem,0,0)
        }
    }

    @-webkit-keyframes giftGuide {
        0% {
            -webkit-transform: scale(.95);
            opacity: .9
        }

        50% {
            -webkit-transform: scale(1);
            opacity: 1
        }

        to {
            -webkit-transform: scale(.95);
            opacity: .9
        }
    }

</style>