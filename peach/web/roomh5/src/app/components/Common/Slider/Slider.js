/**
 * @description: Slider 幻灯片组件
 * @author: Young
 * @param: sliderIndex: 幻灯片页数
 *         onHandleSliderIndex: 回调以重置幻灯片页数方法(传入当前翻页参数)
 *         arrayLeftClass: 向左按键
 *         arrayRightClass: 向右按键
 *         height: 高度设置，为了解决幻灯片出现多排的情况下，箭头偏移问题
 * @date: 2017/02/20
 */

import React, { Component } from "react";
import styles from "./slider.css";
import IconCommon from "../IconCommon/IconCommon.js"

class Slider extends Component{

    //向左按钮设置
    handleLeftButton(index) {
        let childrenLength = this.props.children.length;
        let currentIndex = index - 1;

        if(currentIndex == -1){
            currentIndex = childrenLength - 1; 
        }
        this.props.onHandleSliderIndex(currentIndex);
    }

    //向右按钮设置{{OPEN_WEB}}
    handleRightButton(index) {
        let childrenLength = this.props.children.length;
        let currentIndex = index + 1;

        if(currentIndex == childrenLength){
            currentIndex = 0; 
        }
        
        this.props.onHandleSliderIndex(currentIndex);
    }

    render(){

        let {
            sliderIndex=0, //slider index
            arrayLeftClass="", //左箭头样式
            arrayRightClass="", //右箭头样式
            rootStyle={}, //根目录样式
            sliderItemStyle="", //item样式
            rootClass="" //跟类
            } = this.props;

        return (
            <div className={ styles.sliderContainer + " " + rootClass } style={ rootStyle }>
                <div className={ styles.sliderItem + " " + styles.clearfix + " " +sliderItemStyle}>
                    { 
                        React.Children.map(this.props.children, (element, index) => {
                            if(index == sliderIndex){
                                return React.cloneElement(element, { 
                                    ref: "sliderItem" + index,
                                    style: { display: "block"}
                                });
                            }else{
                                return React.cloneElement(element, { 
                                    ref: "sliderItem" + index,
                                    style: { display: "none"}
                                });
                            }
                        })
                    }
                </div>
                <div className={ styles.sliderArrayLeft + " " + arrayLeftClass } onClick={ ()=>{ this.handleLeftButton(sliderIndex) } }>
                    <IconCommon iconType="slider" iconClass="sliderArrayLeft"></IconCommon>
                </div>
                <div className={ styles.sliderArrayRight + " " + arrayRightClass } onClick={ ()=>{ this.handleRightButton(sliderIndex) } }>
                    <IconCommon iconType="slider" iconClass="sliderArrayRight"></IconCommon>
                </div>
            </div>
        );
    }
}

export default Slider;