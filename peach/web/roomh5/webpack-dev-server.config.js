const webpack = require('webpack');
const path = require('path');
const nodeModulesPath = path.resolve(__dirname, 'node_modules');
const projectPath = path.resolve(__dirname, '');
const TransferWebpackPlugin = require('transfer-webpack-plugin');

console.log("__dirname:" + __dirname);
console.log("projectPath:" + projectPath);
console.log("nodeModulesPath:" + nodeModulesPath);

module.exports = {
  // Entry points to the project
  entry: [
    'react-hot-loader/patch',
    //'webpack-dev-server/client',
    'webpack/hot/only-dev-server',
    path.join(__dirname, '/src/app/app.js'),
  ],
  // Server Configuration options
  devServer: {
    contentBase: 'src/www', // Relative directory for base of server
    hot: true, // Live-reload
    inline: true,
    port: 3000, // Port Number
    historyApiFallback: true,
    disableHostCheck: true, //host安全设置
    host: '0.0.0.0', // Change to '0.0.0.0' for external facing server
    //proxy: {//开启代理
    //  '/api/*': {
    //    target: "http://www.v2f-1.com",
    //    secure: false
    //  }
    //},
  },
  devtool: 'inline-source-map',
  output: {
    filename: 'app.js',
    path: path.resolve(__dirname, 'src/www'),
    publicPath: '/'
    //publicPath: 'http://www.v2f-1.com:3000/'
  },
  context: path.resolve(__dirname, 'src'),
  plugins: [
    // Enables Hot Modules Replacement
    new webpack.HotModuleReplacementPlugin(),
    // Allows error warnings but does not stop compiling.
    new webpack.NoEmitOnErrorsPlugin(),
    // prints more readable module names in the browser console on HMR updates
    new webpack.NamedModulesPlugin(),
    // Moves files
    new TransferWebpackPlugin([
      {from: 'www'},
    ], path.resolve(__dirname, 'src')),

    new webpack.LoaderOptionsPlugin({
      debug: true,
      options: {
        htmlLoader: {
          whateverProp: true
        }
      }
    })
  ],
  module: {
    rules: [
      {
        test: /\.js$/, // All .js files
        //exclude: [nodeModulesPath],
        include: [projectPath+"/src"],
        use: [
          {
            loader: 'babel-loader',
          }
        ], // react-hot is like browser sync and babel loads jsx and es6-7

      },
      {
        test: /\.css$/,
        use:[
          "style-loader",
          {
            loader: "css-loader",
            options: {
              modules: true,
              //importLoaders: 1,
              localIdentName: '[name]--[local]--[hash:base64:5]',
            }
          },
          "postcss-loader"
        ]
      },
      {
        //表情文件夹
        test: /expression\/.+\.png$/i,
        use: [
          //{
          //  loader: "url-loader",
          //  //options: {
          //  //  limit: 10,
          //  //}
          //},
          {
            loader: 'file-loader',
            options: {
              name: '[name].[ext]',
              publicPath: 'http://s.howsp.com/images/expression/',
            }
            //options: {
            //  limit: 1,
            //}
          }
        ]
      },
      {
        //普通文件夹
        test: /images\/.+\.(jpe?g|png|gif|svg)$/i,
        use: [
          //{
          //  loader: "url-loader",
          //  //options: {
          //  //  limit: 10,
          //  //}
          //},
          {
            loader: 'file-loader',
            options: {
              name: '[name].[ext]',
              publicPath: 'http://s.howsp.com/images/',
            }
            //options: {
            //  limit: 1,
            //}
          }
        ]
      },

    ],
  },
};