import React, { Component } from "react";
import styles from "./parking.css";
import Dialog from "../Common/Dialog/Dialog.js";
import {connect} from "react-redux";

const mapStateToProps=(state)=>{
    return {
        userData: state.users.userData,
        parkList: state.users.parkList
    }
}

class RoomParking extends Component{
    constructor(props){
        super(props);
        this.state={
            dialog:{
                park:false
            },
        }
    }

    //dialog 的显示关闭控制
    toggleDialog(key) {
        this.setState({ dialog: { [key]: !this.state.dialog[key] }});
    }

    //点击购买座驾跳转商城
    handleCarShop(){
        window.open("/shop")
    }

    render(){
        let dialogStyles={
            width:"894px",
            height:"130px",
            padding:"0px",
            left:"0px",
            position:"relative",
        }

        let { userData, parkList } = this.props;

        return (
            <div className={ styles.container }>

                <div className={ styles.parkingBtn }
                     onClick={ (e)=>{ this.toggleDialog("park")} }
                >
                    <span className={ styles.parkingBtnArrow }></span>
                    <span className={ styles.parkingNum }>{this.props.parkList.length}</span>
                </div>
                <Dialog dialogStyles={ dialogStyles }
                        theme="red"
                        open={ this.state.dialog.park }
                        onRequestClose={ ()=>{ this.closeDialog("park") } }
                        dialogClose={ false }
                >
                    <div className={ styles.parkingContainer }>
                        <div className={ styles.parkingBuyCar } onClick={()=>{ this.handleCarShop() }}>购买座驾</div>
                        <div className={ styles.parkCarListContainer }>
                        <ul className={ styles.parkCarList }>
                            {
                                parkList.map((key,index)=>{

                                    let userName = userData[key].hidden == 0? userData[key].name: '神秘人';
                                    return(
                                        <li className={ styles.parkingCar } key={"li"+index}>
                                            <div className={ styles.carImg }><img src={window._flashVars.httpRes + "image/gift_material/"+ userData[key].car+".png" }/></div>
                                            <div className={ styles.carUser }>{ userName }</div>
                                        </li>
                                    )
                                })
                            }
                        </ul>
                        </div>
                    </div>
                </Dialog>
            </div>
        )
    }
}
export default connect(mapStateToProps, null)(RoomParking);