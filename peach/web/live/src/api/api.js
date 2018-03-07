import axios from 'axios'
export default {
    getCategory(data) {
        return axios.get('/videoList', {params: data});
    },
    getUserInfo(data) {
        return axios.get('/indexinfo', {params: data});
    },
    getRoomHls(rid, data) {
        return axios.get('/' + rid + '/h5hls', {params: data});
    },
    getSearchAnchor(value, data) {
        return axios.get('/find?nickname=' + value, {params: data});
    }
}