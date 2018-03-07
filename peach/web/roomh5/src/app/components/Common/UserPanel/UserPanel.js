/**
 * @description: 返回一个 <用户名 + 用户等级图标> 的块级元素,并且绑定展开菜单
 * @author: seed
 * @param: userName ：用户名 (必须)  userId：用户id (必须)   vipLv: vip等级(如果缺省则不显示icon )  auchorLv：主播等级(如果缺省则不显示icon)  attachStyle: 主播附加样式
 * @date: 2017-2-16
 */
import React, {Component} from "react";
import {bindActionCreators} from 'redux';
import {connect} from "react-redux";
import * as commonActions from "../../../actions/commonActions.js";
import styles from "./userPanel.css";

//用户图标组件
import IconUser from "../IconUser/IconUser.js";

const mapStateToProps = (state) => {
  return {
    headerUserInfo: state.userInfo,
  }
}

const mapDispatchToProps = (dispatch) => {
  return {
    commonAction: bindActionCreators(commonActions, dispatch),
  }
}

class UserPanel extends Component {

  //获得点击事件的 page X / Y 数值   以及从报文中得到的 uid  . 发送至展开二级菜单组件
  getPanelInfo(event, userInfo) {
    // console.log(this.props.headerUserInfo.ruled)
    if (this.props.headerUserInfo.ruled === -1) {
      return;
    }
    let { pageX, pageY } = event.nativeEvent;
    //设置userpanel显示
    this.props.commonAction.setUserPanelStatus(true);

    //设置user参数
    this.props.commonAction.setUserPanel({
      pageX,
      pageY,
      userId: userInfo.uid,
      userName: userInfo.name
    });
  }

  toRoom(roomid) {
    //目标房间与当前房间是否一致..
    if (roomid == this.props.headerUserInfo.roomid) {
      $.tips("已在当前房间..")
    } else {
      window.location.href = '/' + roomid + '/h5';
    }
  }

  getHashPrefix() {
    return Math.random().toString(16).slice(2, 8) + "_" + Math.random().toString(16).slice(2, 5);
  }

  //这里主要是控制二级展开菜单的绑定
  userNameIcon(type, userInfo, attachStyle) {

    let userStatus = window.OpenMenu;

    if (Number.isInteger(parseInt(userInfo.uid, 10))) {

      //以链接的方式跳转
      if (type == "link") {
        //跑道特殊绑定..
        return <span className={ userStatus !== 0 ? `${styles.identity} ${styles.identityHide}` :  `${styles.identity}`}
                     style={ attachStyle }
                     id="runway"
                     onClick={ () => this.toRoom(userInfo.uid) }
                     key={ this.getHashPrefix() }
                title={ userInfo.name }>{ userInfo.name }</span>
      }

      //神秘人处理
      if(userInfo.hidden){
        return "神秘人";
      }

      //普通面板方式
      return (
        <span className={ userStatus !== 0 ? `${styles.identity} ${styles.identityHide}` :  `${styles.identity}`}
         style={ attachStyle }
         onClick={ (event) => {
           this.getPanelInfo(event, userInfo)
         }}
         key={ this.getHashPrefix() } title={ userInfo.name }>{ userInfo.name }</span>
      )
    }
  }

  //VIP 只要存在就显示
  vipIcon(vipLv) {
    if (vipLv) {
      return <IconUser key={ this.getHashPrefix() } lv={ vipLv } type="vip"></IconUser>
    }
    return "";
  }

  //如果当前用户是主播的话  返回主播icon
  auchorIcon(auchorLv) {
      return <IconUser key={ this.getHashPrefix() } lv={ auchorLv } type="auchor"></IconUser>
  }

  //如果当前用户不是主播的话.. 返回财富等级
  basicIcon(basicLv) {
      return <IconUser key={ this.getHashPrefix() } lv={ basicLv } type="basic"></IconUser>;
  }

  render() {

    let {
      headerUserInfo, //当前登录用户数据
      userInfo = {}, //需要使用vip, lv, uid, name
      //ruled = 0,   // 发送者身份  ： 0 = 是一个普通用户  -1 = 未登录用户  2 = 管理员  3 = 主播, ( 暂时弃用 )
      //auchorLv = 0, // 主播等级 ( 主播等级  财富等级二者只能显示其一 （ 当前用户是主播的话  ..不要显示财富等级 ）  )
      attachStyle = {}, //用户名 .附加样式
      hideIcon = false,
      type = 'panel', //type默认panel(弹出面板), link(跳转)
    } = this.props;

    let userStatus = window.OpenMenu;

    this.getPanelInfo = this.getPanelInfo.bind(this)
    this.toRoom = this.toRoom.bind(this)

    //icon显示控制
    let userIcon = ()=>{
      let userIconArr = [];
      //hideIcon设置图标隐藏, hidden隐藏用户信息
      if(!hideIcon && !userInfo.hidden){
        userIconArr.push(this.vipIcon(userInfo.vip));
        userIconArr.push(headerUserInfo.roomid == userInfo.uid ? this.auchorIcon(userInfo.lv) : "");
        //this.basicIcon(basicLv)
        return userIconArr;
      }
    }

    return (
           <span className={ userStatus !== 0 ? `${styles.container} ${styles.leftPanel}` : styles.container }>
              { this.userNameIcon(type, userInfo, attachStyle) }
                { userIcon() }
              </span>

    )
  }
}
export default connect(mapStateToProps, mapDispatchToProps)(UserPanel);