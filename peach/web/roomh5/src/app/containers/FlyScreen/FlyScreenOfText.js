/**
 * @description 飞屏主要组件  
 *           ( 负责Redux数据层 和 显示层 的完全隔离  需要注意的点有:  )
 *           一、本组件由于特殊的业务需求 ( 既要保持连接redux数据 ，又要因为dom动画的特殊性... 需在两次渲染之间 输出一次空渲染 ( 来删除展示完毕的dom  ) . )
                    1. 无法封装为可复用组件
                    2. 需要有独立的 reducer
                    3. 需要输出一个展示内容的二级组件   并且实现一次回调 到分配dom 的方法上
                    4. 需要输出一个切换状态用的二级组件，并且使用它的 componentWillMount 周期
 * 
 *  @author seed
 *  @date 2017-3-8
 */

import React, {Component } from "react";
import {bindActionCreators} from 'redux';
import {connect} from "react-redux";
import * as animationActions from '../../actions/animationActions.js';
import styles from "./FlyScreen.css";

/*二级动画组件*/

      //队列型动画组件
      import QueueAnimation from "./FlyScreenOfText/Queue.js";
      //飞屏自检缓存数据组件
      import CheckQueue from "./FlyScreenOfText/Check.js";


const mapStateToProps = (state) =>{      
      return {

            /* status 存储状态代码...   
                  0 => 空闲..
                  1 => 进行中... 
                  2 => 从子组件中获得回调
                  3 => 强制停止
            */  
            flyScreenAnimationStatus:state.flyScreenOfText.status,      //队列型动画状态码
            flyScreenAnimationData:state.flyScreenOfText.data            //队列型动画数据
      }
}

const mapDispatchToProps = (dispatch)=>{
      return {
            action:bindActionCreators(animationActions,dispatch)
      }
}

class FlyScreenOfText extends Component {

      //检查队列
      getQueueAnimation( renderKey = false ){
            
            if(renderKey){
                  //得到回调的情况  
                  this.action.changeQueueStatus(3);
            }else{

                  let { flyScreenAnimationData = [] , flyScreenAnimationStatus = 3 } = this.props;

                  switch(flyScreenAnimationStatus){
                        case 0:
                              return <QueueAnimation animationData={ flyScreenAnimationData } handleBack={ this.getQueueAnimation }></QueueAnimation>;            
                              break;
                        case 3:
                              //紧急停止
                              return <CheckQueue></CheckQueue>
                        default:
                              break;
                  }

            } 
      }

      render(){
            return (
                  <div className={ styles.animationBox }>
                        { this.getQueueAnimation() }
                  </div>
            )
      }

}

export default connect(mapStateToProps,mapDispatchToProps)(FlyScreenOfText);
