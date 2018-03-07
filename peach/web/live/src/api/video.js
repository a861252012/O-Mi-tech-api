/**
 *   Created by zeal on 2018/1/17
 */

const video = document.querySelector('video');
const wsURL = 'ws://localhost:8327/media';

const mimeCodec = 'video/mp4;codecs="avc1.42E01E,mp4a.40.2"';

if('MediaSource' in window && MediaSource.isTypeSupported(mimeCodec)) {
    let mediaSource = new MediaSource;
    console.log(mediaSource.readyState);
    video.src = URL.createObjectURL(mediaSource);
    mediaSource.addEventListener('sourceopen', sourceOpen);
} else {
    console.error('不支持MIME类型或编解码器', mimeCodec);
}

function sourceOpen() {
    console.log(this.readyState);
    let mediaSource = this;
    let sourceBuffer = mediaSource.addSourceBuffer(mediaSource);
    fetchMedia(wsURL, function(buf) {
        sourceBuffer.addEventListener('updateend', function() {
            mediaSource.endOfStream();
            video.play();
            console.log(mediaSource.readyState);//结束
        })
        sourceBuffer.appendBuffer(buf);
    })
}

function fetchMedia(url, cb) {
    console.log(url);
    ws = new WebSocket();
    ws.binaryType = 'arraybuffer';
    ws.onopen = function () {
        console.log('已发送');
    }
    ws.onmessage = function (evt) {
        cb(evt.data);
    }
    ws.onclose = function () {
        console.log('已关闭');
    }
    ws.onerror = function (evt) {
        console.log(evt.msg);
    }

}