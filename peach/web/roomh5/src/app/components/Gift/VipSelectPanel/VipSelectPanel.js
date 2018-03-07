/**
 * Created by merci on 2017/5/9.
 * @description:用来展示vip送礼动画选择的面板
 */
import React, { Component } from "react";
import ListItem from "../../Common/ListItem/ListItem.js";
import styles from "./vipSelectPanel.css";

import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import * as giftActions from '../../../actions/giftActions.js';

const mapStateToProps = (state) => {
    return {
        giftCurrentState: state.giftCurrentState,
        userInfo: state.userInfo,
        gifts: state.gifts
    }
}
const mapDispatchToProps = (dispatch) => {
    return {
        giftActions: bindActionCreators(giftActions, dispatch)
    }
}

class VipSelectPanel extends Component{

    handleSelectGiftSendNum(giftNumber){
        this.props.giftActions.updateGiftInputNumber(Number(giftNumber));
        this.props.giftActions.updateGiftSelectPanelStatus(false);
    }

    render(){
        //通过redux获得礼物动画的数据
        let giftData=this.props.gifts.animationRouteList;
        let vipGiftData=[];

        giftData.map((item)=> {
            if ( item.isVip ) {
                //获得vip礼物动画数据
                vipGiftData.unshift(item);
            }
            return vipGiftData
        });

        return(
            <div>
                <div className={ styles.selectPanel }>
                    <div className={ styles.panelTitle }>
                        <span className={ styles.panelTitleIcon }></span>
                        <span>贵族专属</span>
                    </div>

                    {
                        vipGiftData.map((item, index)=>{
                            return (
                                <ListItem
                                    key={ index }
                                    hoverType={ "red" }
                                    leftElement={ <span className={ styles.selectItemImg + " " + styles["selectItemImg"+index]}></span> }
                                    content={ item.name }
                                    rightElement={ <span className={ styles.selectItemNum }>{ item.num }个</span> }
                                    onHandleClick={ ()=>{ this.handleSelectGiftSendNum(item.num) } }
                                >
                                </ListItem>
                            )
                        })
                    }

                </div>
            </div>
        )
    }
}

export default connect(mapStateToProps, mapDispatchToProps)(VipSelectPanel);