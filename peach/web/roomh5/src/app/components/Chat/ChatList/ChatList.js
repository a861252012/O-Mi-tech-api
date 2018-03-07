/**
 * @description 信息列表
 * @author  seed
 * @param  无依赖
 * @date 2017-2-9
 */
import React, { Component } from "react";
import ScrollList from "../../Common/ScrollList/ScrollList.js";
import {bindActionCreators} from 'redux';
import {connect } from 'react-redux';
import * as actions from '../../../actions/chatActions.js';
import styles from "./chatList.css";

const mapStateToProps = (state) =>{
      return {
            chatList:state.chat.list,
      }
}

class ChatList extends Component{

      render(){

            //通知列表 附加样式
            let chatListStyle = {
                  height: 'calc(100vh - 80px - 45px - 120px - 75px - 30px)',
                  minHeight: 298,
                  maxHeight: 550,
                  padding:"0px 10px",
                  boxSizing:"border-box"
            }

            let mobileChatListStyle = {
              height: 'calc(100vh - 80px - 45px - 120px - 75px - 30px - 150px)',
              minHeight: 180,
              maxHeight: 400,
              padding:"0px 10px",
              boxSizing:"border-box"
            }

            let sonElementStyle = {
                  margin:"10px 0px"
            }

            let chatList = this.props.chatList;
            
            return  (
                  <div className={ styles.container }>
                        <ScrollList name="chatList" scrollStyle={ window.isMobile ? mobileChatListStyle : chatListStyle } truelyData={ chatList } sonElementStyle = { sonElementStyle } ></ScrollList>
                  </div>
            )
      }
}
export default connect(mapStateToProps,null)(ChatList);