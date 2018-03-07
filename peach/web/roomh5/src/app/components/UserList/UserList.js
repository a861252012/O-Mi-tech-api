/**
 * @description: sider部分 在线观众 弹窗具体数据
 * @author：Young
 * @Date：2017/2/22
*/
import React,{ Component } from "react";
import Tabs from "../../components/Common/Tabs/Tabs.js";
import IconUser from "../../components/Common/IconUser/IconUser.js";
import UserPanel from "../Common/UserPanel/UserPanel.js";

import {connect} from "react-redux";

import styles from "./userList.css";

const mapStateToProps=(state)=>{
    return {
        userData: state.users.userData,
        onlineList: state.users.onlineList,
        managerList:state.users.managerList,
    }
}

/**
 * 搜索框
 */
class SearchBar extends Component{

    onHandleSearch(){
        let userSearch = this.refs.userSearch.value;
        this.props.onHandleSearch(userSearch);
    }

    render(){
        return (
            <div className={styles.searchBar}>
                <b className={styles.searchIcon}></b>
                <input type="text"
                       placeholder="搜索用户名称"
                       className={styles.searchBox}
                       onKeyUp={ this.onHandleSearch.bind(this) }
                       ref="userSearch" />
            </div>
        )
    }
}

/**
 * 搜索列表
 */
class SearchList extends Component{

    render(){
        const userNameStyle = {
            color: "#333"
        }
        let { dataList, userData } = this.props;
        return (
            <ul className={styles.audence}>
                {
                   dataList.map((key,index)=>{
                        return (
                            <li className={styles.audenceItem} key={ index }>
                                <UserPanel hideIcon={ true } userInfo={ userData[key] } attachStyle={ userNameStyle }></UserPanel>
                                {
                                    (userData[key].vip !== 0)?<IconUser type="vip" lv={userData[key].vip} iconStyle={{ float:"right",marginTop:"9px",marginRight:"20px"}}></IconUser> : ""
                                }
                            </li>
                        )
                    })
                }
            </ul>
        );
    }
}

/**
 * 用户列表
 */
class UserList extends Component{
    constructor(props){
        super(props);
        this.state = {
            tabIndex: 0,
            onlineList:[],
            managerList:[],
            onlineKey:false,
            managerKey:false
        }        
    }

    handleSwitchTab(index){
        this.setState({ tabIndex: index })
    }

    //搜索处理
    handleSearch(searchValue,listKey) {
        let currentList = [];
        let list = this.props[listKey+"List"];

        //如果符合关键词的话，转存为currentList
        for(let item of list){
            let reg = this.props.userData[item].name.indexOf(searchValue);
            if(reg > -1){
                currentList.push(item);
            }
        }

        //转存为state
        if(searchValue===""){
            this.setState({
                [listKey+"Key"]:false,
            });
        }else{
            this.setState({
                [listKey+"List"] : currentList,
                [listKey+"Key"]:true
            });
        }
    }

    render(){
        let arr = [
            {
                key: "userList",
                title: "观众",
                subTitle: this.props.onlineList.length != 0 ? this.props.onlineList.length : 0
            },

            {
                key: "managerList",
                title: "管理员",
                subTitle: this.props.managerList.length != 0 ? this.props.managerList.length : 0
            }
        ];

        let { userData } = this.props;

        //实现数据按VIP等级排序
        let onlineCompareList = this.props.onlineList;
        let list = this.state.onlineKey===false ? onlineCompareList : this.state.onlineList;

        let onlineCompareManagerList = this.props.managerList;
        let managerList = this.state.managerKey ===false ? onlineCompareManagerList : this.state.managerList;

        return(
                <Tabs
                    arr={ arr }
                    onHandleSwitchTab={ this.handleSwitchTab.bind(this) }
                    skinClass="white"
                    tabIndex = { this.state.tabIndex }
                >
                    <div>
                        <SearchBar onHandleSearch={ (searchValue)=>this.handleSearch(searchValue,"online") }></SearchBar>
                        <SearchList dataList={ list } userData={userData} ></SearchList>
                    </div>
                    <div>
                        <SearchBar onHandleSearch={ (searchValue)=>this.handleSearch(searchValue,"manager") }></SearchBar>
                        <SearchList dataList={ managerList } userData={userData} ></SearchList>
                    </div>
                </Tabs>
        )
    }
}
export default connect(mapStateToProps,null)(UserList);