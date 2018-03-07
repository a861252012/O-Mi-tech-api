/**
 * @description: VIP模块
 * @author: Merci
 * @date: 2017/2/20
 */

import React, { Component } from "react";
import TitlePanel from "../../components/Common/TitlePanel/TitlePanel.js";
import Button from "../../components/Common/Button/Button.js";
import RankVipList from "../../components/RankList/RankVipList.js";
import Common from "../../utils/Common.js";
import { bindActionCreators } from 'redux';
import { connect } from "react-redux";
import * as userActions from "../../actions/userActions.js";

import styles from './vip.css';

const mapStateToProps = (state) => {
  return {
    userInfo: state.userInfo
  }
}

const mapDispatchToProps = (dispatch) => {
  return {
    userActions: bindActionCreators(userActions, dispatch),
  }
}


class RoomVip extends Component {
    handleOpenNoble(uid){
        Common.handleBtnVip(uid, this.props.userActions.sendVIPMessage);
    }

    render() {

        let roomid = this.props.userInfo.roomid;

        return (
            <div className={ styles.container }>
                <TitlePanel titleText="贵宾席" titleStyle={{
                    position: "absolute",
                    width: "100%"
                }}></TitlePanel>
                <RankVipList></RankVipList>
                <div className={ styles.buttonWrapper }>
                    <Button
                        text="开通贵族"
                        type="round"
                        size="small"
                        style={{
                            marginTop: 12
                        }}
                        buttonClass="radiusButton"
                        onHandleClick={ ()=>this.handleOpenNoble(roomid) }
                    >
                    </Button>
                </div>
            </div>
        )
    }
}

export default connect(mapStateToProps, mapDispatchToProps)(RoomVip);