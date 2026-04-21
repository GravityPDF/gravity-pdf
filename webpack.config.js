const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const { resolve } = require('path');

module.exports = {
	...defaultConfig,
	entry: {
		'app.bundle': './src/assets/js/react/gfpdf-main.js',
		'gfpdf-entries': './src/assets/js/legacy/gfpdf-entries.js',
		admin: './src/assets/js/admin/bootstrap.js',
	},
	optimization: {
		...defaultConfig.optimization,
		concatenateModules: false,
	},
	output: {
		...defaultConfig.output,
		filename: '[name].min.js',
		path: resolve(process.cwd(), 'build/assets'),
	},
	externals: {
		...defaultConfig.externals,
		jquery: 'jQuery',
	},
};
