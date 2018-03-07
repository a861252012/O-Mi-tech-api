/**
 * @description: 礼物模块
 * @author: Young
 * @date: 2017/2/20
 */

import React, { Component } from "react";
import styles from "./gift.css";

const MobileStyle = () => {
  if (window.isMobile) {
    return (
        <style type="text/css">
          {'.gift-container{ position: relative; margin-top: 0px; top: initial; left: initial; width: auto; height: 150px; min-height: 150px;}'}
        </style>
    )
  } else {
    return (
        <style type="text/css">
          {
            '@media (max-width: 1400px){' +
            '.gift-container{ max-height: 400px; width: 560px; left: -561px; margin-top: 420px; min-height: 150px; height: calc((100vh - 480px) - 20px); }' +
            '}'
          }
        </style>
    )
  }
}

import GiftControl from "../../components/Gift/GiftControl/GiftControl.js";

class RoomGift extends Component {

    render() {
        return (
            <div className="gift-container" >
                <MobileStyle/>
                <GiftControl></GiftControl>
            </div>
        )
    }
}

export default RoomGift;