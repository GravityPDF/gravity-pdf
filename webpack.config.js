const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const { resolve } = require('path');
const I18nCheckWebpackPlugin = require('@automattic/i18n-check-webpack-plugin');

module.exports = {
	...defaultConfig,
	entry: {
		'app.bundle': './src/assets/js/react/gfpdf-main.ts',
		'gfpdf-entries': './src/assets/js/legacy/gfpdf-entries.ts',
		admin: './src/assets/js/admin/bootstrap.ts',
	},
	optimization: {
		...defaultConfig.optimization,
		concatenateModules: false,
	},
	output: {
		...defaultConfig.output,
		filename: '[name].min.js',
		chunkFilename: '[name].js?ver=[contenthash]',
		path: resolve(process.cwd(), 'build/assets'),
	},
	externals: {
		...defaultConfig.externals,
		jquery: 'jQuery',
	},
	plugins: [
		...defaultConfig.plugins,
		new I18nCheckWebpackPlugin({
			expectDomain: 'gravity-pdf',
		}),
	],
};
