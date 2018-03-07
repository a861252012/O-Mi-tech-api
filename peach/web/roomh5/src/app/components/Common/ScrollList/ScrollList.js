/**
 * @description: 这个组件是一个被相对定位的外容器。
 * 这个组件的主要意义是 ：绑定自定义滚动条样式  及 交互 (如高度控制) 
 * @author: seed
 * @param: scrollStyle 附加样式  scrollClass 附加类  truelyData 需要转交的数据  sonElementStyle 需要转交的子元素样式
 * @date: 2017-2-9
 */
import React, { Component } from "react";
import styles from './scrollList.css';

/** 气泡逻辑 , 数据输出在这个组件中 */
import TrueResult from '../TrueResult/TrueResult.js';

class ScrollList extends Component{
      constructor(props){
            super(props);
            this.state = {
                  refName:props.name
            }
      }
      //同步聊天列表的滚动条高度
      keepScrollTop(){
            let refName = this.state.refName;
            this.refs[refName].scrollTop = this.refs[refName].scrollHeight;
      }
      componentDidMount(){
            this.keepScrollTop();
      }
      componentDidUpdate(){
            this.keepScrollTop();
      }
      //同步聊天列表的滚动条高度

      render(){
            
            let {
                  scrollStyle={},
                  scrollClass="",
                  truelyData = [],
                  sonElementStyle = {},
                  name = ""
            } = this.props;

            this.keepScrollTop = this.keepScrollTop.bind(this);

            //输出聊天信息
            return (
                  <div className={ styles.container + " " + styles[scrollClass] || "" } style={ scrollStyle } ref={ this.state.refName }>
                        <TrueResult datas={ truelyData } sonElementStyle = { sonElementStyle } ></TrueResult>
                  </div>
            )
      }
      
}

export default ScrollList;