const webpack = require('webpack')
const path = require('path')
const UglifyJSPlugin = require('uglifyjs-webpack-plugin')
const PROD = (process.env.NODE_ENV === 'production')

module.exports = {
  entry: {
    'app.bundle': './src/assets/js/react/gfpdf-main.js',
    'gfpdf-backbone': './src/assets/js/gfpdf-backbone.js',
    'gfpdf-entries': './src/assets/js/gfpdf-entries.js',
    'gfpdf-migration': './src/assets/js/gfpdf-migration.js',
    'gfpdf-settings': './src/assets/js/gfpdf-settings.js'
  },
  output: {
    path: __dirname + '/dist/assets/js/',
    filename: '[name].min.js'
  },
  mode: PROD ? 'production' : 'development',
  devtool: PROD ? 'source-map' : 'eval-source-map',
  module: {
    rules: [
      {
        test: /\.js$/,
        include: [
          path.resolve(__dirname, "src/assets"),
          path.resolve(__dirname, "tests/mocha"),
        ],
        loader: 'babel-loader',
        options: { babelrc: true }
      },
      {
        type: "javascript/auto",
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
