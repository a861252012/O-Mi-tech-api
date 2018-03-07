/**
 * Created by young on 7/3/17.
 */
import React, { Component } from "react";

import { connect } from "react-redux";
import { bindActionCreators } from "redux";
import * as actions from "../../actions/commonActions.js";

const versionStyle = {
  textAlign: "center",
  color: "#b01a51",
  height: "20px"
}

const mapStateToProps = (state) => {
  return {
    version: state.version
  }
}

const mapDispatchToProps = (dispatch) => {
  return {
    actions: bindActionCreators(actions, dispatch)
  }
}

class Version extends Component {

  componentDidMount(){
    let version = [Config.publishVersion, Config.subPublishVersion].join(" ");
    this.props.actions.setVersion(version);
  }

  render(){

    let version = this.props.version;

    return (
        <div style={ versionStyle }>{ version }</div>
    )
  }
}

export default connect(mapStateToProps, mapDispatchToProps)(Version);