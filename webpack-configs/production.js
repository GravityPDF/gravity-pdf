const webpack = require('webpack')
const MiniCssExtractPlugin = require('mini-css-extract-plugin')
const OptimizeCssAssetsPlugin = require('optimize-css-assets-webpack-plugin')
const TerserPlugin = require('terser-webpack-plugin')

module.exports = {
  mode: 'production',
  devtool: false,
  plugins: [
    new MiniCssExtractPlugin({
      filename: 'css/gfpdf-styles.min.css',
      chunkFilename: '[id].css'
    }),
    new TerserPlugin({
      extractComments: false,
      parallel: true,
      terserOptions: {
        ecma: 6
      }
    }),
    new webpack.SourceMapDevToolPlugin({
      filename: '[name].js.map',
      exclude: ['gfpdf-backbone.min.js', 'gfpdf-entries.min.js', 'gfpdf-migration.min.js']
    })
  ],
  optimization: {
    minimize: true,
    minimizer: [
      new OptimizeCssAssetsPlugin({})
    ]
  }
}
