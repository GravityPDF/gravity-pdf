var webpack = require('webpack')

var PROD = (process.env.NODE_ENV === 'production')
var vendors = require("./package.json").dependencies

module.exports = {
  entry: {
    app: './src/assets/js/react/gfpdf-main.js',
    vendor: Object.keys(vendors), /* auto-load all dependancies from package.json and include in our vendor bundle */
  },
  output: {
    path: './dist/assets/js/',
    filename: 'app.bundle.min.js'
  },
  devtool: PROD ? 'source-map' : 'eval',
  module: {
    loaders: [
      {
        test: /\.js$/,
        exclude: /(node_modules|bower_components)/,
        loader: 'babel'
      },
      {
        test: /\.json$/,
        loader: 'json'
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
    new webpack.optimize.CommonsChunkPlugin(/* chunkName= */"vendor", /* filename= */"vendor.bundle.min.js"),
    new webpack.optimize.UglifyJsPlugin({
      compress: {
        warnings: false
      }
    })
  ] : [
    new webpack.optimize.CommonsChunkPlugin(/* chunkName= */"vendor", /* filename= */"vendor.bundle.min.js"),
  ]
}