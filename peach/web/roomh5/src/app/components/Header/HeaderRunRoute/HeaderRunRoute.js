/**
 * @description: 接收头部跑道送礼信息的组件
 * @author: Merci
 * @date: 2017/3/24
 */
import React, { Component } from "react";
import {bindActionCreators} from 'redux';
import {connect} from 'react-redux';
import * as giftActions from '../../../actions/giftActions.js';
import styles from "../../../containers/Header/header.css";
import TrueResult from '../../Common/TrueResult/TrueResult.js';

const mapStateToProps=(state)=>{
    return {
        giftCurrentState: state.giftCurrentState
    }
}
const mapDispatchToProps=(dispatch)=>{
    return {
        giftActions: bindActionCreators(giftActions, dispatch)
    }
}
class HeaderRunRoute extends Component{

    //点击我要上跑道操作gift模块切换到奢华tab
    handleRunRoute(){
        this.props.giftActions.updateGiftBoardTabIndex(4)
    }

    render(){
        let { giftCurrentState }=this.props;
        let attachStyle = {
            marginTop: "-6px",
            fontWeight: "bold"
        }
        return(
            <div className={styles.headerDetail}>
                <div className={styles.headerGift}>
                    <TrueResult datas={[giftCurrentState.luxury]}  attachStyle={attachStyle}  componentName="Header" ></TrueResult>
                </div>
                <div className={ styles.headerGiftBg }>
                    <div className={ styles.gradient }></div>
                    <div className={ styles.spotlight }></div>
                </div>
                <div className={styles.headerGame} onClick={this.handleRunRoute.bind(this)}>我要上跑道</div>
            </div>
        )
    }
}
export default connect(mapStateToProps,mapDispatchToProps)(HeaderRunRoute);