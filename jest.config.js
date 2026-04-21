module.exports = {
	clearMocks: true,
	collectCoverageFrom: [
		'src/assets/js/react/**/*.{js,jsx,ts,tsx}',
		'!src/assets/js/react/api/*.{js,jsx,ts,tsx}',
		'!src/assets/js/react/store/*.{js,jsx,ts,tsx}',
		'!src/assets/js/react/utilities/versionCompare.{js,jsx,ts,tsx}',
	],
	roots: [ './tests/js-unit' ],
	transform: {
		'^.+\\.(js|ts|jsx|tsx)?$': 'babel-jest',
	},
	moduleFileExtensions: [ 'ts', 'tsx', 'js', 'jsx', 'json' ],
	coverageThreshold: {
		global: {
			branches: 75,
			functions: 75,
			lines: 75,
			statements: 75,
		},
	},
	setupFilesAfterEnv: [ './tests/js-unit/setupTests.ts' ],
	coverageDirectory: './tmp/jest-coverage',
	testEnvironment: 'jsdom',
	testEnvironmentOptions: {
		customExportConditions: [ 'require', 'node', 'node-addons' ],
	},
};
