<template>
    <div class="dialog-alert" v-if="visible">
        <div class="mt-dialog mt-dialog-fullscreen mt-theme-default">
            <div class="mt-dialog-container">
                <span class="mt-dialog-title mt-title">{{title}}</span>
                <div class="mt-dialog-content">{{content}}</div>
                <div class="mt-dialog-actions">
                    <button type="button" class="mt-button mt-primary mt-theme-default">
                        <div class="mt-ripple">
                            <div class="mt-button-content" @click="close()">关闭</div>
                        </div>
                    </button>
                </div>
            </div>
        </div>
        <div class="mt-overlay mt-fixed mt-dialog-overlay"></div>
    </div>
</template>

<script>
    export default {
        name: "dialog-alert",
        props: {
            title: String,
            content: String,
            visible: {
                type: Boolean,
                default: true
            }
        },
        methods: {
            close() {
                this.visible = false;
            }
        }
    }
</script>

<style lang="scss">
    :root {
        --mt-theme-default-primary: #448aff;
        --mt-theme-default-accent: #ff5252;
        --mt-theme-default-theme: "light";
    }
    .mt-dialog {
        box-shadow: 0 11px 15px -7px rgba(0,0,0,.2), 0 24px 38px 3px rgba(0,0,0,.14), 0 9px 46px 8px rgba(0,0,0,.12);
        min-width: 7rem;
        max-width: 80%;
        max-height: 80%;
        margin: auto;
        display: -webkit-box;
        display: flex;
        -webkit-box-orient: vertical;
        -webkit-box-direction: normal;
        flex-flow: column;
        overflow: hidden;
        position: fixed;
        top: 50%;
        left: 50%;
        z-index: 9999;
        border-radius: .2rem;
        -webkit-backface-visibility: hidden;
        backface-visibility: hidden;
        pointer-events: auto;
        -webkit-transform: translate(-50%,-50%);
        transform: translate(-50%,-50%);
        -webkit-transform-origin: center center;
        transform-origin: center center;
        transition: opacity .15s cubic-bezier(.25,.8,.25,1),-webkit-transform .2s cubic-bezier(.25,.8,.25,1);
        transition: opacity .15s cubic-bezier(.25,.8,.25,1),transform .2s cubic-bezier(.25,.8,.25,1);
        transition: opacity .15s cubic-bezier(.25,.8,.25,1),transform .2s cubic-bezier(.25,.8,.25,1),-webkit-transform .2s cubic-bezier(.25,.8,.25,1);
        will-change: opacity,transform,left,top;
    }
    .mt-dialog.mt-theme-default {
        background-color: #fff;
        background-color: var(--mt-theme-default-background, #fff);
        color: rgba(0,0,0,0.87);
        color: var(--mt-theme-default-text-primary-on-background, rgba(0,0,0,0.87));
    }
    .mt-dialog-container {
        display: -webkit-box;
        display: flex;
        -webkit-box-orient: vertical;
        -webkit-box-direction: normal;
        flex-flow: column;
    }
    .mt-dialog-container, .mt-dialog-container .mt-tabs {
        -webkit-box-flex: 1;
        flex: 1;
    }
    .mt-dialog-title {
        margin-bottom: 0.1rem;
        padding: 0.2rem 0.2rem 0;
    }
    .mt-title {
        font-size: .4rem;
        font-weight: 500;
        letter-spacing: .005em;
        line-height: 0.425rem;
    }
    .mt-dialog-content {
        padding: 0.425rem .325rem;
        overflow: auto;
        position: relative;
        font-size: .4rem;
        border-bottom: 1px solid #d8d8d8;
    }
    .mt-dialog-actions {
        min-height: .625rem;
        padding: 0.2rem 0.2rem 0.2rem 0.6rem;
        display: -webkit-box;
        display: flex;
        -webkit-box-align: center;
        align-items: center;
        -webkit-box-pack: end;
        justify-content: flex-end;
        position: relative;
        &:before {
            height: 1px;
            position: absolute;
            top: -1px;
            right: 0;
            left: 0;
            content: " ";
        }
    }
    .mt-button{
        &.mt-theme-default.mt-primary {
            border: none;
            background: none;
            font-size: 0.4rem;
            color: #fa2461;
            margin-right: 2.8625rem;
        }
        &:before {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            z-index: 1;
            opacity: 0;
            transition: .4s cubic-bezier(.4,0,.2,1);
            will-change: background-color,opacity;
            content: " ";
        }
        .mt-ripple {
            padding: 0 8px;
            display: -webkit-box;
            display: flex;
            -webkit-box-pack: center;
            justify-content: center;
            -webkit-box-align: center;
            align-items: center;
            width: 100%;
            height: 100%;
            position: relative;
            z-index: 4;
            overflow: hidden;
            -webkit-mask-image: radial-gradient(circle,#fff 100%,#000 0);
        }
        .mt-button-content {
            position: relative;
            z-index: 2;
        }
    }
    .mt-overlay {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        z-index: 999;
        overflow: hidden;
        background: rgba(0,0,0,.4);
        transition: .35s cubic-bezier(.4,0,.2,1);
        transition-property: opacity;
        will-change: opacity;
        .md-overlay {
            position: fixed;
        }
    }
</style>