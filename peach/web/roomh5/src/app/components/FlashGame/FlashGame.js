/**
 * @description:sider部分 直播大厅 弹窗具体数据
 * @author：Merci
 * @Date：2017/2/22
 */
import React, {Component} from "react";
import swfobject from "../../utils/swfobject.js";
//import styles from "./flashGame.css";

class FlashGame extends Component {

  componentDidMount() {
    let swfVersionStr = "11.1.0";
    let xiSwfUrlStr = "playerProductInstall.swf";

    let params = {};
    params.quality = "high";
    params.bgcolor = "#000";
    params.wmode = "transparent";
    //params.wmode="window";
    params.allowscriptaccess = "always";
    params.allowfullscreen = "true";

    let attributes = {};
    attributes.id = "gameRoom";
    attributes.name = "gameRoom";
    attributes.align = "middle";

    document.getElementById("flashGame").innerHTML = "flashGame";

    swfobject.embedSWF(window._flashVars.httpRes + "CarGame.swf", "flashGame", "600px", "480px", swfVersionStr, xiSwfUrlStr, window._flashVars, params, attributes);
  }

  render() {
    return (
        <div id="flashGame"></div>
    )
  }
}
export default FlashGame;