const path = require('path')
const { merge } = require('webpack-merge')
const production = require('./webpack-configs/production')
const development = require('./webpack-configs/development')
const MiniCssExtractPlugin = require('mini-css-extract-plugin')
const PROD = process.env.NODE_ENV === 'production'
const modeConfig = PROD ? production : development

module.exports = merge(modeConfig, {
  entry: {
    'app.bundle': './src/assets/js/react/gfpdf-main.js',
    'gfpdf-entries': './src/assets/js/legacy/gfpdf-entries.js',
    'admin': './src/assets/js/admin/bootstrap.js'
  },
  output: {
    path: path.resolve(__dirname, 'dist'),
    filename: 'assets/js/[name].min.js',
    chunkFilename: 'assets/js/chunk-[name].[contenthash].js',
    assetModuleFilename: 'assets/js/[hash][ext][query]'
  },
  module: {
    rules: [
      {
        test: /\.m?js$/,
        exclude: /(node_modules)/,
        use: {
          loader: 'babel-loader',
          options: { babelrc: true }
        }
      },
      {
        test: /\.s[ac]ss$/i,
        use: [
          MiniCssExtractPlugin.loader,
          'css-loader',
          {
            loader: 'sass-loader',
            options: {
              implementation: require('sass')
            }
          }
        ]
      },
      {
        test: /\.(png|jpe?g|gif|svg)$/i,
        // More information here https://webpack.js.org/guides/asset-modules/
        type: 'asset/resource'
      }
    ]
  },
  externals: { jquery: 'jQuery' }
})
