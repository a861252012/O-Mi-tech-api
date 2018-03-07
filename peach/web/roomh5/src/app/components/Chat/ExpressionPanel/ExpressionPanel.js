/**
 * @description 表情面板控件    需要在调用的时候,  实现 this.handleExpressionCode ( code )  来得到点击后的回调code 
 * @author seed
 * @param 无依赖
 * @date 2017-2-22
 */
import React, {Component} from 'react';

//表情组件
import Expression from "../../Common/Expression/Expression.js";

import styles from "./expressionPanel.css";

const MobileStyle = () => {
  if (window.isMobile) {
    return (
        <style type="text/css">
          {
            '.expressionPanel-container{bottom: 190px;}'
          }
        </style>
    )
  }else{
    return <noscript></noscript>
  }
}

class ExpressionBox extends Component{

      handleCode( code ){
            return this.props.passCode(code);
      }

      render(){

            let { expressionCode = "none" } = this.props;
            
            this.handleCode = this.handleCode.bind(this);

            return (
                  <div className={ styles.expressionBox } onClick={ ()=>{ return this.handleCode( expressionCode ) } }>
                        <Expression expressionName={ expressionCode }></Expression>
                        <span className={ styles.expressionBoxBg }></span>
                        <span className={ styles.expressionBoxHover }></span>
                  </div>
            )
      }

}


class ExpressionPanel  extends Component {

      passCode( code ){
            if(code !== "none"){
                  this.props.handleExpressionCode(code);
            }
      }

      render(){

            let ctrlStyle = this.props.expressionData.ctrl ? {display:"block"} : {display:"none"};

            //回调方法 加入生命周期
            let allExpression = [
                  "a01","a02","a03","a04","a05","a06","a07",
                  "a08","a09","a10","a11","a12","a13","a14",
                  "a15","a16","a17","a18","a19","a20","a21",
                  "a22","a23","a24","a25","a26","a27","a28",
                  "a29","a30","a31","a32","a33","a34","a35",
                  "a36","a37","a38","a39","a40","a41","a42",
                  "none","none","none","none","none","none"
            ]

            let passCode = this.passCode.bind(this);

            return (
                  <div className="expressionPanel-container"  style={ ctrlStyle } ref="Expression">
                        <MobileStyle />
                        <div className={ styles.expressionContent } >
                              {allExpression.map(function(code,key){
                                    return <ExpressionBox expressionCode = { code } key = { key } passCode = { passCode }></ExpressionBox>
                              })}
                        </div>
                  </div>
            )
      }
}

export default ExpressionPanel;