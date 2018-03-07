<template>
    <div class="p-search" ref="wrap">
        <div class="search">
            <a class="back" href="javascript:void(0);" @click="goBack()"></a>
            <div class="search-input">
                <input class="keyword" @blur="filterAnchor()" type="text" v-model="anchor" placeholder="请输入用户昵称或主播号">
                <i class="btn-reset" v-if="anchor != ''" @click="clearKeyWord()"></i>
            </div>
            <button class="btn search-button" ref="searchButton" disabled="disabled" @click="searchAnchorList()">搜索</button>
        </div>
        <div class="history-block" v-if="isListShow != true"
             :style="'border-bottom:' + searchHistroy != '' ? '.03125rem solid #e5e5e5;' : ''">
            <div class="search-category" v-if="searchHistroy != ''">最近搜过</div>
            <!--<ul class="history-list">-->
                <!--<li class="history-line-wrapper" v-for="(item, index) in searchHistroy" @click="historyList(index)">-->
                    <!--<i class="history-icon"></i>-->
                    <!--<div class="history-line">-->
                        <!--<a class="history-item" href="javascript:;" :keyword="item">{{item}}</a>-->
                    <!--</div>-->
                <!--</li>-->
            <!--</ul>-->
            <div class="history-list" v-if="searchHistroy != ''">
                <span class="flag-item" v-for="(item, index) in searchHistroy" @click="historyList(index)">
                    {{item}}
                </span>
            </div>
            <div class="btn-line" v-if="searchHistroy != ''" @click="removeSearchHistroy()">
                <a href="javascript:;" class="btn clean-btn">清除搜索历史</a>
            </div>
        </div>
        <div v-if="emptyData != true" class="list-wrappers">
            <div class="p-followfans-list">
                <ul>
                    <li class="item" v-for="(item, index) in searchAnchor">
                        <router-link
                                :to="{path:'/living/' + item.rid, query:{username: item.username, avatar: imgUrl + '/' + item.headimg}}">
                            <div class="user-wraper">
                                <div class="user">
                                    <div class="avatar">
                                        <span class="ui-img ui-circle">
                                            <img :src="item.headimg != '' ? imgUrl + '/' + item.headimg : './static/images/default.jpg'"
                                                 style="width:1.1875rem;height:1.1875rem;" :title="item.username">
                                        </span>
                                    </div>
                                    <div class="info">
                                        <dl>
                                            <dt>
                                                <span class="nickname">{{item.username}}</span>
                                                <div class="ui-grade">
                                                    <i :class="'AnchorLevel' + item.lv_exp"></i>
                                                </div>
                                            </dt>
                                        </dl>
                                    </div>
                                </div>
                                <div class="enter-status">
                                    <span class="entered"></span>
                                </div>
                            </div>
                        </router-link>
                    </li>
                </ul>
            </div>
        </div>
        <div v-else class="list-wrapper">
            <div class="empty-follow">
                <span class="no-live">暂时没有找到相关主播</span>
                <span class="to-see">去看看当前热门直播</span>
            </div>
        </div>
        <dialog-alert :content="tipContent" v-if="active"></dialog-alert>
    </div>
</template>

<script>
    import {dialogAlert} from '../components/dialog/'
    import api from '../api/api'

    export default {
        name: "search",
        data() {
            return {
                docWidth: document.documentElement.clientWidth,
                docHeight: document.documentElement.clientHeight,
                tipContent: '',
                searchHistroy: [],
                emptyData: false,
                active: false,
                isShow: false,
                isListShow: false,
                imgUrl: '',
                searchAnchor: [],
                anchor: ''
            }
        },
        methods: {
            filterAnchor() {
                let _anchor = this.anchor.replace(/(\s|\u00A0)+/g, '');
                if(_anchor == '') {
                    this.anchor = '';
                    this.$refs.searchButton.setAttribute('disabled', 'disabled');
                }

            },
            getKeyup() {
                let keyWord = document.getElementsByClassName('keyword')[0];
                let that = this;
                keyWord.addEventListener('input', function (e) {
                    that.$refs.searchButton.removeAttribute('disabled');
                    return e.target.value;
                });
            },
            clearKeyWord() {
                this.$refs.searchButton.setAttribute('disabled', 'disabled');
                this.emptyData = false;
                this.isListShow = false;
                this.setSearchHistroy();
                this.searchAnchor.length = 0;
                this.anchor = '';
            },
            setSearchHistroy() {
                let searchHistroy = localStorage.getItem("search-histroy");
                if (searchHistroy != null) {
                    this.searchHistroy = searchHistroy.split(',');
                }
            },
            removeSearchHistroy() {
                localStorage.removeItem("search-histroy");
                this.isListShow = true;
            },
            historyList(index) {
                this.anchor = this.searchHistroy[index];
                this.$refs.searchButton.removeAttribute('disabled');
                this.searchAnchorList();
            },
            wrap() {
                const clientHeight = this.docHeight;

                this.$refs.wrap.style.cssText = 'height: ' + clientHeight + 'px;overflow:hidden;';

            },
            searchAnchorList() {
                this.emptyData = false;
                this.isListShow = true;
                let data = {
                    _t: new Date().getTime()
                }

                //搜索记录
                let searchHistroy = localStorage.getItem("search-histroy");

                localStorage.setItem('search-histroy', searchHistroy != null ? this.anchor + ',' + searchHistroy : this.anchor);
                // //去重复搜索记录
                if (searchHistroy != null) {
                    let filterHistroy = (this.anchor + ',' + searchHistroy).split(',').filter((element, index, self) => {
                        return self.indexOf(element) === index;
                    });
                    localStorage.setItem('search-histroy', filterHistroy.toString());
                } else {
                    localStorage.setItem('search-histroy', this.anchor.replace(/(\s|\u00A0)+/g, ''));
                }

                api.getSearchAnchor(this.anchor, data).then((res) => {
                    let imgUrl = document.getElementsByClassName('footer-warp')[0].getAttribute('img-url');

                    if (res.data.data == '') {
                        this.emptyData = true;
                        return
                    }

                    this.searchAnchor = res.data.data;
                    this.imgUrl = imgUrl;
                }).catch((error) => {
                    this.emptyData = true;
                })
            },
            goBack() {
                this.$router.go(-1);
            }
        },
        components: {
            dialogAlert
        },
        mounted() {
            this.setSearchHistroy();
            this.getKeyup();
            this.wrap();
        }
    }
</script>

<style lang="scss">
    .p-search {
        .search {
            display: -webkit-box;
            background-color: #fff;
            border-bottom: .03125rem solid #e5e5e5;
            -webkit-box-align: center;
            position: absolute;
            width: 10rem;
            z-index: 100;
            .back {
                display: block;
                width: 1rem;
                height: .825rem;
                background: url(../../static/images/back.png) center center no-repeat;
                background-size: .425rem .425rem;
            }
            .search-input {
                padding: .3125rem 0.1rem;
                height: .825rem;
                line-height: .625rem;
                border-radius: .15625rem;
                color: #444;
                -webkit-box-flex: 1;
                position: relative;
                input {
                    outline: 0;
                    border: 0;
                    width: 98%;
                    background-color: #e6e6e6;
                    height: .825rem;
                    border-radius: 0.1rem;
                    padding-left: 0.1rem;
                }
                .btn-reset {
                    position: absolute;
                    right: 0.2rem;
                    top: 50%;
                    margin-top: -0.2rem;
                    width: 0.4rem;
                    height: 0.4rem;
                    line-height: 0.4rem;
                    background: url(../../static/images/close.png) no-repeat;
                }
            }
            .btn{
                width: 1.35rem;
                height: .825rem;
                margin: 0 .4rem 0 0.04rem;
                border-radius: 10rem;
                vertical-align: middle;
                /*background: #fa2461;*/
                background-image: -webkit-linear-gradient(left,#ff9901,#fa2461);
                background-image: -moz-linear-gradient(left,#ff9901,#fa2461);
                background-image: linear-gradient(90deg,#ff9901,#fa2461);
                transition: background .1s ease-out;
                color: #fff;
            }
            .search-button:disabled {
                background: #dcdcdc;
                border-radius: 10rem;
                color: #999;
            }
        }
        .history-block {
            //border-bottom: .03125rem solid #e5e5e5;
            -webkit-box-align: center;
            position: absolute;
            top: 1.56rem;
            width: 10rem;
            z-index: 100;
            .search-category {
                padding: 0 0 0 1rem;
                height: 1rem;
                line-height: 1rem;
                font-size: 0.325rem;
                color: #999;
                -webkit-transform: scale(.91667);
                -moz-transform: scale(.91667);
                -ms-transform: scale(.91667);
                -o-transform: scale(.91667);
                transform: scale(.91667);
                -webkit-transform-origin: 0 50%;
                -moz-transform-origin: 0 50%;
                -ms-transform-origin: 0 50%;
                -o-transform-origin: 0 50%;
                transform-origin: 0 50%;
            }
            .history-list {
                padding: .45rem 0 0 1rem;
                list-style: none;
                color: #333;
                //display: flex;
                background-color: #fff;
                .flag-item{
                    display: inline-block;
                    padding: .2rem .3rem;
                    margin: 0 .15rem .1rem 0;
                    max-width: 100%;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                    font-size: .35rem;
                    color: #fff;
                    -moz-border-radius: 10rem;
                    border-radius: 10rem;
                    background-color: #f42561;
                }
            }
            .history-line-wrapper {
                position: relative;
            }
            .history-icon {
                position: absolute;
                top: 50%;
                margin-top: -0.2rem;
                width: 0.425rem;
                height: 0.425rem;
                background: url(../../static/images/histroy.png) left no-repeat;
                background-size: cover
            }
            .history-line {
                border: none;
                margin-left: 0.625rem;
            }
            .history-item {
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                display: block;
                height: 1.25rem;
                line-height: 1.25rem;
                font-size: 0.325rem;
                color: #333;
            }
            .btn-line {
                display: block;
                width: 100%;
                height: 1.25rem;
                line-height: 1.25rem;
                font-size: 0.325rem;
                color: #666;
                text-align: center;
                background-color: #fff;
            }
        }
        .list-wrappers {
            padding-top: 1.56rem;
            .p-followfans-list {
                .item {
                    list-style-type: none;
                    background-color: #fff;
                    margin-bottom: .03125rem;
                    padding: .3125rem 0;
                    .user-wraper {
                        display: -webkit-box;
                        -webkit-box-flex: 1;
                        -webkit-box-align: center;
                        width: 9.375rem;
                        margin: 0 auto;
                    }
                    .user {
                        -webkit-box-flex: 1;
                        display: -webkit-box;
                        -webkit-box-align: center;
                        .ui-circle {
                            img {
                                border-radius: 50%;
                            }
                        }
                        .info {
                            margin-left: .3125rem;
                            dl {
                                display: inline-block;
                                max-width: 6.25rem;
                            }
                            .nickname {
                                display: inline-block;
                                max-width: 4.0625rem;
                                overflow: hidden;
                                text-overflow: ellipsis;
                                white-space: nowrap;
                                vertical-align: middle;
                            }
                            .ui-grade {
                                position: relative;
                                display: inline-block;
                                vertical-align: middle;
                                box-sizing: border-box;
                                padding-left: .09375rem;
                                margin: 0 .0625rem;
                            }
                        }
                    }
                    .enter-status {
                        display: inline-block;
                        width: 1.1875rem;
                        height: 1.1875rem;
                        line-height: 1.1875rem;
                        vertical-align: middle;
                        text-align: center;
                        .entered {
                            width: .625rem;
                            height: .625rem;
                            display: inline-block;
                            background: url(../../static/images/enter.png) no-repeat 0 0;
                            background-size: .625rem .625rem;
                            vertical-align: middle;
                        }
                    }
                }
            }
        }
    }
</style>