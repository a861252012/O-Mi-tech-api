/**
 * @description: 排行榜列表总计组件
 * @author: Merci
 * @param: keyWord:关键字，handleSumMoney:加总的方法
 * @date: 2017/3/24
 */

import React, { Component } from "react";
import styles from "./rankTotal.css";

class RankTotal extends Component{

    render(){
        let{keyWord="", handleSumMoney=""}=this.props;
        return (
            <div className={styles.rankMoneyTotal}>
                <span>本{keyWord}总计：</span>
                <span>{handleSumMoney}钻</span>
            </div>
        )
    }
}

export default RankTotal;