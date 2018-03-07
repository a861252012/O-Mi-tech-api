/**
 *   Created by zeal on 2017/11/16
 */

import React, { Component } from "react";
import styles from "./footer.css";

class Footer extends Component {

    smartBannerBigClick(){
        $('#' + this.refs.smartBanner.getAttribute('id')).removeClass('smart-banner-shows');
        $('#J_smartBannerSmall').addClass('smart-banner-hide-show');
    }
    smartBannerSmallClick(){
        $('#' + this.refs.smartBanner.getAttribute('id')).addClass('smart-banner-shows');
        $('#J_smartBannerSmall').removeClass('smart-banner-hide-show');
    }

    render() {
        let userStatus = window.OpenMenu;
        return (
            <div>
                { userStatus !== 0 ? "" :
                    <div>
                        <div  id="J_smartBannerBig" className={ styles.smartBannerShow } ref="smartBanner">
                            <div className={ styles.smartBannerContent }>
                                <img id="J_qrCode" className={ styles.qr }/>
                                <a href="/download" target="_blank" className={ styles.smartBannerButton }>APP下载</a>
                                <button className={styles.smartBannerClose} onClick={ this.smartBannerBigClick.bind(this) }>×</button>
                            </div>
                        </div>
                        <div id="J_smartBannerSmall" className={ styles.smartBannerHide } onClick={ this.smartBannerSmallClick.bind(this) }>
                            <div className={ styles.smartBannerImg}></div>
                        </div>
                    </div>
                }
            </div>
        )
    }
}

export default Footer;