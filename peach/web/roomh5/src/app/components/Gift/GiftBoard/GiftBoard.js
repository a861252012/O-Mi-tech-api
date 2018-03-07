/**
 * @description: 送礼选择窗口
 * @author: Young
 * @date: 2017/2/20
 */

import React, {Component} from "react";
import Tabs from "../../Common/Tabs/Tabs.js";
import Slider from "../../Common/Slider/Slider.js";
import IconGift from "../../Common/IconGift/IconGift.js";

import {bindActionCreators} from 'redux';
import {connect} from 'react-redux';
import * as actions from '../../../actions/giftActions.js';

import styles from './giftBoard.css';

const mapStateToProps = (state) => {
    return {
        giftBoardList: state.gifts.giftBoard,
        giftBoardState: state.giftBoardState,
        giftCurrentState: state.giftCurrentState,
    }
}

const mapDispatchToProps = (dispatch) => {
    return {
        actions: bindActionCreators(actions, dispatch)
    }
}

const MobileStyle = () => {
    if (window.isMobile) {
        return (
            <style type="text/css">
                {
                    '.giftBoard-giftTabs{ margin: 5px 35px; }' +
                    '.giftBoard-giftItem{ margin-bottom: 5px; }' +
                    '@media (max-width: 1540px){' +
                    '.giftBoard-container{ margin-right: 0px }' +
                    '}' +
                    '@media (max-width: 1450px){' +
                    '.giftBoard-giftItem{ margin-right: 5px; }' +
                    '.giftBoard-container{ width: 340px }' +
                    '.giftBoard-giftTab{ margin-right: 5px !important; }' +
                    '}'
                }
            </style>
        )
    } else {
        return (
            <style type="text/css">
                {
                    "@media (min-height: 800px){" +
                    ".giftBoard-container{ float: none; width: auto; }" +
                    ".giftBoard-giftItem{ width: 60px; height: 60px; margin-left: 15px; margin-right: 15px;}" +
                    ".giftBoard-giftIcon{ margin: 5px auto; }" +
                    ".giftBoard-giftTabs{ margin: 10px auto; }" +
                    ".giftBoard-giftBoardSliderRootStyle{ margin: 10px auto 10px auto; }" +
                    "}" +

                    "@media (min-height: 840px){" +
                    ".giftBoard-giftItem{ margin-bottom: 30px; }" +
                    ".giftBoard-giftItemPanel{ opacity: 1 !important; display: block; }" +
                    ".giftBoard-giftBoardSliderRootStyle{ width: 520px; margin: 10px auto 0px auto; }" +
                    "}" +

                    '@media (max-width: 1400px){' +
                    '.giftBoard-container{ width: 340px; margin-right: 0px; }' +
                    '.giftBoard-giftItem{ margin-right: 5px; margin-bottom: 5px }' +
                    '.giftBoard-giftTab{ margin-right: 6px!important }' +
                    '.giftBoard-giftTabs{ margin: 5px 35px; }' +
                    '}' +

                    '@media (max-width: 1400px) and (min-height: 800px){' +
                    '.giftBoard-giftBoardSliderRootStyle{ width: 480px }' +
                    '.giftBoard-container{ width: auto }' +
                    '.giftBoard-giftTabs{ margin: 10px auto }' +
                    '.giftBoard-giftTab{ margin-right: 10px!important; margin-left: 11px!important; }' +
                    '.giftBoard-giftItem{ margin-bottom: 30px;}' +
                    '}'
                }
            </style>
        )
    }
}

class GiftBoard extends Component {

    componentDidMount() {

        //设置首个礼物为默认
        //let firstGiftInfo = {};
        //if(Object.keys(this.props.giftBoardList).length > 0){
        //    firstGiftInfo = this.props.giftBoardList["1"][0]
        //}

        this.props.actions.fetchGiftBoardList();
        //this.props.actions.updateGiftCurrentSend(firstGiftInfo);

    }

    //tab bar 切换方法
    handleSwitchTab(index) {
        this.props.actions.updateGiftBoardTabIndex(index);
    }

    //箭头翻页方法
    onHandleSliderIndex(key, index) {
        this.props.actions.updateGiftBoardSliderIndex({[key]: index});
    }

    //选定礼物功能实现：将选定的礼物数据同步至redux
    handleSelectGift(giftInfo) {
        this.props.actions.updateGiftCurrentSend(giftInfo);
        this.props.actions.updateGiftInputNumber(1);
    }

    //双击赠送礼物
    handleDoubleClickSend() {
        this.props.onHandleSend();
    }

    //生成礼物列表
    composeList(list) {
        let giftList = [];
        list.map((item) => {

            let giftItemActive = item.gid == this.props.giftCurrentState.select.gid ? styles.giftItemActive : '';

            giftList.push(
                <div className={'giftBoard-giftItem' + ' ' + giftItemActive}
                     key={"giftItem" + item.gid}
                     onClick={() => {
                         this.handleSelectGift(item)
                     }}
                     onDoubleClick={() => {
                         this.handleDoubleClickSend()
                     }}
                >
                    <IconGift iconClass={"giftBoard-giftIcon"} iconId={item.gid} title={item.name}></IconGift>
                    <div className="giftBoard-giftItemPanel">{item.price}钻</div>
                    {item.isLuck == 1 ? <div className={styles.giftItemLucky}>LUCK</div> : ''}
                </div>
            )
        });

        return (<div className={styles.giftListContainer} key={"giftGroup" + list[0].gid}>{giftList}</div>);

    }

    //与composeList方法结合使用，翻页功能实现
    getGiftList(giftList) {
        let currentList = [];
        for (let i = 0; i < giftList.length; i += 10) {
            currentList.push(
                giftList.slice(i, i + 10)
            )
        }
        return currentList;
    }

    render() {
        let giftTabTitle = [
            {
                key: "hot",
                title: "热门"
            },
            {
                key: "vip",
                title: "贵族"
            },
            {
                key: "rec",
                title: "推荐"
            },
            {
                key: "high",
                title: "高级"
            },
            {
                key: "luxury",
                title: "奢华"
            }
        ]

        let {giftBoardList, giftBoardState} = this.props;
        this.handleDoubleClickSend = this.handleDoubleClickSend.bind(this)


        return (
            <div className="giftBoard-container">
                <MobileStyle/>
                <Tabs
                    arr={giftTabTitle}
                    onHandleSwitchTab={this.handleSwitchTab.bind(this)}
                    tabIndex={giftBoardState.tabIndex}
                    tabContainerClass={"giftBoard-giftTabs"}
                    tabLiClass={"giftBoard-giftTab"}
                    tabLiActiveClass={styles.giftTabActive}
                    tabTitleClass={styles.giftTabTitle}
                >
                    {Object.keys(giftBoardList).map((key, index) => {
                        return (
                            <div key={"categoryItem" + index}>
                                <Slider
                                    onHandleSliderIndex={(i) => this.onHandleSliderIndex("category" + key, i)}
                                    sliderIndex={giftBoardState.sliderIndex["category" + key]}
                                    rootClass={"giftBoard-giftBoardSliderRootStyle"}
                                >
                                    {
                                        this.getGiftList(giftBoardList[key]).map((itemList) => {
                                                return this.composeList(itemList);
                                            }
                                        )}
                                </Slider>
                            </div>
                        )
                    })}

                </Tabs>

            </div>

        )

    }

}

export default connect(mapStateToProps, mapDispatchToProps)(GiftBoard);