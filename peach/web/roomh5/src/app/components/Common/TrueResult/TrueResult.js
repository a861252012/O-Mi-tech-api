/**
 * @description: 循环数据 , 根据通知类型进行逻辑处理，分发通知气泡。返回一个块级元素
 * @author: seed
 * @param: datas:数据  attachStyle:给列表的附加样式   sonElementStyle:给气泡元素的附加样式  componentName：做特殊处理
 * @date: 2017-2-9
 */

import React, {Component} from "react";
//用户id 控件
import UserPanel from "../UserPanel/UserPanel.js";
//礼物icon组件
import IconGift from "../IconGift/IconGift.js";
//表情icon组件
import Expression from "../Expression/Expression.js";
//icon组件
import IconUser from "../IconUser/IconUser.js";
import Common from "../../../utils/Common.js";
import styles from "./trueResult.css";

import {bindActionCreators} from "redux";
import {connect} from 'react-redux';
import * as commonActions from "../../../actions/commonActions.js";
import * as userActions from "../../../actions/userActions.js";

const mapStateToProps = (state) => {
  return {
    giftList: state.gifts.giftData,
    userInfo: state.userInfo
  }
}

const mapDispatchToProps = (dispatch) => {
  return {
    commonActions: bindActionCreators(commonActions, dispatch),
    userActions: bindActionCreators(userActions, dispatch)
  }
}

class TrueResult extends Component {

  constructor(props) {
    super(props);
    this.renderKey = 0;
  }

  /*信息处理逻辑*/
  /*返回哈希值前缀（专门用于控制key值）*/
  getHashPrefix() {
    let thatKey = this.renderKey;
    this.renderKey = (thatKey + 1);

    return thatKey;
  }

  /* 将聊天内容中的 表情字符替换 */
  expressionReplace(content) {

    let newContentArr = content.match(/\{\/\d{1,2}\}|[^\{\/\}]+/g);
    let newDomArr = [];

    newContentArr.map((contentStr) => {

      if (/\{\/\d{1,2}\}/.test(contentStr)) {
        let expressionCode = contentStr.replace(/[\{|\/|\}]/g, "");
        let expressionItem = <Expression expressionName={ "a" + expressionCode }
                                         key={this.getHashPrefix()}></Expression>;
        newDomArr.push(expressionItem);
      } else {
        newDomArr.push(contentStr);
      }

    });

    return newDomArr;
  }

  /**
   * 打开游戏面板
   */
  openGamePanel() {
    this.props.commonActions.openDialog('game');
  }

  //生成UserPanel需要的信息
  generateUserPanelInfo(item) {
    return {
      name: item.sendName,
      richLv: item.richLv,
      lv: item.lv,
      vip: item.vip,
      uid: item.sendUid,
      hidden: item.sendHidden
    }
  }

  //30001的type = 6的情况下, 字符串切割
  paraCustomMessage(msg) {

    const enterGameBtnStyle = {
      color: '#f8065d',
      cursor: 'pointer',
      margin: '0px 3px',
      verticalAlign: 'middle'
    }

    let giftPanelInfo = this.generateUserPanelInfo(msg);
    let roomId = this.props.userInfo.roomid;
    let that = this;

    //去除方括弧
    let contentArr = msg.content.replace(/\【|\】/g, '').split('||');
    let contentCus = [];
    let i = 0;
    for (i; i < contentArr.length; i++) {
      switch (contentArr[i]) {
        //用户名
        case "@sendUid":
          contentCus.push(<UserPanel hideIcon={ true } userInfo={ giftPanelInfo } key={ this.getHashPrefix() }></UserPanel>);
          break;

          //显示主播等级
        case "@icon":
          contentCus.push(<IconUser key={ this.getHashPrefix() } lv={ msg.lv } type="auchor"></IconUser>);
          break;

          //显示财富等级
        case "@recIcon":
          contentCus.push(<IconUser key={ this.getHashPrefix() } lv={ msg.vip } type="vip"></IconUser>);
          break;

          //特殊链接
        case "@link":

          if(/chatlink_gamre1/.test(msg.recName)){
            //游戏获奖时弹窗
            contentCus.push(<a style={ enterGameBtnStyle } key={ this.getHashPrefix() } onClick={ () => {
              this.openGamePanel()
            }}>立即参与</a>);
          }else{

            //开通贵族
            contentCus.push(<a style={ enterGameBtnStyle } key={ this.getHashPrefix() } onClick={ () => {
              Common.handleBtnVip(roomId, that.props.userActions.sendVIPMessage);
            }}>立即开通贵族</a>);
          }
          break;

        default:
          contentCus.push(contentArr[i]);
          break;
      }
    }

    return contentCus;
  }

  /*信息处理逻辑*/
  render() {

    this.getHashPrefix = this.getHashPrefix.bind(this);

    const inlineMiddle = {
      display: "inline-block",
      verticalAlign: "middle"
    }

    const inlineBox = {
      display: "inline",
      verticalAlign: "middle"
    }

    let lists = new Array;

    let {datas = [], attachStyle = {}, sonElementStyle = {}, componentName = false} = this.props;

    //验证 props.datas 是否为数组
    if (!Array.isArray(datas)) {

      console.info('传入的并不是一个纯数组');
      return <div></div>;

    } else {
      datas.map((item) => {

        //如果数据格式错误,跳过循环
        if (item === undefined) {
          return "";
        }

        let doms = new Array();
        let listsClass = "defaultMsg";

        if (Number.isInteger(item.cmd)) {
          if (item.cmd == 10003) {
            //房间公告
            // let {
            //     url="",    //公告链接
            //     content="" //公告内容
            // } = item;
            listsClass = "notice";
            doms.push(<span style={ inlineMiddle } className={ styles.shortTitle }
                            key={ this.getHashPrefix() }>[房间公告]</span>);
            doms.push(<span style={ inlineBox } key={ this.getHashPrefix() }>{ item.content }</span>);
          }

          else if (item.cmd == 11002) {
            //用户进入消息通知
            // let  {
            //     //isAuchor = false,
            //     vip=0,               // vip 等级
            //     name="",          // 发送者的名字
            //     uid=0,            // 发送者id
            //     richLv=0,         // 用户等级 (int)
            //     //sex=0,             // 性别
            //     //guests=0,
            //     ruled=0,       // 发送者身份  ： 0 = 是一个普通用户  -1 = 未登录用户  2 = 管理员  3 = 主播,
            //     car=0,             // 发言者是否携带坐骑,  0 / 其他
            //     //total=0,
            //     //hidden=0,
            //     //icon=0,             // Icon 代码
            //     lv = 0,           // 主播经验值 (int)
            //     } = item;

            if (item.ruled >= 0) {

              doms.push(<span style={ inlineMiddle } className={ styles.shortTitle }
                              key={ this.getHashPrefix() }>[欢迎]</span>);
              doms.push(<UserPanel userInfo={ item } key={ this.getHashPrefix() }></UserPanel>);

              //判断是否存在坐骑
              if (item.car) {
                doms.push(<span style={ inlineBox } key={ this.getHashPrefix() }>开着他的座驾</span>);
                doms.push(<IconGift iconId={ item.car } iconSize="small" iconStyle={{margin: "0 5px"}}
                                    key={ this.getHashPrefix() }></IconGift>);
                doms.push(<span style={ inlineBox } key={ this.getHashPrefix() }>进入房间</span>);
              } else {
                doms.push(<span style={ inlineBox } key={ this.getHashPrefix() }>进入房间</span>)
              }
            }
          }

          else if (item.cmd == 30001) {
            //基本聊天
            // let {
            //   recIcon = 0,        // 接收者是否是贵族, 0 / 其他
            //   vip = 0,            // vip 等级 （未使用）
            //   date = "",           // 时间字符串
            //   sendHidden = 0,// 发送者是否是神秘人  0 / 1
            //   richLv = 0,         // 用户等级 (int)
            //   recHidden = 0,   // 接收者是否是神秘人,0  / 1
            //   recUid = 0,      // 接收者的ID
            //   car = 0,             // 发言者是否携带坐骑,  0 / 其他
            //   recName = "",  // 接收者的名字
            //   sendName = "", // 发送者的名字
            //   type = -1,       // 消息类型
            //   content = "",    // 消息内容
            //   icon = 0,          // Icon 代码
            //   recLv = 0,        // 接收者的主播等级
            //   lv = 0,            // 主播经验值 (int)
            //   sendUid = 0    // 发送者的ID
            // } = item;

            /*   基本聊天
             *    目前存在的通知类型 （type字段）
             -1   全服广播
             0    房间可见
             1    私聊  （> 1.3.2 删除）
             3    系统消息
             5    广播
             6    升级提示,游戏提示匹配 (通用)
             7    开通贵族提示,本房间可见
             8    跑马游戏盈利轮播
             9    飞屏,本房间可见
             10   贵族到期提醒
             11   划拳游戏盈利轮播
             */

            //查找表情符号.. 替换表情icon组件 ..
            let chatContent = this.expressionReplace(item.content);

            //组合聊天数据
            let chatPanelInfo = this.generateUserPanelInfo(item);

            //分发具体逻辑
            switch (item.type) {

              case 0:

                //根据贵族设置字体颜色
                if (item.vip !== 0) {
                  chatContent = <span style={ inlineBox } className={ styles['vipChat' + item.vip] }
                                      key={ this.getHashPrefix() }>{ chatContent }</span>;
                } else {
                  chatContent = <span style={ inlineBox } className={ styles['userChat'] } key={ this.getHashPrefix() }>{ chatContent }</span>;
                }

                //普通聊天
                doms.push(<span style={ inlineMiddle } className={ styles.mtr5px }
                                key={ this.getHashPrefix() }>[{ item.date }]</span>);
                doms.push(<UserPanel userInfo={ chatPanelInfo } key={ this.getHashPrefix() }></UserPanel>)
                doms.push(<span style={ inlineBox } className={  styles.actionName }
                                key={ this.getHashPrefix() }>:</span>)
                doms.push(chatContent);

                break;
              // case 1:
              //   //私聊
                // 私聊已被移除
              //   doms.push(<span style={ inlineMiddle } className={ styles.mtr5px }
              //                   key={ this.getHashPrefix() }>[{ item.date }]</span>);
              //   doms.push(<span style={ inlineMiddle } className={ styles.shortTitle }
              //                   key={ this.getHashPrefix() }>[私聊]</span>);
              //   doms.push(<UserPanel userInfo={ chatPanelInfo } key={ this.getHashPrefix() }></UserPanel>);
              //   doms.push(<span style={ inlineBox } className={  styles.actionName }
              //                   key={ this.getHashPrefix() }>:</span>)
              //   doms.push(chatContent);
              //   break;
              case 3:
                //系统消息
                doms.push(<span style={ inlineMiddle } className={ styles.shortTitle } key={ this.getHashPrefix() }>【系统消息】</span>);
                doms.push(chatContent);
                listsClass = "systemNotice"
                break;
              case 5:
                //广播
                doms.push(<span style={ inlineMiddle } className={ styles.mtr5px }
                                key={ this.getHashPrefix() }>[{ item.date }]</span>);
                doms.push(<span style={ inlineMiddle } className={ styles.shortTitle }
                                key={ this.getHashPrefix() }>[广播]</span>);
                doms.push(<UserPanel userInfo={ chatPanelInfo } key={ this.getHashPrefix() }></UserPanel>);
                doms.push(<span style={ inlineBox } className={  styles.actionName }
                                key={ this.getHashPrefix() }>:</span>)
                doms.push(chatContent);
                break;

                //游戏单独通知到用户
                //隐身人玩游戏不显示消息

              case 6:
                //{"cmd":30001,"vip":0,"recUid":0,"recHidden":0,"car":0,"type":6,"lv":12,"recIcon":0,"sendHidden":0,"sendUid":2650045,"richLv":0,"recLv":1,"date":"2017-06-26 17:39:44","icon":0,
                // "recName":"立即参与,chatlink_gamre1",
                // "content":"[跑马会]【||@recIcon||@sendUid||】在跑马游戏中获得钻石：810钻石 ||@link||",
                // "sendName":"young1"
                //     }
                //     //游戏通知，通知条件，赢取钻石数大于500
                doms.push(this.paraCustomMessage(item));
                break;

              //开通贵族
              case 7:
                //{
                // "recIcon":0,"type":7,"richLv":1,"lv":1,"recName":"",
                // "vip":1106,"recUid":0,"sendHidden":0,"car":0,
                // "sendUid":2653794,
                // "content":"[贵族]：恭喜【||@sendUid||】开通||@recIcon||！",
                // "recLv":1,"recHidden":0,"sendName":"seed","date":"",
                // "cmd":30001,"icon":1106
                // }

                doms.push(this.paraCustomMessage(item));
                break;

                //游戏广播到用户(flash使用)
                //case 8:
                //游戏通知，通知条件，赢取钻石数大于500(roger的flash使用)
                //doms.push(<span style ={ inlineMiddle } className={ styles.shortTitle } key={ this.getHashPrefix() }>[跑马会]</span>);
                //doms.push(content);
                //doms.push(<a style={ enterGameBtnStyle } key={ this.getHashPrefix() } onClick={ ()=>{ this.openGamePanel() } }>立即参与</a>);
                //break;

              case 10:
                //幸运礼物中奖 || 开通贵族提醒
                //{"cmd":30001,"vip":0,"recUid":0,"sendHidden":0,"car":0,"type":10,"lv":0,"recIcon":0,"recHidden":0,"richLv":23,"date":"","icon":1107,"recName":"0",
                // "content":"[幸运礼物中奖]：恭喜【||@sendUid||】中了1等奖1个,总计500钻！！！",
                // "sendUid":2650045,"sendName":"young1","recLv":1}

                  //开通贵族提醒
                //{"cmd":30001,"vip":0,"recUid":0,"sendHidden":0,"car":0,"type":10,"lv":0,"recIcon":0,"recHidden":0,"richLv":23,"date":"","icon":1107,"recName":"请尽快充值保级,chongzhi",
                // "content":"[贵族到期提醒]：您的||@icon||贵族到期日：2017-07-23 10:18:49！！||@link||！",
                // "sendUid":2650045,"sendName":"young1","recLv":1}
                doms.push(this.paraCustomMessage(item));
                break;

              default:
                break;
            }
          }
          else if (item.cmd == 40001 || item.cmd == 40002 || item.cmd == 40003) {
            //普通送礼 & 豪华送礼
            let {
              price = 0,        // 价格
              lv = 0,            // 主播经验值 (int)
              sendHidden = 0,// 发送者是否是神秘人  0 / 1
              richLv = 0,         // 用户等级 (int)
              created = "",   // 创建时间
              recIcon = 0,        // 接收者是否是贵族, 0 / 其他
              gnum = 0,      // 礼物数量
              recHidden = 0,   // 接收者是否是神秘人,0  / 1
              recUid = 0,      // 接收者的ID
              ruled = 0,       // 发送者身份  ： 0 = 是一个普通用户  -1 = 未登录用户  2 = 管理员  3 = 主播,
              goodCategory = 0,
              isInRoom = 0,
              recRuled = 0, // 接收者身份  ： 0 = 是一个普通用户  -1 = 未登录用户  2 = 管理员  3 = 主播
              sendUid = 0,  // 发送者的ID
              recName = "", // 接收者的名字
              gid = 0,      // 礼物 id
              sendName = "",// 发送者的名字
              icon = 0,     // Icon 代码
              recLv = 0,    // 接收者的主播等级
              roomid = 0,   // 房间id
              recRichLv = 0,// 接收者经验值
            } = item;

            //礼物列表名称数据
            let giftPanelInfo = this.generateUserPanelInfo(item);
            let giftPanelRecInfo = {
              name: item.recName,
              //lv: item.recLv,
              richLv: item.recRichLv,
              uid: item.recUid,
              hidden: item.recHidden
            }

            if (componentName != "Header") {
              doms.push(<span style={ inlineMiddle } key={ this.getHashPrefix() }>[礼物]</span>);
              doms.push(<span style={ inlineMiddle } className={ styles.mt5px }
                              key={ this.getHashPrefix() }>{ created }</span>);
              doms.push(<UserPanel userInfo={ giftPanelInfo } key={ this.getHashPrefix() }></UserPanel>)
              doms.push(<span style={ inlineMiddle } className={  styles.actionName }
                              key={ this.getHashPrefix() }>送出{goodCategory == 4 ? "奢华礼物" : ""}</span>)
              doms.push(<IconGift iconId={ gid } iconSize={ "small" } iconStyle={{margin: "0 5px"}}
                                  key={ this.getHashPrefix() }></IconGift>)
              doms.push(<span style={ inlineMiddle } key={ this.getHashPrefix() }>{ gnum }个</span>)
            } else {

              //查询字典 获取礼物名称。 （待修改）
              let giftMSG = this.props.giftList[gid];

              doms.push(<span style={ inlineMiddle } className={ styles.mt5px }
                              key={ this.getHashPrefix() }>[{ created }]</span>);
              doms.push(<UserPanel userInfo={ giftPanelInfo } key={ this.getHashPrefix() }></UserPanel>)
              doms.push(<span style={ inlineMiddle } className={  styles.actionName }
                              key={ this.getHashPrefix() }> 送给主播 </span>);
              doms.push(<UserPanel userInfo={ giftPanelRecInfo } type={ "link" }
                                   key={ this.getHashPrefix() }></UserPanel>)
              if (giftMSG) {
                doms.push(<span style={ inlineMiddle } key={ this.getHashPrefix() }>奢华礼物 {giftMSG.name}</span>)
              }
              doms.push(<IconGift iconId={ gid } iconSize={ "small" } iconStyle={{margin: "0 5px"}}
                                  key={ this.getHashPrefix() }></IconGift>)
              doms.push(<span style={ inlineMiddle } key={ this.getHashPrefix() }>{ gnum }个</span>)

            }
          }

        } else {
          return "";
        }

        //循环结束
        if(doms.length > 0){
          lists.push(<div key={ this.getHashPrefix() } className={ styles[listsClass] }
                          style={ sonElementStyle }>{ doms }</div>);
        }

      })
    }

    //处理完毕   输出内容
    return <div style={attachStyle}>
      {lists}
    </div>
  }
}
export default connect(mapStateToProps, mapDispatchToProps)(TrueResult);