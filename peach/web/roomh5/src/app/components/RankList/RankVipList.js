/**
 * @description: VIP排行榜列表
 * @author: Merci
 * @param:添加参数rankName及rankData,其中rankName为ul列表 类名，rankData为列表数据，
 *        height:设置排行列表高度
 * @date: 2017/2/22
 */

import React, { Component } from "react";
import RankBasicList from "../Common/RankBasicList/RankBasicList.js";
import { connect } from 'react-redux';

const mapStateToProps=(state)=>{
    return {
        userData: state.users.userData,
        vipList: state.users.vipList
    }
}

class RankVipList extends Component {

    render() {

        return (

            <RankBasicList
                rankName="rankVip"
                rankList={ this.props.vipList }
                rankData={ this.props.userData }
                hideIcon={ true }
                style={{
                    margin: '43px 0px 50px 0',
                    height: 'calc((100vh - 40px - 40px)/2 - 45px - 50px)',
                    minHeight: 200,
                    maxHeight: 305
                }}
            >
            </RankBasicList>

        )
    }
}
export default connect(mapStateToProps,null)(RankVipList);