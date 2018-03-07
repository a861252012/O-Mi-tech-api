/**
 * @description:封装礼物图标小组件，根据不同图标类型添加不同class名
 * @param:  iconSize: icon尺寸， 取值为big, small
 *          iconClass: icon类,
 *          iconClass: 外部样式,
 *          iconId: id
 * @author: Young
 * @date:2017/3/17
 */

import React, { Component } from "react";
import styles from './iconGift.css';
// 封装左侧部分所用图标
class IconGift extends Component{

    render(){
        let { iconId, iconClass, iconStyle = {}, iconSize = 'big', title } = this.props;
        let iconCurrentClass = "";
        let iconCurrentStyle = {};

        let backgroundStyle = {
            backgroundImage: "url(" + window._flashVars.httpRes + "image/gift_material/"+ iconId+ ".png)"
        }

        switch (iconSize){
            case "small":
                iconCurrentClass = styles.basic + " " + styles.smallSize;
                break;
            default:
                iconCurrentClass = styles.basic;
        }

        iconCurrentStyle = Object.assign({}, backgroundStyle, iconStyle);
        iconCurrentClass = iconCurrentClass + " " + iconClass;

        return  <div className={ iconCurrentClass } style={ iconCurrentStyle } title={ title }></div>
               
    }
}
export default IconGift;