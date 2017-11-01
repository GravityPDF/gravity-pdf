var webpack = require('webpack')
var path = require('path')
const UglifyJSPlugin = require('uglifyjs-webpack-plugin')

var PROD = (process.env.NODE_ENV === 'production')

module.exports = {
  entry: {
    app: './src/assets/js/react/gfpdf-main.js',
  },
  output: {
    path: __dirname + '/dist/assets/js/',
    filename: 'app.bundle.min.js'
  },
  devtool: PROD ? 'source-map' : 'eval-source-map',
  module: {
    loaders: [
      {
        test: /\.js$/,
        include: [
          path.resolve(__dirname, "src/assets"),
          path.resolve(__dirname, "tests/mocha"),
          path.resolve(__dirname, "node_modules/promise-reflect/promise-reflect.js"),
        ],
        loader: 'babel-loader'
      },
      {
        test: /\.json$/,
        loader: 'json-loader'
      }
    ]
  },
  externals: {
    'jquery': 'jQuery',
  },

  plugins: PROD ? [
    new webpack.DefinePlugin({
        'process.env': {
            'NODE_ENV': JSON.stringify('production')
        }
    }),
    new webpack.optimize.ModuleConcatenationPlugin(),
    new UglifyJSPlugin({
      parallel: true,
      sourceMap: true,
      uglifyOptions: {
        output: {
          comments: false
        }
      }
    })
  ] : []
}