const isProduction = process.env.NODE_ENV === 'production';

module.exports = {
	plugins: [
		require('postcss-import')(),
		require('postcss-nested')(),
		require('autoprefixer')({ grid: true }),
		...(isProduction
			? [
					require('cssnano')({
						preset: ['default', { discardComments: { removeAll: true } }],
					}),
			  ]
			: []),
	],
};
