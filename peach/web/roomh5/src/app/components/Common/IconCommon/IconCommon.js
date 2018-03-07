/**
 * @description:封装图标小组件，根据不同图标类型添加不同class名
 * @param:  iconType: icon类型， 取值为sider,diamond,slider
 *          iconClass: icon类, 
 *          iconLevel: 等级
 * @author: Merci
 * @date:2017/2/8
 */

import React, { Component } from "react";
import styles from './iconCommon.css';
// 封装左侧部分所用图标
class IconCommon extends Component{

    constructor(props){
        super(props);
        this.return_new_class = this.return_new_class.bind(this);
    }
    
    return_new_class(){
        let iconClass = this.props.iconClass;
        return styles.siderItemIcon+' '+ iconClass;
    }

    render(){
        let { iconType, iconClass } = this.props;
        let iconStyleClass = "";
        // 判断iconType是哪种类型，添加不同类名
        if(styles[iconClass]){
            switch(iconType){
                case "sider":
                    iconStyleClass = styles.siderItemIcon+ " " + styles[iconClass];
                    break;
                case "man":
                    iconStyleClass = styles.genderIcon+ " " + styles[iconClass];
                    break;
                case "woman":
                    iconStyleClass = styles.genderIcon+ " " + styles[iconClass];
                    break;
                default:
                    iconStyleClass = styles[iconClass];
            }
        }

          return  <div className={ iconStyleClass }></div>
               
    }
}
export default IconCommon;