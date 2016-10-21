var webpack = require('webpack')

var PROD = (process.env.NODE_ENV === 'production')

module.exports = {
  entry: {
    app: './src/assets/js/gfpdf-main.js',
    vendor: [
      'react',
      'react-dom',
      'react-redux',
      'react-router',
      'redux',
      'redux-watch',
      'reselect',
      'immutable',
      'lodash.debounce',
      'lodash.union'
    ]
  },
  output: {
    path: './src/assets/js/',
    filename: 'app.bundle.js'
  },
  devtool: PROD ? 'source-map' : 'eval',
  module: {
    loaders: [
      {
        test: /\.js$/,
        exclude: /(node_modules|bower_components)/,
        loader: 'babel',
        query: {
          plugins: [ 'transform-runtime' ]
        }
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
    new webpack.optimize.CommonsChunkPlugin(/* chunkName= */"vendor", /* filename= */"vendor.bundle.js"),
    new webpack.optimize.UglifyJsPlugin({
      compress: {
        warnings: false
      }
    })
  ] : [
    new webpack.optimize.CommonsChunkPlugin(/* chunkName= */"vendor", /* filename= */"vendor.bundle.js"),
  ]
}