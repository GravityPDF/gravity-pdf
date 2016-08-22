var webpack = require('webpack')

var PROD = (process.env.NODE_ENV === 'production')

module.exports = {
    entry: './src/assets/js/gfpdf-main.js',
    output: {
        path: './src/assets/js/',
        filename: 'app.webpack.js'
    },
    devtool: PROD ? 'source-map' : 'eval',
    module: {
        loaders: [
            {
                test: /\.js$/,
                exclude: /(node_modules|bower_components)/,
                loader: 'babel',
                query: {
                    plugins: ['transform-runtime']
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
        new webpack.optimize.UglifyJsPlugin({
            compress: {
                warnings: false
            }
        })
    ] : [

    ]
}