var CDN = typeof CDN_HOST == "undefined" ? false : CDN_HOST;
//cdn 数组
var cdnPathArr = [
    CDN || '',
    //'http://s.bigpeach52.com',
    //'http://s.mitaoclub52.net'
];

/**
 * 随机切换CDN方法
 * @param  {[type]} arr [cdn数组]
 * @return {[type]}     [返回cdn中的一个值]
 */
var __randomSeedFromArr = function(arr){
    var arrLen = arr.length;
    var randomNum = Math.floor(Math.random()*arrLen);
    return arr[randomNum];
}

//优化目的将ued_config 改为了 Config
var __cdn = __randomSeedFromArr(cdnPathArr);

var Config = {
    publishVersion: "v2017090701",
    subPublishVersion: "1.0",
    resource: typeof crossList == "undefined" ? {}: crossList,
    //language: navigator.language || navigator.browserLanguage,
    cdnPath: __cdn + '/public',
    cdnOrigin: __cdn,
    //cdnPath: '/public',
    imagePath: __cdn + '/public/src/img',
    roomSrcPath: __cdn + '/roomh5/src/www',
    liveMode: '/h5', //直播间播放方式: 旧直播间'', h5直播间'/h5'
    mode: 'online' //dev/online/onlinedev
};