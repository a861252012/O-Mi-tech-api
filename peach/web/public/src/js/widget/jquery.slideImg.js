;(function ($) {
    var dirParams = [
        'translate3d(0, -100%, 0)', // Top
        'translate3d(100%, 0, 0)', // Right
        'translate3d(0, 100%, 0)', // Bottom
        'translate3d(-100%, 0, 0)', // Left
    ];

    $.fn.slideImg = function (opts) {

        var slideBlock = opts.slideBlock || '.info',
            w = opts.width || this.width(),
            h = opts.height || this.height();

        this.each(function (k, item) {
            var $item = $(item),
                $info = $item.find(slideBlock);
            $info.css({
                transition: 'all .3s ease',
                transform: 'translate3d(0, 100%, 0)'
            })
                .show();

            $item.hover(function (e) {
                setShow($info, getDirection(e));
            }, function (e) {
                $info.css('transform', dirParams[getDirection(e)]);
            })
        })

        /**
         * 获取鼠标进入的方向
         */
        function getDirection(event) {

            // 将容器中心为坐标原点
            // 计算X,Y的坐标， 如果坐标点的值大于半径，则等比缩小
            // 如果宽大于高，那么圆的半径为宽度，则需要将高度等比缩小，使其在坐标系内
            var x = (event.offsetX - (w / 2)) * (w > h ? (h / w) : 1);
            var y = (event.offsetY - (h / 2)) * (h > w ? (w / h) : 1);
            // 使用atan2计算出弧度值，在 -π 到 π 之间，
            // 根据 弧度＝(角度/180) *PI 计算出角度，
            // 由于atan2是在 -π到 π之间，也就是 -180°到180°之间  需要加上180 变成 0 - 360
            // 除以 90 并四舍五入 使其以45°为分界线，分出四个方向，
            // + 3 如果不加3，则方向为顺序为 0左 1上 2右 3下 
            // 加上之后再 %4 取得的结果为 0上 1右 2下 3左  符合平时的操作习惯
            var direction = Math.round((((Math.atan2(y, x) * (180 / Math.PI)) + 180) / 90) + 3) % 4;
            return direction;
        }


        /**
         * 显示进入动画
         */
        function setShow($el, direction) {
            $el.css({
                'transform': dirParams[direction],
                'transition-duration': '0s',
            });
            setTimeout(function () {
                $el.css({
                    'transform': 'translate3d(0, 0, 0)',
                    'transition-duration': '.3s',
                })
            }, 10);
        }
    }
})(jQuery);
