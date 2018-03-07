/**
 * @description: 日排行榜加总
 * @author: Merci
 * @date: 2017/3/24
 */

import React, { Component } from "react";
import { bindActionCreators } from 'redux';
import RankTotal from "../Common/RankTotal/RankTotal";
import { connect } from 'react-redux';

const mapStateToProps=(state)=>{
    return {
        userDayTotal:state.giftCurrentState.userDayTotal,
    }
}
class RankDayTotal extends Component{
    render(){
        return (
            <RankTotal keyWord="日" handleSumMoney={ this.props.userDayTotal }></RankTotal>
        )
    }
}

export default connect(mapStateToProps,null)(RankDayTotal);