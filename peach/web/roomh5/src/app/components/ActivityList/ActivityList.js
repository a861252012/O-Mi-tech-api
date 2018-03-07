/**
 * @description: sider部分 活动弹窗具体数据
 * @author：Merci
 * @Date：2017/3/20
*/
import React, { Component } from "react";
import IconCommon from "../Common/IconCommon/IconCommon.js";
import styles from "./activityList.css";

import {connect} from "react-redux";


const mapStateToProps=(state)=>{
    return {
        activityList: state.siderActivityList
    }
}

//生成活动排行列表
class ActivityList extends Component{

    render(){

        let dataItems = this.props.dataItems;
        return (
            <div>
                <div className={styles.rankContainer} >
                    <ul className={styles.rankName}>
                        {
                            dataItems.map((item,index)=>{

                                let rankStyle="";
                                if(index===0){
                                    rankStyle=styles.rankItemNumOne
                                }else if(index===1){
                                    rankStyle=styles.rankItemNumTwo
                                }else if(index===2){
                                    rankStyle=styles.rankItemNumThree
                                }else{
                                    rankStyle=styles.rankItemNumFour
                                }
                                return (
                                    <li className={styles.rankItem} key={ index }>
                                        <span className={rankStyle}>{index+1}</span>
                                        <span className={styles.rankItemName}>{ item.nickname }</span>
                                        <span className={styles.rankItemMoney}>{ item.score }</span>
                                        <IconCommon iconType="diamond" iconClass="diamondIcon"></IconCommon>
                                    </li>
                                )
                            })
                        }
                    </ul>
                </div>
            </div>
        )
    }
}
class ActivityListTab extends Component{

    render(){
        let data1=[];
        let data2=[];
        this.props.activityList.map((item)=>{
            if(item.type == 1 && data1.length < 5){
                data1.push(item);
            }

            if(item.type == 2 && data2.length < 5){
                data2.push(item);
            }
        })
        return (
            <div className={ styles.dialogNewStylesActivity }>
                <div className={ styles.activityContainer }>
                    <h3>女神榜</h3>
                    <ActivityList dataItems={ data1 }></ActivityList>
                </div>
                <div className={ styles.activityContainer+" "+ styles.activityRich }>
                    <h3>富豪榜</h3>
                    <ActivityList dataItems={ data2 }></ActivityList>
                </div>
            </div>
        )
    }
}
export default connect(mapStateToProps,null)(ActivityListTab);