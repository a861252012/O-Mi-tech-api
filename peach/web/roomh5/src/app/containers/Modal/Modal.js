/**
 * @description:用于实现限制房间弹窗
 * @author：Merci
 * @Date：2017/3/30
 */
import React,{ Component } from "react";
import Dialog from "../../components/Common/Dialog/Dialog.js";
import Button from "../../components/Common/Button/Button.js";
import IconCommon from "../../components/Common/IconCommon/IconCommon.js";
import IconUser from "../../components/Common/IconUser/IconUser.js";
import Common from '../../utils/Common.js';
import {connect} from "react-redux";
import {bindActionCreators} from "redux";
import * as userActions from "../../actions/userActions.js";
import styles from "./modal.css";

const mapStateToProps=(state)=>{
    return {
        limitRoomData: state.limitRoomData,
        userInfoData: state.userInfo
    }
}
const mapDispatchToProps=(dispatch)=>{
    return{
      userActions:bindActionCreators(userActions,dispatch)
    }
}

class Modal extends Component{
    constructor(props){
        super(props);
        this.state={
            dialog:{
                modal:false
            }
        }
    }

    //充值
    handleCharge(){
        window.open("/charge/order");
    }
    //开通贵族
    handleOpenNoble(uid){
        Common.handleBtnVip(uid, this.props.userActions.sendVIPMessage);
    }
    render(){
        let dialogStyles={
            width:"500px",
            height:"350px",
            left:"120px",
            top:"55px",
            borderRadius:"10px"
        }

        let { limitRoomData, userInfoData }=this.props;

        //通过room limit 的 open字段来开启和关闭弹窗
        return(
           <div className={ styles.container }>
               <Dialog dialogStyles={ dialogStyles }
                       dialogClose={ false }
                       open={ (limitRoomData.open && (limitRoomData.open == 1) && (userInfoData.uid !== userInfoData.roomid) ) ? true :false }
               >
                    <div className={ styles.modalContainer }>
                        <h2 className={ styles.modalTitle }>您<span>未到达</span>进入房间的条件！</h2>
                        <div className={ styles.modalContent }>
                            <div className={ styles.modalContentLeft }>
                                <h3>当前房间进入条件</h3>
                                <table className={ styles.limitContainer }>
                                    <tbody>
                                    <tr className={ styles.limitItem }>
                                        <td className={ styles.limitEmail }>邮箱验证</td>
                                        <td className={ styles.limitEmailStatus } >
                                            {
                                                ( limitRoomData.mailCheckedLimit===0 ) ? "无需验证" : "需进行验证"
                                            }
                                        </td>
                                    </tr>
                                    <tr className={ styles.limitItem }>
                                        <td className={ styles.limitMoney }>当前余额</td>
                                        <td className={ styles.limitMoneyNum }>
                                            <span >{ limitRoomData.richLimit }</span>
                                            <IconCommon iconType="diamond" iconClass="diamondIcon"></IconCommon>
                                        </td>
                                    </tr>
                                    <tr className={ styles.limitItem }>
                                        <td className={ styles.limitRichLv }>进入财富等级</td>
                                        <td className={ styles.limitRichLvIcon }>
                                            <IconUser type="basic" lv={ limitRoomData.richLvLimit }></IconUser>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div className={ styles.modalContentRight }>
                                <h3>您目前的条件</h3>
                                <table className={ styles.limitContainer }>
                                    <tbody>
                                    <tr className={ styles.limitItem }>
                                        <td className={ styles.limitEmail }>邮箱验证</td>
                                        <td className={ styles.limitEmailStatus }>
                                            {
                                                (userInfoData.emailValid===0) ? "未通过" : "已通过"
                                            }
                                        </td>
                                    </tr>
                                    <tr className={ styles.limitItem }>
                                        <td className={ styles.limitMoney }>当前余额</td>
                                        <td className={ styles.limitMoneyNum }>
                                            <span className={ styles.limitUserMoney }>{ userInfoData.points }</span>
                                            <IconCommon iconType="diamond" iconClass="diamondIcon"></IconCommon>
                                        </td>
                                    </tr>
                                    <tr className={ styles.limitItem }>
                                        <td className={ styles.limitRichLv }>进入财富等级</td>
                                        <td className={ styles.limitRichLvIcon }>
                                            <IconUser type="basic" lv={ userInfoData.richLv }></IconUser>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>

                        </div>
                        <div className={ styles.modalOpenTips }>贵族身份，将无视任何进入限制，赶紧开通贵族吧！</div>
                        <div className={ styles.modalBtn }>
                            <Button
                                text="充值"
                                type="round"
                                style={{width:"86px"}}
                                size="small"
                                buttonClass="radiusButton"
                                onHandleClick={ ()=>this.handleCharge() }
                            >
                            </Button>
                            <Button
                                text="开通贵族"
                                type="round"
                                style={{ marginTop: "0px"}}
                                size="small"
                                buttonClass="radiusButton"
                                onHandleClick={ ()=>this.handleOpenNoble('uid') }
                            >
                            </Button>
                        </div>
                    </div>
               </Dialog>
           </div>
        )
    }
}
export default connect(mapStateToProps,mapDispatchToProps)(Modal);