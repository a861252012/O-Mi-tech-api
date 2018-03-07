/**
 * @author: Young
 * @date: 2017.5.15
 * @description: 单条列表
 * @params:
 *      leftElement 左侧元素 type: string | jsx
 *      content 内容 type: string | jsx
 *      rightElement 右侧元素 type: string | jsx
 *      contentStyle 内容显示的style
 *      rootStyle 根div的样式
 *      listHoverType hover状态的样式，现有"red", "white"
 */

import React, { Component } from "react";
import styles from "./listItem.css";

class ListItem extends Component {

    onHandleClick(){
        this.props.onHandleClick();
    }

    render(){

        let {
            leftElement = "",
            content = "",
            rightElement = "",
            contentStyle = {},
            rootStyle = {},
            hoverType = ""
            } = this.props;

        let contentInitStyle = {};
        let rootInitStyle = [styles.root];

        if(hoverType){
            rootInitStyle.push(styles["itemHover_" + hoverType]);
        }

        if(leftElement){
            Object.assign(contentInitStyle, { marginLeft: 70 });
        }

        if(rightElement){
            Object.assign(contentInitStyle, { marginRight: 60 });
        }

        if(contentStyle){
            Object.assign(contentInitStyle, contentStyle);
        }

        return (
            <div
                style={ rootStyle }
                className={ rootInitStyle.join(" ") }
                onClick={ ()=>{ this.onHandleClick() }}
            >
                <div className={ styles.leftElement }>{ leftElement }</div>
                <div
                    className={ styles.content }
                    style={ contentInitStyle }
                >{ content }</div>
                <div className={ styles.rightElement }>{ rightElement }</div>
            </div>
        )
    }
}

export default ListItem;
