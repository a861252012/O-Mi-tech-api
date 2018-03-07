<template>
    <div id="app">
        <nprogress-container></nprogress-container>
        <nav-bar></nav-bar>
        <app-main></app-main>
        <footer-bar></footer-bar>
    </div>
</template>

<script>
    import NprogressContainer from 'vue-nprogress/src/NprogressContainer.vue'
    import {AppMain, NavBar, FooterBar} from './components/layout/'
    export default {
        name: 'app',
        components: {AppMain, NavBar, FooterBar, NprogressContainer}
    }
</script>
<style lang="scss">
    .nprogress-container {
        position: fixed !important;
        width: 10rem;
        height: 50px;
        z-index: 2048;
        pointer-events: none;

        #nprogress {
            $color: #ff3e69;

            .bar {
                background: $color;
            }
            .peg {
                box-shadow: 0 0 10px $color, 0 0 5px $color;
            }

            .spinner-icon {
                display: none;
                border-top-color: $color;
                border-left-color: $color;
            }
        }
    }
    embed, img, object, video {
        max-width: 100%;
        vertical-align: top;
    }
    .is-deactive {
        display: none;
    }
    .is-active {
        display: block;
    }
    .mod-indicator-wrapper{
        top: 80%!important;
    }
    .category-list{
        padding-top: 1.6875rem;
        .list{
            padding-bottom: 1.25rem;
            position: relative;
            ul{
                overflow: hidden;
                padding-bottom: .6125rem;
                li {
                    float: left;
                    width: 4.75rem;
                    height: 4.75rem;
                    overflow: hidden;
                    position: relative;
                    margin-left: .15625rem;
                    margin-top: .15625rem;
                    a{
                        display: block;
                    }
                    .head-img{
                        display: inline-block;
                        width: 100%;
                        height: 100%;
                        img{
                            width: 4.75rem;
                            height: 4.75rem;
                        }
                    }
                    .status{
                        position: absolute;
                        right: 0;
                        top: 0;
                        z-index: 1;
                        padding: 0 .125rem;
                        height: .5rem;
                        line-height: .5rem;
                        overflow: hidden;
                        background: #3eadff;
                        color: #fff;
                        text-align: center;
                        font-size: .28125rem;
                    }
                    .live{
                        background: #fa2461;
                    }
                    .reset{
                        background: #bababa;
                    }
                    .refbox {
                        position: absolute;
                        width: 100%;
                        height: 100%;
                        bottom: 0;
                        left: 0;
                        background: -webkit-linear-gradient(top,transparent,rgba(0,0,0,.5));
                        .user {
                            position: absolute;
                            left: 0;
                            bottom: .78125rem;
                            height: .71875rem;
                            width: 100%;
                            line-height: .71875rem;
                            text-align: center;
                            color: #fff;
                            font-size: .375rem;
                            display: -webkit-box;
                            -webkit-box-pack: center;
                            -webkit-box-align: center;
                            .name {
                                max-width: 2.8125rem;
                                overflow: hidden;
                                white-space: nowrap;
                                text-overflow: ellipsis;
                                vertical-align: middle;
                                margin: 0 .09375rem;
                                text-shadow: 0.03125rem 0 0 #000;
                            }
                        }
                        .loc {
                            position: absolute;
                            width: 100%;
                            height: .625rem;
                            bottom: .15625rem;
                            left: 0;
                            text-align: center;
                            i{
                                display: inline-block;
                                height: .625rem;
                                line-height: .625rem;
                                color: #fff;
                                background: url('../static/images/location.svg') no-repeat 0 .15625rem;
                                background-size: .28125rem .3125rem;
                                padding-left: .3125rem;
                                max-width: 4.375rem;
                                overflow: hidden;
                                white-space: nowrap;
                                text-overflow: ellipsis;
                                font-size: .256rem;
                                vertical-align: middle;
                                box-sizing: border-box;
                            }
                        }
                    }
                }
            }
        }
    }
    .ui-grade {
        position: relative;
        display: inline-block;
        vertical-align: middle;
        box-sizing: border-box;
        width: .6rem;
        height: .38rem;
        margin: 0 .0625rem;
        i {
            position: absolute;
            z-index: 2;
            display: block;
            width: .6rem;
            height: .38rem;
            background-image: url('../static/images/1.png');
            background-repeat: no-repeat;
            background-position: 50% 50%;
            background-size: contain;
            &.AnchorLevel1 {
                background-image: url('../static/images/1.png');
            }

            &.AnchorLevel2 {
                background-image: url('../static/images/2.png');
            }

            &.AnchorLevel3 {
                background-image: url('../static/images/3.png');
            }

            &.AnchorLevel4 {
                background-image: url('../static/images/4.png');
            }

            &.AnchorLevel5 {
                background-image: url('../static/images/5.png');
            }

            &.AnchorLevel6 {
                background-image: url('../static/images/6.png');
            }

            &.AnchorLevel7 {
                background-image: url('../static/images/7.png');
            }

            &.AnchorLevel8 {
                background-image: url('../static/images/8.png');
            }

            &.AnchorLevel9 {
                background-image: url('../static/images/9.png');
            }

            &.AnchorLevel10 {
                background-image: url('../static/images/10.png');
            }

            &.AnchorLevel11 {
                background-image: url('../static/images/11.png');
            }

            &.AnchorLevel12 {
                background-image: url('../static/images/12.png');
            }

            &.AnchorLevel13 {
                background-image: url('../static/images/13.png');
            }

            &.AnchorLevel14 {
                background-image: url('../static/images/14.png');
            }

            &.AnchorLevel15 {
                background-image: url('../static/images/15.png');
            }

            &.AnchorLevel16 {
                background-image: url('../static/images/16.gif');
            }

            &.AnchorLevel17 {
                background-image: url('../static/images/17.gif');
            }

            &.AnchorLevel18 {
                background-image: url('../static/images/18.gif');
            }

            &.AnchorLevel19 {
                background-image: url('../static/images/19.gif');
            }

            &.AnchorLevel20 {
                background-image: url('../static/images/20.gif');
            }

            &.AnchorLevel21 {
                background-image: url('../static/images/21.gif');
            }

            &.AnchorLevel22 {
                background-image: url('../static/images/22.gif');
            }

            &.AnchorLevel23 {
                background-image: url('../static/images/23.gif');
            }

            &.AnchorLevel24 {
                background-image: url('../static/images/24.gif');
            }

            &.AnchorLevel25 {
                background-image: url('../static/images/25.gif');
            }

            &.AnchorLevel26 {
                background-image: url('../static/images/26.gif');
            }

            &.AnchorLevel27 {
                background-image: url('../static/images/27.gif');
            }

            &.AnchorLevel28 {
                background-image: url('../static/images/28.gif');
            }

            &.AnchorLevel29 {
                background-image: url('../static/images/29.gif');
            }

            &.AnchorLevel30 {
                background-image: url('../static/images/30.gif');
            }

            &.AnchorLevel31 {
                background-image: url('../static/images/31.gif');
            }
        }
    }
    .list-wrapper{
        width: 8.125rem;
        padding-top: 2.1875rem;
        background: url(../static/images/empty.png) no-repeat center 0;
        background-size: 3rem 1.9375rem;
        margin: 3.125rem auto;
        text-align: center;
        .empty-follow{
            .no-live {
                display: block;
                font-size: .40625rem;
            }
            .to-see {
                display: inline-block;
                width: 6.875rem;
                background-color: #f42561;
                height: 1.25rem;
                line-height: 1.25rem;
                border-radius: .625rem;
                text-align: center;
                margin: 0 auto;
                margin-top: .3125rem;
                color: #fff;
                font-size: .40625rem;
            }
        }
    }

    @import '~assets/css/resets.scss';
</style>
