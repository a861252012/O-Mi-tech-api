/**
 * @description 文字飞屏动画
 * @author Seed
 * @param  actionData : 动画数据 
 *                      {
 *                            ......
 *                      },
 *                      {
                              contentText : 需要转交的内容,
                        },

 * @date 2017-3-1
 */
import React, {Component } from "react";
import {bindActionCreators} from 'redux';
import {connect} from "react-redux";
import * as animationActions from '../../../actions/animationActions.js';
import styles from "../FlyScreen.css";

//动画插件
import TweenOne from 'rc-tween-one';

const mapDispatchToProps = ( dispatch ) =>{
      return {
            action: bindActionCreators(animationActions,dispatch)
      }
}

class Queue extends Component {

      constructor(props){

            super(props);
            this.state = {
                  QueueKey:0,
                  temporyQueue:new Array,
                  animeOption:new Object,
                  animeStyle:new Object
            }
      }

      /*组成文字队列*/
      getTextOfQueue(){

            let animationData = this.props.animationData[this.state.QueueKey];

            if(animationData && (typeof animationData == "object")){

                  //拼装这个队列..
                  let QueueOfText = new Array;

                  //当前屏幕尺寸..
                  const windowWidth = document.body.clientWidth;

                  //占位
                  const firstPart = <div className = { styles.onePart } style={{width:windowWidth}} key = { 0 }></div>
                  QueueOfText.push(firstPart);

                  let { contentText, sendName, recUid, recName, sendHidden } = animationData;

                  if(sendHidden){
                        sendName = "神秘人";
                  }else{
                        sendName = <span className={ styles.em }>{ sendName }</span>;
                  }

                  //过滤文字内容（表情）..
                  contentText = contentText.replace(/\{+\/+\d{0,2}\}+/g,"");
                  const roomText = <a href={"/"+ recUid + "/h5"} className={ styles.em }>{ recName }</a>;
                  //夹入内容..
                  QueueOfText.push(
                      <div className= { styles.onePart }  key={ 1 }>
                            { sendName }{" 在 "}{ roomText }{" 的直播间说: " +contentText }
                      </div>
                  )

                  /*<a className ={ styles.oneHref }>
                   来自主播XXX的直播间
                   </a>*/

                  //占位
                  const finalPart = <div className = { styles.onePart } style={{width:windowWidth}} key = { 2 } id="finalPart" ></div>
                  QueueOfText.push(finalPart);

                  //设置JSX
                  this.setState({
                        temporyQueue:QueueOfText,
                        animeOption:new Object,
                        animeStyle:new Object
                  })
            }
      }


      componentWillMount(){
            this.getTextOfQueue()
      }

      render(){

            this.getTextOfQueue = this.getTextOfQueue.bind(this);
            this.setAnimeOfQueue = this.setAnimeOfQueue.bind(this);

            return (
                <div className={ styles.flyScreenOfText }>
                  <TweenOne style={ this.state.animeStyle } animation = { this.state.animeOption } id="FlyScreenOfText" >
                        <nobr>
                        { this.state.temporyQueue }
                        </nobr>
                  </TweenOne>
                </div>
            )
      }

      //获取移动距离(时间)  加入动画插件.. 再次激活组件.
      componentDidMount(){
            this.setAnimeOfQueue();
      }

      componentDidUpdate(){
           if(Object.keys(this.state.animeOption).length == 0 || Object.keys(this.state.animeStyle).length == 0){
                 this.setAnimeOfQueue()
           }
      }

      setAnimeOfQueue(){
            //获得移动距离
            let mobileDistance =  document.getElementById("finalPart").offsetLeft;

            //移动相对时间(移动相对速度)
            let mobileTime = Math.floor(mobileDistance * 12);

            //无视窗口变动,获得当前窗口宽度..
            let screenWidth = this.state.temporyQueue[0].props.style.width;
            mobileDistance = mobileDistance - screenWidth;

            //设置动画用dom 初始状态..
            let animeStyle = {
                  position:"fixed",
                  left:screenWidth
            }

            //设置动画需要的参数
            let animeOption = {
                  left:-mobileDistance+"px",
                  duration:mobileTime,
                  ease:'linear'
            }


            //抽取内容节点.. 删除前后占位节点..
            let AnimeQueue = this.state.temporyQueue[1];

            //设置JSX
            this.setState({
                  animeOption:animeOption,
                  animeStyle:animeStyle,
                  temporyQueue:AnimeQueue
            })

            let thatKey = this.state.QueueKey;
            thatKey++;

            //检查是否有下一条数据.. 如果有的话异步自增渲染key  再次唤醒组件..
            if(this.props.animationData[thatKey] && (typeof this.props.animationData[thatKey] == "object")){

                  setTimeout(()=>{
                        this.setState({
                              QueueKey:thatKey,
                        })
                        this.getTextOfQueue()
                  },mobileTime)

            }else{
            //如果不存在的话回调上层组件.. 切换渲染状态..关闭本组件
                  setTimeout(()=>{
                        this.props.handleBack(true);
                  },mobileTime)
            }

      }

}

export default connect(null,mapDispatchToProps)(Queue);