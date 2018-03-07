<template>
    <div class="middle-section" :style="{'width': winWidth,'height': winHeight}" :start="isStart">
        <div class="video-section" :style="{'width': winWidth,'height': winHeight}">
            <video class="live-video vjs-default-skin" webkit-playsinline playsinline preload="none" width="100%" height="100%" :style="fullScreenStatus">
                <!--<source id="videoSrc" :src="videoSrc" type="application/x-mpegURL">-->
                <source id="videoSrc" :src="videoSrc" type="video/mp4">
            </video>
        </div>
    </div>
</template>

<script>
    export default {
        name: "living-video",
        props: {
            videoSrc: {type: String, default: ''},
            fullScreenStatus: 'transform:scale(1.5)',
            isStart: {type: String, default: 'off'},
            winWidth: '',
            winHeight: ''
        }
    }
</script>