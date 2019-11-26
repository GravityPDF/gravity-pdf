const webpackMerge = require('webpack-merge')
const path = require('path')
const PROD = (process.env.NODE_ENV === 'production')
const chunkPath = __dirname + '/dist/assets/js/'
const production = require('./webpack-configs/production')
const development = require('./webpack-configs/development')
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
      path: __dirname + '/dist/assets/js/',
      filename: '[name].min.js',
      chunkFilename: 'chunk-[name].[contenthash].js',
      publicPath: chunkPath
    },
    module: {
      rules: [
        {
          test: /\.(js|jsx)$/,
          exclude: path.resolve(__dirname, 'src/assets/js/legacy'),
          include: [
            path.resolve(__dirname, 'src/assets'),
            path.resolve(__dirname, 'tests/js-unit')
          ],
          loader: 'babel-loader',
          options: { babelrc: true }
        },
        {
          type: 'javascript/auto',
          test: /\.json$/,
          loader: 'json-loader'
        }
      ]
    },
    externals: {
      'jquery': 'jQuery'
    }
  },
  modeConfig
)
