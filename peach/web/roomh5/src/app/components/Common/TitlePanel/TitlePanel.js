import React, { Component } from "react";
import Style from "./titlePanel.css";
class TitlePanel extends Component{
      render(){
            let {titleText = '' , titleStyle = {} } = this.props;
            return <div className={ Style.titlePanel } style={titleStyle}>{titleText}</div>;
      }
}
export default TitlePanel;