const webpackMerge = require('webpack-merge')
const MiniCssExtractPlugin = require('mini-css-extract-plugin')
const production = require('./webpack-configs/production')
const development = require('./webpack-configs/development')
const PROD = process.env.NODE_ENV === 'production'
const modeConfig = PROD ? production : development

module.exports = webpackMerge(
  {
    entry: {
      'app.bundle': './src/assets/js/react/gfpdf-main.js',
      'gfpdf-backbone': './src/assets/js/legacy/gfpdf-backbone.js',
      'gfpdf-entries': './src/assets/js/legacy/gfpdf-entries.js',
      'gfpdf-migration': './src/assets/js/legacy/gfpdf-migration.js',
      'admin': './src/assets/js/admin/bootstrap.js'
    },
    output: {
      path: __dirname + '/dist/',
      filename: 'assets/js/[name].min.js',
      chunkFilename: 'assets/js/chunk-[name].[contenthash].js',
      publicPath: __dirname + '/dist/'
    },
    module: {
      rules: [
        {
          test: /\.m?js$/,
          exclude: /(node_modules|bower_components)/,
          loader: 'babel-loader',
          options: { babelrc: true }
        },
        {
          type: 'javascript/auto',
          test: /\.json$/,
          loader: 'json-loader'
        },
        {
          test: /\.s[ac]ss$/i,
          use: [
            MiniCssExtractPlugin.loader,
            'css-loader',
            {
              loader: 'sass-loader',
              options: {
                implementation: require('sass'),
                sassOptions: { fiber: require('fibers') }
              }
            }
          ]
        },
        {
          test: /\.(jp(e?)g|png|svg|gif)$/,
          use: [
            {
              loader: 'url-loader',
              options: {
                limit: 8192,
                name: 'assets/images/[name].[ext]',
                publicPath: '../../dist/'
              }
            }
          ]
        }
      ]
    },
    externals: { jquery: 'jQuery' }
  },
  modeConfig
)
