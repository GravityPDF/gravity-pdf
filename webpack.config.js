const webpackMerge = require('webpack-merge')
const path = require('path')
const PROD = (process.env.NODE_ENV === 'production')
const production = require('./webpack-configs/production')
const development = require('./webpack-configs/development')
const modeConfig = PROD ? production : development

module.exports = {
  entry: {
    'app.bundle': './src/assets/js/react/gfpdf-main.js',
    'gfpdf-backbone': './src/assets/js/gfpdf-backbone.js',
    'gfpdf-entries': './src/assets/js/gfpdf-entries.js',
    'gfpdf-migration': './src/assets/js/gfpdf-migration.js',
    'admin': './src/assets/js/admin/bootstrap.js'
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
