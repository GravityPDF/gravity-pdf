const MiniCssExtractPlugin = require('mini-css-extract-plugin')

module.exports = {
  mode: 'development',
  devtool: 'eval-source-map',
  plugins: [
    new MiniCssExtractPlugin({
      filename: 'assets/css/gfpdf-styles.min.css',
      chunkFilename: '[id].css'
    })
  ]
}
