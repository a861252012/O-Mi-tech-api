/**
 * @description: 排行榜列表组件
 * @author: Merci
 * @param:添加参数rankName及rankData,其中rankName为ul列表 类名，rankData为列表数据
 *        height:设置排行列表高度
 *        hideIcon: 是否隐藏名称按钮
 * @date: 2017/3/23
 */

import React, { Component } from "react";
import IconCommon from "../../Common/IconCommon/IconCommon.js";
import IconUser from "../../Common/IconUser/IconUser.js";
import UserPanel from "../UserPanel/UserPanel.js";
import styles from "./rankBasicList.css";

class RankBasicList extends Component{

    render(){

        const userNameStyle = {
            color: "#fff"
        }

        let { rankData, rankList=[], rankName="", style={}, hideIcon=false } = this.props;

        let rootStyle = Object.assign({}, style);

        return (

            <div className={styles.rankContainer} style={rootStyle}>
                <ul className={styles.rankName}>
                    {
                        rankList.map((key,index)=>{
                            let rankStyle="";
                            switch ( index ){
                                case 0:
                                    rankStyle=styles.rankItemNumOne;
                                    break;
                                case 1:
                                    rankStyle=styles.rankItemNumTwo;
                                    break;
                                case 2:
                                    rankStyle=styles.rankItemNumThree;
                                    break;
                                default:
                                    rankStyle=styles.rankItemNumFour;
                            }

                            //图标
                            let icon="";
                            if(rankName === "rankVip"){
                                icon=<IconUser type="vip" lv={ rankData[key].vip } iconStyle={{float: "right"}}></IconUser>;
                            }else {
                                icon=<div className={ styles.rankItemRight }>
                                    <span className={ styles.rankItemMoney }>{ rankData[key].score }</span>
                                    <IconCommon iconType="diamond" iconClass="diamondIcon"></IconCommon>
                                </div>
                            }

                            return (
                                <li className={ styles.rankItem } key={ index }>
                                    <span className={ rankStyle }>{ index+1 }</span>
                                    <UserPanel
                                        userInfo={ rankData[key] }
                                        attachStyle={ userNameStyle }
                                        hideIcon={ hideIcon }
                                    ></UserPanel>
                                    { icon }
                                </li>
                            )
                        })
                    }
                </ul>
            </div>

        )
    }
}

export default RankBasicList;