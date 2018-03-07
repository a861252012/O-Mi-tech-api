/**
 /**
 * @description: 礼物轮播
 * @author: Young
 * Created by young on 2017/3/29.
 */
import React, {Component} from "react";
import IconGift from "../../Common/IconGift/IconGift.js";

import {connect} from 'react-redux';
import {CSSTransitionGroup} from 'react-transition-group';
import styles from './giftSet.css';

const mapStateToProps = (state) => {
  return {
    giftSetList: state.giftCurrentState.giftSetList,
    animationRouteData: state.gifts.animationRouteData,
    animationState: state.animationState
  }
}

class GiftSetCompose extends Component {

  render() {

    const compose = [];
    const animationRouteData = this.props.animationRouteData;
    const gid = this.props.gid;

    animationRouteData.map((item, index) => {
      compose.push(
          <div style={{
            position: "absolute",
            height: 50,
            width: 50,
            top: item.y + "px",
            left: item.x + "px"
          }} key={ item.x + "_" + index }>
            <IconGift iconId={ gid }></IconGift>
          </div>
      );
    });

    return (
        <div>{ compose }</div>
    )
  }
}

class GiftSet extends Component {

  /*返回哈希值前缀*/
  /*用于刷新节点缓存（React机制问题）*/
  setHashPrefix() {
    return Math.random().toString(16).slice(2, 8);
  }

  render() {

    let {animationRouteData, giftSetList, animationState} = this.props;

    const comboStyle = {
      display: "none",
    }

    //循环节点
    let items = [];

    if (animationState) {
      items = giftSetList.map((item, i) => {
        //刷新最后一组的动画
        return (
            <div key={ i } style={ comboStyle }>
              <GiftSetCompose animationRouteData={ animationRouteData[item.gnum] }
                              gid={ item.gid }></GiftSetCompose>
            </div>
        )
      });
    }

    return (
        <div className={ styles.container }>
          <CSSTransitionGroup
              component="div"
              transitionName="animationRoutes"
              transitionAppear={false}
              transitionEnterTimeout={5000}
              transitionLeaveTimeout={300}>
            {items}
          </CSSTransitionGroup>
        </div>
    );
  }
}

export default connect(mapStateToProps, null)(GiftSet);