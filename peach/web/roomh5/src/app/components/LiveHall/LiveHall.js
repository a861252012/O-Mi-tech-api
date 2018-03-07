/**
 * @description:sider部分 直播大厅 弹窗具体数据
 * @author：Merci
 * @Date：2017/2/22
*/
import React,{ Component } from "react";
import Slider from "../Common/Slider/Slider.js";
import IconUser from "../../components/Common/IconUser/IconUser.js";

import {bindActionCreators} from "redux";
import {connect} from "react-redux";

import styles from "./liveHall.css";

const mapStateToProps=(state)=>{
    return {
        liveHallList: state.siderLiveHall,
    }
}

class LiveHall extends Component{
    constructor(props){
        super(props);
        this.state={
            sliderIndex:0,
        }
    }

    //添加箭头翻页的方法
    handleSliderIndex(index) {
        this.setState({ sliderIndex: index })
    }

    //组合一个列表数据
    //注意：线上环境version参数已经自带路径
    composeUl(list){
        let listItem = [];
        list.map((item, index)=>{
            let status= item.status===0?"直播中":"休息中";
            let statusStyle= (item.status===0)?styles.liveItemChange:styles.liveItemChangeWhite;

            listItem.push(
                <li className={styles.liveItem} key={ "li" + index }>
                    <a className={styles.liveItemInto} href={"/"+item.roomid+"/h5"} target="_blank">
                        <div className={styles.liveItemImg}>
                            <img src={ ( item.version === "" ) ? window.CDN_HOST + "/public/src/img/vzhubo.jpg" : item.version } />
                        </div>
                        <div className={statusStyle}>{ status }</div>
                        <div className={styles.liveItemUser}>
                            <span className={styles.liveItemUserName}>{ item.name }</span>
                            <IconUser type="auchor" lv={ item.lv }></IconUser>
                        </div>
                    </a>
                </li>);
        })
        return (<ul className={styles.liveList} key={ list[0].uid }>{listItem}</ul>)
    }
    getLiveHallList(liveData){
        let currentList = [];
        for(let i=0;i<liveData.length;i+=8){
            currentList.push(
                liveData.slice(i,i+8)
            )
        }
        return currentList;
    }

    render(){

        let liveData = this.props.liveHallList;
        // console.log(liveData)
        if( !liveData){
            return (<div></div>)
        }else{

            return(
                <Slider
                    sliderIndex={ this.state.sliderIndex }
                    rootStyle={{
                        height: 300
                    }}
                    onHandleSliderIndex={ this.handleSliderIndex.bind(this) }
                    arrayLeftClass={styles.arrayLeftStyle}
                    arrayRightClass={styles.arrayRightStyle}
                >
                    {
                        this.getLiveHallList(liveData.items).map((itemList, index)=>{
                            return this.composeUl( itemList );
                        })
                    }
                </Slider>
            )
        }
    }
}
export default connect(mapStateToProps, null)(LiveHall);