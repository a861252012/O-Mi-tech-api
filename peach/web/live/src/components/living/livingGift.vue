<template>
    <div class="living-gift">
        <div class="static-gift-list" v-for="(item, index) in giftIcon">
            <div v-html="item"></div>
        </div>
    </div>
</template>

<script>
    export default {
        name: "living-gift",
        props: {
            giftIcon: Array
        }
    }
</script>