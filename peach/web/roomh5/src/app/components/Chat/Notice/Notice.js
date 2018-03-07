/**
 * @description 房间公告
 * @author  seed
 * @param  无依赖
 * @date 2017-2-9
 */
import React, { Component } from "react";
import ScrollList from "../../Common/ScrollList/ScrollList.js";
import {bindActionCreators} from 'redux';
import {connect } from 'react-redux';
import * as actions from '../../../actions/chatActions.js';
import styles from "./notice.css";

const mapStateToProps = (state) =>{
      return {
            noticeList:state.chatRoomNotice,
      }
}

const mapDispatchToProps = (dispatch ) =>{
      return {
            action:bindActionCreators(actions,dispatch)
      }
}

class Notice extends Component{

      render(){
        
            let { 
                  noticeList = []
             } = this.props;
      
            //主播公告 附加样式
            let noticeStyle = {
                  height:"60px",
                  padding:"0px 10px",
                  boxSizing:"border-box",
            }

            return  (      
                  <div className={ styles.container }>
                        <span className = { styles.NoticeBorder }></span>
                        <ScrollList name="noticeList" scrollStyle={ noticeStyle } truelyData={ [noticeList] }></ScrollList>
                        <span className = { styles.NoticeBorder }></span>
                  </div>
            )
      }

      componentDidMount(){
            //this.props.action.fetchUpdateNotice();
      }

}
export default connect(mapStateToProps,mapDispatchToProps)(Notice);