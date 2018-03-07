/**
 * @description: 按钮
 * @param: handleClick 按钮回调, 
 *          text 按钮文字内容 string
 *          icon 按钮图标 jsx节点对象
 *          style按钮附加样式
 *          buttonClass  按钮样式名称 (默认default)
 *          size 按钮尺寸，现已有参数small
 *          color 按钮颜色 pink purple
 *          type 按钮圆角样式 square（方角） round（圆角）
 * @author: Young
 * @date:2017-2-21
 */
import React, { Component } from "react";
import styles from './button.css';

class Button extends Component{

      handleClick(event) {
            const { onHandleClick } = this.props;

            if(typeof onHandleClick === "function"){
                  onHandleClick(event);
            }
      }

      render(){

            let { type="", text='', style={}, buttonClass="", icon="", color="pink", size="middle" } = this.props;

            //color 颜色
            let currentClass = color ? styles[color] : styles["pink"];
            
            //size 尺寸
            currentClass = currentClass + " " + (size ? styles[size]: "");

            //type 圆角方角类型
            currentClass = currentClass + " " + (type ? styles[type]: "");

            //外部设置button class
            currentClass = buttonClass ? (currentClass + " " + buttonClass) : currentClass;

            return (
                  <button className={ currentClass } style={ style } onClick={ (e)=>this.handleClick(e) }>{ icon }{ text }</button>
            );
      }
}

export default Button;