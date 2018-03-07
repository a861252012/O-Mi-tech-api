
/**
 * @description: main 主体框架
 * @author: Young
 * @date: 2017/2/20
 */

import React, { Component } from 'react';
import { Provider } from 'react-redux';
import RoomHeader from '../Header/Header.js';
import RoomSider from '../Sider/Sider.js';
import RoomVideo from '../Video/Video.js';
import RoomRank from '../Rank/Rank.js';
import RoomVip from '../Vip/Vip.js';
import RoomChat from '../Chat/Chat.js';
import FlyScreenOfText from "../FlyScreen/FlyScreenOfText.js";
import FlyScreenOfRiding from "../FlyScreen/FlyScreenOfRiding.js";
import FlyScreenOfGift from "../FlyScreen/FlyScreenOfGift.js";
import SpreadUserPanel from "../../components/Common/UserPanel/SpreadUserPanel.js";
import UserInfoPanel from "../../components/Common/UserPanel/UserInfoPanel.js";
import Version from "../Version/Version.js";
import Footer from '../Footer/Footer.js';

//礼物组合
import GiftSet from "../../components/Gift/GiftSet/GiftSet.js";
//单独图片礼物
import GiftImage from "../../components/Gift/GiftImage/GiftImage.js";

import FlashMiddleWare from "../../flashMiddleWare.js";
import RoomParking from "../../components/Parking/Parking.js";
import Modal from "../Modal/Modal.js";

import styles from './main.css';

const MobileStyle = () => {
  if (window.isMobile) {
    return (
        <style type="text/css">
          {'.main-contentCenter{ width: calc((100vh - 80px)/16*9); max-height: 820px; min-height: 650px; min-width: 400px; max-width: 462px;}'}
        </style>
    )
  } else {
    return (
        <style type="text/css">
          {
            '@media (max-width: 1400px){' +
            '.main-contentCenter{ width: 560px; }' +
            '}'
          }
        </style>
    )
  }
}

class Main extends Component {
    componentDidMount() {
        // resize the page
        if(window.isMobile) {
          // if the page is mobile page, resize it
          $("#chat").css({ marginLeft: this.getMainWidth() });
          $(window).resize(() => {
            $("#chat").css({ marginLeft: this.getMainWidth() });
          })
        }
    }
    getMainWidth(){
      return $("#main").width() + 1;
    }
    render() {

        const { store } = this.props;

        return (
            <div className={ styles.container }>
                <Provider store={store}>
                    <div className={ styles.main }>
                        <MobileStyle />
                        <RoomHeader />
                        <div className={ styles.content } id="content">
                            <div className="main-contentCenter" id="main" >
                                <RoomVideo />
                                <Modal />
                            </div>
                            <RoomChat />
                            <div className={ styles.contentInfo } id="side">
                                <RoomRank />
                                <RoomVip />
                            </div>
                            <FlyScreenOfGift></FlyScreenOfGift>
                        </div>
                        <FlyScreenOfRiding></FlyScreenOfRiding>
                        <RoomSider />
                        <FlyScreenOfText></FlyScreenOfText>
                        <FlashMiddleWare></FlashMiddleWare>
                        <SpreadUserPanel></SpreadUserPanel>
                        <UserInfoPanel></UserInfoPanel>
                        <RoomParking></RoomParking>
                        <GiftSet></GiftSet>
                        <GiftImage></GiftImage>
                        <Version></Version>
                        <Footer></Footer>
                    </div>
                </Provider>
            </div>
        )

    }

};

export default Main;
