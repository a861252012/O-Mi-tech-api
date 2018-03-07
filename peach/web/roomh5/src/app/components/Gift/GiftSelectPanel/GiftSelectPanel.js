/**
 * Created by merci on 2017/5/9.
 * @description:用来展示送礼动画选择的面板
 */
import React, { Component } from "react";
import ListItem from "../../Common/ListItem/ListItem.js";
import Button from "../../Common/Button/Button.js";
import VipSelectPanel from "../VipSelectPanel/VipSelectPanel.js";

import { bindActionCreators } from 'redux';
import styles from "./giftSelectPanel.css";
import { connect } from 'react-redux';
import * as giftActions from '../../../actions/giftActions.js';

const mapDispatchToProps = (dispatch) => {
    return {
        giftActions: bindActionCreators(giftActions, dispatch)
    }
}
const mapStateToProps = (state) => {
    return {
        gifts: state.gifts
    }
}
class GiftSelectPanel extends Component{
    constructor(props){
        super(props);
        this.state = {
            vipPanel: true,
        }
    }
    //控制贵族专属按钮，用以切换贵族专属面板的显示与关闭
    handleShowVipPanel(){
        this.setState({
            vipPanel : !this.state.vipPanel,
        })
    }
    //实现点击在输入框中显示数量，并同时让面板消失
    handleSelectGiftSendNum(giftNumber){
        this.props.giftActions.updateGiftInputNumber(Number(giftNumber));
        this.props.giftActions.updateGiftSelectPanelStatus(false);
    }

    render(){

        let vipPanelStyle = {} ;
        let vipPanelArrow="";
        if( this.state.vipPanel ){
            vipPanelStyle = { display:"block" }
            vipPanelArrow = "<<<"
        }else{
            vipPanelStyle = { display:"none" }
            vipPanelArrow = ">>>"
        }

        //通过redux获得礼物动画的数据
        let giftData=this.props.gifts.animationRouteList;
        let commonGiftData=[];

        giftData.map((item)=> {
            if (!item.isVip) {
                //获得普通礼物动画数据
                commonGiftData.unshift(item);
            }
            return commonGiftData
        });

        return(
            <div className={ styles.panelContainer }>
                <div className={ styles.selectPanel }>
                    {
                        commonGiftData.map((item, index)=> {
                            return (
                                <ListItem
                                    hoverType={ "white" }
                                    key={ index }
                                    leftElement={ <span className={ styles.selectItemImg + " " + styles["selectItemImg"+index]}></span> }
                                    content={ item.name }
                                    rightElement={ <span className={ styles.selectItemNum }>{ item.num }个</span> }
                                    onHandleClick={ ()=>{ this.handleSelectGiftSendNum(item.num) } }
                                >
                                </ListItem>
                            )
                        })
                    }
                    <ListItem
                        onHandleClick={ this.handleShowVipPanel.bind(this) }
                        content={
                            <Button size="small" text={ <span>贵族专属{ vipPanelArrow }</span> }></Button>
                        }
                        hover
                        contentStyle={{
                            textAlign: "center"
                        }}
                    >
                    </ListItem>
                </div>
                <div className={ styles.vipSelectPanel } style={ vipPanelStyle }>
                    <VipSelectPanel></VipSelectPanel>
                </div>
            </div>
        )
    }
}
export default connect(mapStateToProps, mapDispatchToProps)(GiftSelectPanel);