<template>
    <div class="recent-gift">
        <ul>
            <li v-for="(item, index) in recentGift">
                <img :src="'./static/images/' + item.giftIcon" :id="item.giftId">
            </li>
        </ul>
    </div>
</template>

<script>
    export default {
        name: "recent-gift",
        data() {
            return {
                recentGift:[
                    {
                        giftId: 1,
                        giftIcon: '300_h5_1492160499_small.png'
                    },
                    {
                        giftId: 1,
                        giftIcon: '590034_h5_1505554636_small.png'
                    },
                    {
                        giftId: 3,
                        giftIcon: '1000125_h5_1511841878_small.png'
                    }
                ]
            }
        }
    }
</script>