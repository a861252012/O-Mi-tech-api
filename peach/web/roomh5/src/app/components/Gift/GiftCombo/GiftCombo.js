/**
 * /**
 * @description: 礼物连击卡
 * @author: Young
 * Created by young on 2017/3/30.
 */

import React, { Component } from "react";
import styles from './giftCombo.css';
import IconGift from "../../Common/IconGift/IconGift.js";

class GiftCombo extends Component {

    render() {

        let { comboData, giftInfo } = this.props;
        let comboStyle = {};

        if(comboData.goodCategory == 4 || comboData.goodCategory == 5){
            comboStyle = {
                container: styles.luxuryContainer,
                info: styles.luxuryInfo,
                iconX: styles.luxuryIconX,
                number: styles.luxuryNumber,
                bg: styles.luxuryBg
            }

        }else{
            comboStyle = {
                container: styles.normalContainer,
                info: styles.normalInfo,
                iconX: styles.normalIconX,
                number: styles.normalNumber,
                bg: styles.normalBg
            }
        }

        let userName = comboData.sendHidden ? "隐身人" : comboData.sendName;

        return (
            <div className={ styles.wrapper }>
                <div className={ comboStyle.container }>
                    <IconGift
                        iconClass={ styles.comboIcon }
                        iconId={ comboData.gid }
                    ></IconGift>
                    <div className={ comboStyle.info }>
                        <div className={ styles.userName}>{ userName }</div>
                        <div className={ styles.iconName}>{ giftInfo.name }</div>
                    </div>
                    <div className={ styles.numberBox }>
                        <div className={ comboStyle.iconX }></div>
                        <div className={ comboStyle.number }>{ comboData.gnum }</div>
                    </div>
                </div>
                <div className={ comboStyle.bg }></div>
            </div>
        );
    }
}

export default GiftCombo;