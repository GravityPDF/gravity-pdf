const { hasArgInCLI } = require( '@wordpress/scripts/utils' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

const isProduction = defaultConfig.mode === 'production';
const hasReactFastRefresh = hasArgInCLI( '--hot' ) && ! isProduction;

module.exports = ( api ) => {
	api.cache.using( () => process.env.NODE_ENV === 'production' );

	const plugins = [
		'babel-plugin-inline-json-import',
		'@babel/plugin-transform-modules-commonjs',
	];

	if ( hasReactFastRefresh ) {
		plugins.push( 'react-refresh/babel' );
	}

	if ( isProduction ) {
		plugins.push(
			...[
				[
					'react-remove-properties',
					{ properties: [ 'data-test' ] },
				],
				'transform-react-remove-prop-types',
			]
		);
	}

	return {
		presets: [ '@wordpress/babel-preset-default' ],
		plugins,
	};
};
