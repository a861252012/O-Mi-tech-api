<template>
  <section class="app-main">
    <!--<div class="is-fluid is-marginless app-content">-->
      <!--<transition-->
              <!--mode="out-in"-->
              <!--enter-active-class="fadeIn"-->
              <!--leave-active-class="fadeOut"-->
              <!--appear>-->
        <!--<keep-alive>-->
          <!--<router-view :key="key" class="animated"></router-view>-->
        <!--</keep-alive>-->
      <!--</transition>-->
    <!--</div>-->
    <router-view :key="key"></router-view>
  </section>
</template>
<script>
    export default {
        name: 'appmain',
        computed: {
            key() {
                const _routename = this.$route.name,
                    _route = this.$route,
                    times = new Date().getTime();
                return _routename !== undefined ? _routename + '_' + times : _route + '_' + times;
            }
        }
    }
</script>
