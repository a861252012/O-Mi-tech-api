/**
 * @description: 展示头部信息
 * @author: Merci
 * @date: 2017/2/20
 */

import React, { Component } from "react";
import HeaderUserInfo from "../../components/Header/HeaderUserInfo/HeaderUserInfo.js";
import HeaderRunRoute from "../../components/Header/HeaderRunRoute/HeaderRunRoute.js";
import {bindActionCreators} from 'redux';
import {connect} from 'react-redux';
import * as actions from '../../actions/commonActions.js';
import styles from "./header.css";

const mapDispatchToProps=(dispatch)=>{
    return {
         commonActions: bindActionCreators(actions, dispatch),
        // giftActions: bindActionCreators(giftActions, dispatch)
    }
}

class RoomHeader extends Component {

    componentDidUpdate(){
        this.props.commonActions.fetchLimitRoomData();
    }

    render() {

        let userStatus = window.OpenMenu;

        return (
            <div className={ styles.container }>
                <div className={styles.header}>
                    <a className={styles.headerLogo} href={ userStatus !== 0 ? "javascript:void(0);" : "/"}></a>
                    <HeaderRunRoute></HeaderRunRoute>
                    <HeaderUserInfo></HeaderUserInfo>
                </div>
            </div>
        )
    }
}

export default connect(null,mapDispatchToProps)(RoomHeader);