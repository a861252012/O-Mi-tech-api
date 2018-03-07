/**
 * @description 送礼 礼物通知 列表
 * @author  seed
 * @param  无依赖
 * @date 2017-2-9
 */
import React, { Component } from "react";
import {bindActionCreators} from 'redux';
import {connect} from "react-redux";
import * as actions from '../../../actions/giftActions.js';
import ScrollList from "../../Common/ScrollList/ScrollList.js";
import styles from "./giftsList.css";

const mapStateToProps = (state) =>{
      return {
            giftList:state.gifts.giftList
      }
}
const mapDispatchToProps = (dispatch) =>{
      return {
            action:bindActionCreators(actions,dispatch)
      }
}

class GiftsList extends Component{

      render(){


            //礼物列表  附加样式（未展开）
            const giftsListStyle  = {
                  height:"115px",
                  padding:'0px 10px',
                  marginTop: '5px',
                  boxSizing:"border-box"
            }

            //礼物列表  附加样式（展开）
            const spreadGiftsListStyle  = {
                  height: 'calc(100vh - 80px - 55px)',
                  maxHeight: 745,
                  padding:'0px 10px',
                  marginTop: '5px',
                  boxSizing:"border-box",
            }

            //子元素附加样式
            const sonElementStyle = {
                  marginTop:"6px",
            }

            let { giftList = [] } = this.props;
            return  (
                <ScrollList name="giftsList" scrollStyle={ this.props.lengthChange ? spreadGiftsListStyle : giftsListStyle } sonElementStyle = { sonElementStyle } truelyData={ giftList }></ScrollList>
            )
      }
}
export default connect(mapStateToProps,mapDispatchToProps)(GiftsList);