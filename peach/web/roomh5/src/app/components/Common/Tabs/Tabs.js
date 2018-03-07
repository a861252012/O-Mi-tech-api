/**
 * @description: 切换卡组件。需要在调用的时候,  实现 this.returnCtrlKey(key)  来得到点击后的回调key
 * @update Young
 * @date: 2017-2-20
 * @param:
 * arr : {
 *    key : val
 *    title : val
 *    subTitle:"afaf"    subTitle用来显示数字（字符串也可以），当其存在时，增加标签span与title分行显示
 * }
 * Tabs 循环arr 对象中的值
 * returnCtrlKey 返回对象的 Key
 * skinClass：皮肤样式
 * ulStyle : ul 附加样式
 * liStyle : li 附加样式
 * tabIndex: tab激活序列
 * tabContainerClass: tab容器样式类
 * tabLiActiveClass: tab li样式类
 * tabLiClass: tab li 样式类
 * tabTitleClass: tab title 样式类
 */

import React, { Component } from "react";
import styles from './tabs.css';

//Panel 的可视面板
class TabsTitle extends Component{

      handleSwtichTab(key, index){
            //返回 回调Key
            return this.props.handleSwitchTab(index);
      }

      render(){

            let { 
                  ulStyle={},
                  liStyle={}, 
                  arr=[],
                  skinClass = "",
                  tabIndex = 0,
                  tabContainerClass="", 
                  tabUlClass="",
                  tabLiClass="", 
                  tabLiActiveClass="", 
                  tabTitleClass=""
            } = this.props;

            //如果props.skinClass 未设置的话 ,启用默认皮肤样式
            let _skinClass = skinClass ? styles[skinClass] : styles["purple"];
            
            return (
                  <div className={ styles.tabs+" "+_skinClass + " " + tabContainerClass }>
                        <ul style={ ulStyle } className={ tabUlClass }>
                              {arr.map((item, index)=>{
                                    //如果props.defaultActiveKey 未设置的话, 走默认设置
                                    const activeClassNum = (tabIndex == index) ? tabLiClass + " " + styles.active + " " + tabLiActiveClass : tabLiClass;
                                    return (
                                          <li key={ item.key } style={ liStyle } className={ activeClassNum } onClick={
                                                ()=>{ this.handleSwtichTab(item.key, index) }
                                          }>
                                                <span className={ tabTitleClass }>{ item.title }</span>
                                                <div className={ styles.subTitle }>{ item.subTitle }</div>
                                          </li>
                                    )
                              })}
                        </ul>
                  </div>
            )
      }
}

class TabsPanel extends Component{
      render(){
            let { tabIndex } = this.props;
            return (
                  <div>
                        { React.Children.map(this.props.children, (element, index) => {
                              if(index == tabIndex){
                                    return React.cloneElement(element, {
                                          ref: "tabPanelItem" + index,
                                          style: { display: "block" },
                                          key: "tabPanelItem" + index
                                    });
                              }else{
                                    return React.cloneElement(element, {
                                          ref: "tabPanelItem" + index,
                                          style: {display: "none"},
                                          key: "tabPanelItem" + index
                                    })
                              }
                        })}
                  </div>
            )
      }
}

class Tabs extends Component{

      //将回调 key 再次返回上层容器
      onHandleSwitchTab(index){
            this.props.onHandleSwitchTab(index);
      }

      render(){

            let {ulStyle = {}, liStyle = {} , arr = {}, skinClass = "", tabIndex = 0 } = this.props;
            return (
                  <div>
                        <TabsTitle { ...this.props }  handleSwitchTab={ this.onHandleSwitchTab.bind(this) }></TabsTitle>
                        <TabsPanel tabIndex={ tabIndex }>{this.props.children}</TabsPanel>
                  </div>
            );
      }
}

export default Tabs;