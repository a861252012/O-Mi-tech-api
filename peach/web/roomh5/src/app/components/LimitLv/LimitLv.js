/**
 * Created by merci on 2017/3/30.
 * @description:当钻石不够时，无法进入房间，此为展现进入房间条件的组件
 */

import React, { Component } from "react";
import IconCommon from "../Common/IconCommon/IconCommon.js";
import IconUser from "../Common/IconUser/IconUser.js";
import styles from "./limitLv.css"
class LimitLv extends Component{
    render(){
        let {limitData}=this.props
        return(
            <table className={ styles.limitContainer }>
                <tbody>
                <tr className={ styles.limitItem }>
                    <td className={ styles.limitEmail }>邮箱验证</td>
                    <td className={ styles.limitEmailStatus }>
                        {
                            limitData.mailCheckedLimit
                        }
                    </td>
                </tr>
                <tr className={ styles.limitItem }>
                    <td className={ styles.limitMoney }>当前余额</td>
                    <td className={ styles.limitMoneyNum }>
                        <span >{ limitData.richLimit }</span>
                        <IconCommon iconType="diamond" iconClass="diamondIcon"></IconCommon>
                    </td>
                </tr>
                <tr className={ styles.limitItem }>
                    <td className={ styles.limitRichLv }>进入财富等级</td>
                    <td className={ styles.limitRichLvIcon }>
                        <IconUser type="basic" lv={ limitData.richLvLimit }></IconUser>
                    </td>
                </tr>
                </tbody>
            </table>
        )
    }
}
export default LimitLv;