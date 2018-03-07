module.exports = {
  plugins: [
    require('postcss-smart-import')({ /* ...options */ }),
    require('precss'),
    require('postcss-mixins'),
    require('autoprefixer')({ /* ...options */ })
  ]
}