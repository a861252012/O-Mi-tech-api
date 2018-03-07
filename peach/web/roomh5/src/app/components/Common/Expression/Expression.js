/**
 * @description 返回一个带有背景表情 的span 块级元素
 * @author seed
 * @param expressionName : 表情代码 (样式名称)   expressionStyle : 表情附加样式
 * @date 2017-2-22
 */
import React, {Component} from 'react';
import * as styles from "./expression.css";

class Expression extends Component {
      
      render(){
            let { expressionName = "a01" , 
                   expressionStyle = {} 
            } = this.props;

            return (
                  <span className={ styles.spanContainer + " " +  styles[expressionName] }  style={expressionStyle}></span>
            )
      }
}

export default Expression;