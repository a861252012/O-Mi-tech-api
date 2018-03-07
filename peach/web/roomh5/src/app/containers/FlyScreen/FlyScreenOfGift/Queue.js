/**
 * @description 礼物飞屏动画 (理论上png格式的礼物不会走到这个队列, 但逻辑暂时保留)
 * @author Seed
 * @param  actionData : 动画数据
 * @date 2017-3-17
 */
import React, {Component } from "react";
import {bindActionCreators} from 'redux';
import {connect} from "react-redux";
import * as animationActions from '../../../actions/animationActions.js';
import TweenOne from 'rc-tween-one';
import swfobject from '../../../utils/swfobject.js';

import styles from "../FlyScreen.css";

const mapDispatchToProps = ( dispatch ) =>{
    return {
        action:bindActionCreators(animationActions,dispatch)
    }
}

class QueueGift extends Component {

    constructor(props){
        super(props);
        this.state = {
            img:false
        }
    }

    /*返回哈希值前缀*/
    getHashPrefix(){
        return Math.random().toString(16).slice(2,8) + "_" + Math.random().toString(16).slice(2,5);
    }


    //设置flash 依赖插件的参数
    setAnimationConfig(config){
        let param = {};
        param.wmode = "transparent";
        param.allowscriptaccess = "always";
        param.allowfullscreen = true;
        swfobject.embedSWF(window._flashVars.httpRes + "image/gift_material/"+config.code+".swf", "FlyScreenOfGift", "100%", "100%", "11.1.0", "playerProductInstall.swf", window._flashVars, param);
    }

    finalResult(){

        let { animationData = [] } = this.props;

        let animationKey = 0;  //循环计数器

        //挂载异步方法
        let setLoopAnimationConfig = ()=>{

            if(animationData[animationKey] && (typeof animationData[animationKey] == "object")){

                let animeData = animationData[animationKey];
                animationKey++;

                //设置插件参数
                if(animeData){
                    if(animeData.giftType == "swf"){
                        this.setAnimationConfig({code:animeData.contentText})
                    }else if(animeData.giftType == "png"){
                        this.setState({
                            img:<TweenOne style={{
                                     position:'absolute',
                                     width:'70px',
                                     height:'70px',
                                     left:'46%',
                                     top:'45%',
                            }} animation={{left:'23%',duration:animeData.actionTime,ease:'easeInSine'}}><img src={ window._flashVars.httpRes + "image/gift_material/"+animeData.contentText+".png"} className={ styles.FlyScreenOfGiftImg }></img></TweenOne>
                        })
                    }
                }

                //判断还有没有下一条数据..
                if(animationData[animationKey] && (typeof animationData[animationKey] == "object")){

                    //异步递归
                    setTimeout(()=>{
                        setLoopAnimationConfig()
                    },animeData.actionTime)

                }else{
                    //已经是最后一条 (一般来说是 缓存队列未启用的情况....)
                    //异步回调父组件..更改组件渲染状态
                    setTimeout(()=>{
                        return this.props.handleBack(animationKey)
                    },animeData.actionTime)

                }

            }
        }

        setLoopAnimationConfig()


    }

    render(){

        this.finalResult = this.finalResult.bind(this);

        return (
            <span className={ styles.riding }>
                <div id="FlyScreenOfGift">
                    { this.state.img }
                </div>
            </span>
        )
    }

    componentDidMount(){
        this.finalResult()
    }

}

export default connect(null,mapDispatchToProps)(QueueGift);
