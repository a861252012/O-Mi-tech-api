<template>
    <header class="navbar-header">
        <div class="nav-wrap-left">
            <a class="back" @click="goBack()"></a>
        </div>
        <span class="nav-header h1">搜索</span>
        <div class="nav-wrap-right">
            <a class="back" @click="goBack()"></a>
        </div>
    </header>
</template>

<script>
    export default {
        name: "navbar-brand",
        methods: {
            goBack() {
                this.$router.go(-1);
            }
        }
    }
</script>

<style lang="scss">
.navbar-header{
    height: 1.01rem;
    color: #fff;
    background: #fa2461;
    display: -webkit-box;
    display: -ms-flexbox;
    position: relative;
    .nav-wrap-left {
        .back {
            display: block;
            width: 1rem;
            height: 1.01rem;
            background: url(../../../static/images/back.png) center center no-repeat;
            background-size: .425rem .425rem;
        }
    }
    .nav-header {
        display: block;
        -webkit-box-flex: 1;
        -ms-flex: 1;
        font-size: .36rem;
        font-weight: 400;
        text-align: center;
        line-height: 1rem;
        margin: 0;
        text-overflow: ellipsis;
        white-space: nowrap;
        overflow: hidden;
    }
    .nav-wrap-right {
        .back {
            display: block;
            width: 1rem;
            height: 1.01rem;
            background: url(../../../static/images/home-1.png) center center no-repeat;
            background-size: .425rem .425rem;
        }
    }
}
</style>