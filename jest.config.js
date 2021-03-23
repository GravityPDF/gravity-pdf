module.exports = {
  clearMocks: true,
  collectCoverageFrom: [
    'src/assets/js/react/**/*.{js,jsx}',
    '!src/assets/js/react/api/*.{js,jsx}',
    '!src/assets/js/react/store/*.{js,jsx}',
    '!src/assets/js/react/utilities/versionCompare.{js,jsx}'
  ],
  roots: [
    './tests/js-unit'
  ],
  transform: {
    '^.+\\.js?$': 'babel-jest'
  },
  coverageThreshold: {
    global: {
      branches: 75,
      functions: 75,
      lines: 75,
      statements: 75
    }
  },
  setupFiles: [
    './tests/js-unit/setupTests.js'
  ]
}
