/**
 * Created by young on 7/4/17.
 */
/**
 * 动画特效开启与关闭
 */
import React, { Component } from 'react';
import Button from '../../../components/Common/Button/Button.js';

import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import * as animationActions from '../../../actions/animationActions.js';

const mapStateToProps = (state) => {
  return {
    animationState: state.animationState
  }
}

const mapDispatchToProps = (dispatch) => {
  return {
    animationActions: bindActionCreators(animationActions, dispatch),
  }
}

const buttonStyle = {
  position: 'absolute',
  bottom: -37,
  right: 10,
  zIndex: 10
}

const mobileButtonStype = {
  position: 'absolute',
  bottom: 15,
  right: 10,
  zIndex: 10
}

class AnimationControl extends Component{

  handleClick(){
    //切换是否播放动画状态
    this.props.animationActions.toggleAnimationState();
    //清空动画
    this.props.animationActions.clearAllAnimation();
  }

  render(){
    return (
        <Button
            style={ window.isMobile ? mobileButtonStype : buttonStyle }
            text={ this.props.animationState ? "关闭特效": "开启特效" }
            onHandleClick={ ()=>{ this.handleClick() } }
            color={ this.props.animationState ? "pink": "white" }
            size={ "small" }
        ></Button>
    )
  }
}

export default connect(mapStateToProps, mapDispatchToProps)(AnimationControl);