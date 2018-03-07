<template>
    <div class="mod-indicator-wrapper">
        <span class="mod-indicator-spin">
            <div class="mod-spinner-snake"></div>
        </span>
        <slot></slot>
    </div>
</template>
<script>
    export default {
        name: 'laoding'
    }
</script>
<style lang="scss">
    .mod-indicator {
        -webkit-transition: opacity .2s linear;
        transition: opacity .2s linear;
    }

    .mod-indicator-wrapper {
        top: 50%;
        left: 50%;
        position: absolute;
        -webkit-transform: translate(-50%, -50%);
        transform: translate(-50%, -50%);
        border-radius: 5px;
        background: #fff;
        color: #fff;
        box-sizing: border-box;
        text-align: center;
        padding: 20px;
    }

    .mod-indicator-spin {
        display: inline-block;
        text-align: center;
    }

    .mod-spinner-snake {
        -webkit-animation: mod-spinner-rotate .8s infinite linear;
        animation: mod-spinner-rotate .8s infinite linear;
        border: 4px solid transparent;
        border-radius: 50%;
        border-top-color: rgb(244, 37, 97);
        border-left-color: rgb(244, 37, 97);
        border-bottom-color: rgb(244, 37, 97);
        height: 32px;
        width: 32px;
    }

    .mod-indicator-text {
        display: block;
        color: rgb(244, 37, 97);
        text-align: center;
        margin-top: 10px;
        font-size: 16px;
    }

    .mod-indicator-mask {
        /*top: 0;*/
        /*left: 0;*/
        /*position: fixed;*/
        /*width: 100%;*/
        /*height: 100%;*/
        /*opacity: 0;*/
        /*background: transparent;*/
    }

    @-webkit-keyframes mod-spinner-rotate {
        0% {
            -webkit-transform: rotate(0deg);
            transform: rotate(0deg);
        }

        100% {
            -webkit-transform: rotate(360deg);
            transform: rotate(360deg);
        }
    }

    @keyframes mod-spinner-rotate {
        0% {
            -webkit-transform: rotate(0deg);
            transform: rotate(0deg);
        }

        100% {
            -webkit-transform: rotate(360deg);
            transform: rotate(360deg);
        }
    }
</style>