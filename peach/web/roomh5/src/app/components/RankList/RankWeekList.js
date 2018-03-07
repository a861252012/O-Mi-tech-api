/**
 * @description: 周排行榜列表
 * @author: Merci
 * @param:添加参数rankName及rankData,其中rankName为ul列表 类名，rankData为列表数据
 *        height:设置排行列表高度
 * @date: 2017/2/22
 */

import React, { Component } from "react";
import RankBasicList from "../Common/RankBasicList/RankBasicList.js";
import RankTotal from "../Common/RankTotal/RankTotal";
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import Common from "../../utils/Common.js";
import * as actions from "../../actions/commonActions";
const mapStateToProps=(state)=>{
    return {
        rankWeekData: state.giftCurrentState.userDataWeek,
    }
}
const mapDispatchToProps=(dispatch)=>{
    return {
        actions:bindActionCreators(actions,dispatch)
    }
}
class RankWeekList extends Component{

    //加总money的方法
    handleSumMoney(data){
        let sumMoney=0;
        for(let item in data){
             sumMoney+=parseInt(data[item].score);
        }
        return sumMoney;
    }

    //初始化数据
    componentDidMount(){
        this.props.actions.fetchRankWeekListData();
    }


    render(){

        return (
            <div>
                <RankBasicList
                    style={{
                        height: 'calc((100vh - 40px - 40px)/2 - 45px - 30px)',
                        minHeight: 210,
                        maxHeight: 325
                    }}
                    rankData={ this.props.rankWeekData }
                    rankList={ Common.convertObjToArray(this.props.rankWeekData, "score")}
                ></RankBasicList>
                <RankTotal keyWord="周" handleSumMoney={ this.handleSumMoney(this.props.rankWeekData) }></RankTotal>
            </div>
        )
    }
}

export default connect(mapStateToProps,mapDispatchToProps)(RankWeekList);