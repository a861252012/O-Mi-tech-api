/**
 * @description: 日排行榜列表
 * @author: Merci
 * @param:添加参数rankName及rankData,其中rankName为ul列表 类名，rankData为列表数据
 *        height:设置排行列表高度
 * @date: 2017/2/22
 */

import React, { Component } from "react";
import RankBasicList from "../Common/RankBasicList/RankBasicList.js";
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import Common from "../../utils/Common.js";


const mapStateToProps=(state)=>{
    return {
        rankDayData: state.giftCurrentState.userDataDay,
    }
}
class RankDayList extends Component{

    render(){

        return (
            <RankBasicList
                style={{
                    height: 'calc((100vh - 40px - 40px)/2 - 45px - 30px)',
                    minHeight: 210,
                    maxHeight: 325
                }}
                rankData={ this.props.rankDayData }
                rankList={ Common.convertObjToArray(this.props.rankDayData, "score")}
            >
            </RankBasicList>
        )
    }
}

export default connect(mapStateToProps,null)(RankDayList);