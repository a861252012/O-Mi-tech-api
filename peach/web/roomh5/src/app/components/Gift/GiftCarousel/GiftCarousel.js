/**
 /**
 * @description: 礼物轮播
 * @author: Young
 * Created by young on 2017/3/29.
 */
import React, {Component} from "react";

import {CSSTransitionGroup} from 'react-transition-group';
import styles from './giftCarousel.css';
import GiftCombo from '../GiftCombo/GiftCombo.js';

import {bindActionCreators} from 'redux';
import {connect} from 'react-redux';
import * as giftActions from '../../../actions/giftActions.js';

const mapStateToProps = (state) => {
  return {
    carouselList: state.giftCurrentState.carouselList,
    giftData: state.gifts.giftData,
    animationState: state.animationState
  }
}

const mapDispatchToProps = (dispatch) => {
  return {
    giftActions: bindActionCreators(giftActions, dispatch),
  }
}

class GiftCarousel extends Component {

  handleRemove(i) {
    //let newItems = this.state.items.slice();
    //newItems.splice(i, 1);
    //this.setState({items: newItems});
  }

  render() {

    const comboStyle = {
      display: "none",
    }

    let {giftData, carouselList, animationState} = this.props;

    let items = [];

    //动画开启判断
    if (animationState) {
      items = carouselList.map((item, i) => (
          <div key={i} onClick={() => this.handleRemove(i)} style={ comboStyle }>
            <GiftCombo comboData={ item } giftInfo={ giftData[item.gid] }></GiftCombo>
          </div>
      ));
    }

    return (
        <div className={ styles.carousel }>
          <CSSTransitionGroup
              component="div"
              transitionName="example"
              transitionAppear={false}
              transitionEnterTimeout={4000}
              transitionLeaveTimeout={300}>
            {items}
          </CSSTransitionGroup>
        </div>
    );
  }
}

export default connect(mapStateToProps, mapDispatchToProps)(GiftCarousel);