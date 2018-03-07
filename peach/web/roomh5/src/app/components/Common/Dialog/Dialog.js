/**
 * @description:实现弹窗功能（点击显示与关闭切换）
 * @param: DialogTitle: 弹窗标题， DialogDetail: 弹窗具体内容，dialogTitleSmall：小标题
 *         dialogStyles:控制dialog样式（width，height，display）,
 *         open:控制dialog显示的参数，false表示不显示，true表示显示
 *         dialogClose:控制dialog的叉叉是否显示的参数，如果dialogClose="false"，则叉叉不出现
 *         type：根据type类型来控制dialog的样式（因整个直播间较多地方用到此组件，故此参数一直扩展中）
 * @author: Merci
 * @date:2017/2/9
 */

import React, { Component } from "react";
import styles from './dialog.css';

function getStyles(props) {
    const { open, dialogStyles } = props;
    const initStyle = {
        root: {
            display: 'none'
        }
    }
    let currentStyle = Object.assign({}, initStyle, { root: dialogStyles });
    currentStyle.root.display = open ? "inline-block": "none";

    return currentStyle;
}

// 封装弹窗面板部分
class Dialog extends Component{

    //通过dialog的叉叉关闭dialog面板
    handleDialogClose(obj){
        obj.props.onRequestClose();
    }

    render(){
        let { dialogTitle, dialogDetail, dialogTitleSmall, theme="" , dialogClose=true , dialogCloseStyle = {}, dialogCloseClass = "" }=this.props;
        let content = this.props.children;
        let prepareStyles = getStyles(this.props);
        let that = this;
        let dialogRootClass="";
        switch (theme){
            case "purple":
              dialogRootClass = styles.dialogPurpleTheme;
                break;
            case "red":
              dialogRootClass = styles.dialogRedTheme;
                break;
            default:
              dialogRootClass = styles.dialogWhiteTheme;
        }
        //控制dialog的叉叉是否出现:通过传入false或者true来控制
        let dialogCloseInitStyle = {};
        let dialogCloseBasicStyle={}
        if( dialogClose === false ){
           dialogCloseBasicStyle={ display:"none" }
        }else{
           dialogCloseBasicStyle={}
        }

        dialogCloseInitStyle = Object.assign({}, dialogCloseBasicStyle,dialogCloseStyle);

        return  (
            <div style={ prepareStyles.root } ref="dialog"  className={ styles.dialogInitStyle+ " " + dialogRootClass }>
                <div className={ styles.dialogHead }>
                    <h4 className={ styles.dialogTitle }>{ dialogTitle }</h4>
                    <h5 className={ styles.dialogTitleSmall }>{ dialogTitleSmall }</h5>
                    <div className={ styles.dialogClose + " " + dialogCloseClass }
                         onClick={ (e) => {this.handleDialogClose(that)}}
                         style={ dialogCloseInitStyle }
                    ></div>
                </div>
                <div className={styles.dialogDetail}>{ dialogDetail }{ content }</div>
            </div>
        );
    }
}

export default Dialog;