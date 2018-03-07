/**
 * @description: 视频模块及flash middle ware嵌入
 * @author: Young
 * @date: 2017/2/20
 */


import React, { Component } from "react";
import styles from './video.css';
import swfobject from '../../utils/swfobject.js';
import AnimationControl from '../FlyScreen/AnimationControl/AnimationControl.js';

const MobileStyle = () => {
  if (window.isMobile) {
    return (
        <style type="text/css">
          { '.video-container{' +
              'height: 100%; width: calc((100vh - 80px)/16*9);' +
              'max-height: 820px;' +
              'min-height: 650px;' +
              'min-width: 400px;' +
              'max-width: 462px;' +
            '}'
          }
        </style>
    )
  } else {
    return (
        <style type="text/css">
          {
            '@media (max-width: 1400px){' +
              '.video-container{ height: 420px; width: 560px; }' +
            '}'
          }
        </style>
    )
  }
}

class RoomVideo extends Component {

    constructor(props){
      super(props);
      this.state = {
        flashContent: ""
      }
    }

    componentDidMount(){

      let swfVersionStr = "11.1.0";
      let xiSwfUrlStr = "playerProductInstall.swf";

      let params = {};
      //params.quality = "high";
      params.bgcolor = "#000";
      params.wmode = "opaque";
      //params.wmode="window";
      params.allowScriptAccess = "always";
      params.allowFullScreen = "true";
      params.allowFullScreenInteractive = 'true';

      let attributes = {};
      attributes.id = "videoRoom";
      attributes.name = "videoRoom";
      attributes.align = "middle";

      // let initContent = <div className={ styles.tips }>抱歉, Flash无法显示, 可能是以下情况引起:
      //     <h3 className={ styles.title } >1. 请使用Chrome浏览器已达到最佳的观影体验, Chrome浏览器设置问题</h3>
      //     <p>请在浏览器地址栏输入'chrome://flags/', 然后找到'Run all Flash content when Flash setting is set to "allow"', 点开下拉菜单选择Enabled, 以启用flash</p>
      //     <h3 className={ styles.title } >2. 没有安装flash插件或flash版本过低</h3>
      //     <p><a className={ styles.em } target='_blank' href='http://www.adobe.com/go/getflashplayer'>立即获取最新flash</a> 完成安装后, 请关闭浏览器, 重新进入直播间即可观看.</p>
      //   </div>;
      let initContent = <object id="video" width="100%" height="100%">
          <param name="movie" value=""/>
          <object type="application/x-shockwave-flash" data="" width="100%" height="100%">
              <div className={ styles.shockwave }>
                  <p>
                      <a href="http://www.adobe.com/go/getflashplayer">
                          <img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif"  alt="Get Adobe Flash player"/>
                      </a>
                  </p>
                    <h1>注意：此内容需要Adobe Flash Player 支持。单击此处以解决播放问题。</h1>
              </div>
          </object>
      </object>;

      this.setState(()=>{
        return {
          flashContent: initContent
        }
      });

      document.getElementById("flashContent").innerHTML = initContent;
      swfobject.embedSWF(window._flashVars.httpRes + "VideoRoomHtml.swf", "flashContent", "100%", "100%", swfVersionStr, xiSwfUrlStr, window._flashVars, params, attributes);

    }

    render() {
        return (
            <div className="video-container" >
                <MobileStyle />
                <div id="flashContent">{ this.state.flashContent }</div>
                <AnimationControl></AnimationControl>
            </div>
        )
    }
}

export default RoomVideo;