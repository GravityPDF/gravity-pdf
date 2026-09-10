const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const DependencyExtractionWebpackPlugin = require('@wordpress/dependency-extraction-webpack-plugin');
const { resolve } = require('path');

/**
 * The legacy bundles: React, Redux and everything else is bundled, because they predate WordPress
 * shipping the packages they would otherwise borrow.
 */
const bundled = {
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

/**
 * The Font Manager, on the packages WordPress already serves
 *
 * `wp-scripts` is run with `--webpack-no-externals` for the bundles above, so this compiler brings its own
 * `DependencyExtractionWebpackPlugin` with the defaults on: every `@wordpress/*` import, plus React itself,
 * becomes a `window.wp.*` reference and a script handle in `font-manager.min.asset.php`. It writes to a
 * directory of its own so neither compiler's `output.clean` can race the other's assets.
 */
const fontManager = {
	...defaultConfig,
	entry: {
		'font-manager': './src/assets/js/react/fontManager/index.js',
	},
	output: {
		...defaultConfig.output,
		filename: '[name].min.js',
		path: resolve(process.cwd(), 'build/font-manager'),
		clean: true,
	},
	plugins: [
		/* Everything but the extraction plugin, which this compiler needs with its defaults on rather than off */
		...defaultConfig.plugins.filter(
			(plugin) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new DependencyExtractionWebpackPlugin(),
	],
};

module.exports = [bundled, fontManager];
