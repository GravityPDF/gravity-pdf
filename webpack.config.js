const webpackMerge = require('webpack-merge')
const path = require('path')
const PROD = (process.env.NODE_ENV === 'production')
const production = require('./webpack-configs/production')
const development = require('./webpack-configs/development')
const modeConfig = PROD ? production : development

module.exports = webpackMerge(
  {
    entry: {
      'gfpdf-backbone': './src/assets/js/legacy/gfpdf-backbone.js',
      'gfpdf-entries': './src/assets/js/legacy/gfpdf-entries.js',
      'gfpdf-migration': './src/assets/js/legacy/gfpdf-migration.js',
      'gfpdf-settings': './src/assets/js/legacy/gfpdf-settings.js',
      'app.bundle': './src/assets/js/react/gfpdf-main.js'
    },
    output: {
      path: __dirname + '/dist/assets/js/',
      filename: '[name].min.js'
    },
    module: {
      rules: [
        {
          test: /\.js$/,
          exclude: path.resolve(__dirname, 'src/assets/js/legacy'),
          include: [
            path.resolve(__dirname, 'src/assets'),
            path.resolve(__dirname, 'tests/mocha')
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
