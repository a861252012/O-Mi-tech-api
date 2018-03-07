/**
 * @description 本组件用于检查 queue 的 缓存数组是否有内容, 如果有内容在 componentDidMount 周期发送 action 激活 Animation 父组件
 * @author seed
 * @date 2017-3-12
 */

import React, {Component } from "react";
import {bindActionCreators} from 'redux';
import {connect} from "react-redux";
import * as animationActions from '../../../actions/animationActions.js';

const mapStateToProps = (state) =>{      
      return {
            /*status 存储状态代码...   
                  0 => 空闲..
                  1 => 进行中... 
                  2 => 从子组件中获得回调
                  3 => 强制停止
            */  
            flyScreenOfRidingStatus:state.flyScreenOfRiding.status,                    //队列型动画状态码
            flyScreenOfRidingData:state.flyScreenOfRiding.data,                        //队列型动画数据
            flyScreenOfRidingTemporyData:state.flyScreenOfRiding.temporyData           //队列型动画数据
      }
}

const mapDispatchToProps = (dispatch)=>{
      return {
            action:bindActionCreators(animationActions,dispatch)
      }
}

class CheckRiding extends Component{

      render(){
            return (
                  <span></span>
            )
      }

      componentDidMount(){
      //检查缓存队列数据。 如果有数据的话转移至正式队列
            let {
                flyScreenOfRidingStatus=0,
                flyScreenOfRidingData=[],
                flyScreenOfRidingTemporyData=[]
          } = this.props;


            if(flyScreenOfRidingData.length && flyScreenOfRidingTemporyData.length){
                  this.props.action.rideTransferData({
                        data:flyScreenOfRidingTemporyData,
                        temporyData:[],
                        status:0
                  })
            }
      }
}

export default connect(mapStateToProps,mapDispatchToProps)(CheckRiding);