/**
 * @description: 返回一个<span>用户等级图标<span> 的块级元素
 * @author: seed
 * @param: lv : 用户等级  mode : 用户等级处理模式 ( rich / vip / auchor ) iconStyle : icon 附加样式
 * @date: 2017-2-16
 */
import React, { Component } from "react";
import styles from "./iconUser.css";

class IconUser extends Component {
      render(){
            let { 
                  lv = 0 ,
                  type = "basic", 
                  iconStyle = {}      
            } = this.props;

            let userLvClass = "";
            let spanContainer = "";

            if(lv === 0 || lv === 1){
                  return <span></span>;
            }

            //根据不同type  拼接用户等级样式
            switch(type){
                  case "basic":
                        userLvClass = "basicLevel"+lv;
                        spanContainer = "basicContainer";
                        break;
                  case "vip":
                        userLvClass = "vipLevel"+lv;
                        spanContainer = "vipContainer";
                        break;
                   case "auchor":
                        userLvClass = "auchorLevel"+lv;
                        spanContainer = "basicContainer";
                        break;
                  default:
                        userLvClass = "richLevel"+lv;
                        spanContainer = "richContainer";
            }

            return <span style={ iconStyle } className={ styles[spanContainer] + " " +styles[userLvClass] }></span>;
      }
}
export default IconUser;