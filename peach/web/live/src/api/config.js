/**
 * Created by zeal on 2017/7/19.
 */
import axios from 'axios'

const configPath = '/api';
window.configPath = configPath;

export default {
    get (url, data = {}) {
        //url = configPath + url;
        return new Promise((resolve, reject) => {
            axios.get(url, {
                params: data,
                credentials: true
            }).then(response => {
                resolve(response.data);
            }, error => {
                reject(error);
            }).catch((error) => {
                reject(error);
            })
        })
    },
    post(url, data = {}) {
        //url = configPath + url;
        return new Promise((resolve, reject) => {
            axios.post(url, {
                params: data
            }).then(response => {
                resolve(response.data);
            }, error => {
                reject(error);
            }).catch((error) => {
                reject(error);
            })
        })
    }
}