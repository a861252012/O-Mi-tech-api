const webpack = require('webpack');
const path = require('path');
const buildPath = path.resolve(__dirname, 'build');
//const nodeModulesPath = path.resolve(__dirname, 'node_modules');
const projectPath = path.resolve(__dirname, '');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const ExtractTextPlugin = require("extract-text-webpack-plugin");
const WebpackCleanupPlugin = require('webpack-cleanup-plugin');
const WebpackShellPlugin = require('webpack-shell-plugin');

console.log("__dirname:" + __dirname);
console.log("projectPath:" + projectPath);
console.log("buildPath:" + buildPath);


const config = {
  entry:{
    app: [path.join(__dirname, '/src/app/app.js')],
    vendor: ['react', 'react-dom', 'redux', 'redux-thunk', 'react-redux']
  },
  // Render source-map file for final build
  devtool: "source-map",
  // output config
  output: {
    path: buildPath, // Path of output file
    filename: 'app-[hash].js', // Name of output file
    publicPath: 'http://s.howsp.com/roomh5/build/' //线上环境html cdn
  },
  context: __dirname,
  plugins: [
    // Define production build to allow React to strip out unnecessary checks
    new webpack.DefinePlugin({
      'process.env':{
        'NODE_ENV': JSON.stringify('production')
      }
    }),
    // uglify the bundle
    new webpack.optimize.UglifyJsPlugin({
      compress: {
        // suppresses warnings, usually from module minification
        warnings: false,
      },
    }),

    new webpack.LoaderOptionsPlugin({
      minimize: true
    }),
    // Allows error warnings but does not stop compiling.
    new webpack.NoEmitOnErrorsPlugin(),

    new WebpackCleanupPlugin({
      exclude: ["images/**/*", "roomh5.html.twig"],
    }),
      //生成js到twig模板
      ///www/peach-front/Vcore/App/View/Room/roomh5.html.twig
    new HtmlWebpackPlugin({
      //title: '蜜桃儿',
      filename: 'roomh5.html.twig',
      template: 'src/template/roomh5.html.twig',
      hash: false, //开启hash
    }),

    // Transfer Files, 将src/www 下的images 拷贝到build
    new CopyWebpackPlugin([
        //copy to build
        {from: 'src/www/images', to: 'images'},
        //copy to view
      //{from: 'build/roomh5.html.twig', to: '../../../Vcore/App/View/Room/roomh5.html.twig'}
    ], {
      context: path.resolve(__dirname, '')
    }),

    new webpack.optimize.CommonsChunkPlugin({
      name:'vendor',
      filename: 'vendor-[hash].js'
    }),

    new webpack.LoaderOptionsPlugin({
      debug: false
    }),

    new ExtractTextPlugin("styles-[hash].css"),
    //new webpack.BannerPlugin({banner: 'Banner 2017.3.2', raw: true, entryOnly: false}

    //编译前或者编译后执行命令
    new WebpackShellPlugin({
      onBuildEnd: ['cp build/roomh5.html.twig /data/www/peach-front/Vcore/App/View/Room']
    })
  ],
  module: {
    rules: [
      {
        test: /\.css$/,
        use: ExtractTextPlugin.extract({
          fallback: "style-loader",
          use: [
              {
                loader: "css-loader",
                options: {
                  modules: true,
                  importLoaders: 1,
                  //url: true,
                  //root: 'www',
                  localIdentName: '[name]--[local]--[hash:base64:5]',
                }
              },
              { loader: "postcss-loader" }]
        })
      },
      {
        test: /\.jsx?$/, // All .js files
        loader: 'babel-loader',
        //include: [path.resolve(projectPath, 'src')],
        exclude: /node_modules/
      },
      {
        test: /\.(png|jpg|gif|svg|eot|ttf|woff|woff2)$/,
        use: [

          {
            loader: 'img-loader',
            options: {
              progressive: true
            }
          },
          //{
          //  loader: 'file-loader',
          //  options: {
          //    outputPath: 'images/',
          //    publicPath: "http://s.bydln.com/roomh5/build/",
          //    name:'[name].[ext]'
          //  }
          //},
          {
            loader:'url-loader',
            options: {
              limit: 10,
              //outputPath: 'images/',
              name:'images/[name]-[hash:5].[ext]'
            }
          },
        ]
      },


      // {
      //   test: /\.html$/,
      //   loader: 'string-replace',
      //   query: {
      //     multiple: [
      //       { search: '$app.js', replace: 'abc' }
      //     ]
      //   }
      // }
    ],
  }
};

module.exports = config;