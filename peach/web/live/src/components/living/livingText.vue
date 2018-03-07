<template>
    <div class="living-text">
        <div class="item" v-for="(item,index) in msg">
            <div :class="[{'item': true}, classNames]">
                <div v-html="item"></div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: "living-text",
        props: {
            msg: Array,
            classNames: {type: String, default: ''}
        }
    }
</script>