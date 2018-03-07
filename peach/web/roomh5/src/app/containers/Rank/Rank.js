/**
 * @description: 排行榜
 * @author: Merci
 * @date: 2017/2/20
 */

import React, { Component } from "react";
import Tabs from "../../components/Common/Tabs/Tabs.js";
import RankDayList from "../../components/RankList/RankDayList.js";
import RankWeekList from "../../components/RankList/RankWeekList.js";
import RankDayTotal from "../../components/RankList/RankDayTotal.js";
import styles from './rank.css';

class RoomRank extends Component {

    constructor(props){
        super(props);
        this.state = {
            tabIndex: 0
        }
    }
    //切换tab
    handleSwitchTab(index){
        this.setState({ tabIndex: index })
    }
    
    render() {

        let arr = [
            {
                key: "dayContributions",
                title: "本日贡献",
                subTitle: ""
            },

            {
                key: "weekContributions",
                title: "本周贡献",
                subTitle:""
            }
        ]
        /* TabBar附加样式*/
        let tabbarLiStyle = {
            fontSize:'14px'
        }
        let tabbarUlStyle = {
            marginLeft:'0px'
        }


        return (

            <div className={ styles.container }>
                <Tabs 
                    arr={arr} 
                    liStype={tabbarLiStyle}
                    ulStyle={tabbarUlStyle}
                    tabIndex={ this.state.tabIndex }
                    onHandleSwitchTab={ this.handleSwitchTab.bind(this) }
                >

                    <div>
                        <RankDayList></RankDayList>
                        <RankDayTotal></RankDayTotal>
                    </div>
                    <div>
                        <RankWeekList></RankWeekList>
                    </div>
                </Tabs>
            </div>
        )
    }
}

export default RoomRank;