/**
 * @description 图片格式飞屏...
 *  @author seed
 *  @date 2017-3-8
 */
import React, {Component } from "react";
import { connect } from "react-redux";
import { CSSTransitionGroup } from 'react-transition-group';
import styles from "./giftImage.css";

const mapStateToProps = (state) =>{
    return {
        giftImgList: state.giftCurrentState.giftImgList,
        animationState: state.animationState
    }
}

class GiftImage extends Component {

    render(){

        let items = [];

        if(this.props.animationState) {
          items = this.props.giftImgList.map((item, index) => {
            return <img src={ window._flashVars.httpRes + "image/gift_material/" + item.contentText + ".png"}
                        key={ index }></img>
          });
        }

        return (
            <div className={ styles.giftImage } >
                <CSSTransitionGroup
                    component="div"
                    transitionName="moveRL"
                    transitionAppear={false}
                    transitionEnterTimeout={2000}
                    transitionLeave={false} >
                { items }
                </CSSTransitionGroup>
            </div>
        );
    }
}

export default connect( mapStateToProps,null )(GiftImage);