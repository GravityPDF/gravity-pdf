let gulp = require('gulp')
let cleanCSS = require('gulp-clean-css')
let rename = require('gulp-rename')
let wpPot = require('gulp-wp-pot')
let watch = require('gulp-watch')

/* Generate the latest language files */
gulp.task('language', function () {
  return gulp.src(['src/**/*.php', '*.php'])
    .pipe(wpPot({
      domain: 'gravity-forms-pdf-extended',
      package: 'Gravity PDF'
    }))
    .pipe(gulp.dest('src/assets/languages/gravity-forms-pdf-extended.pot'))
})

gulp.task('default', gulp.series(['language', 'minify']))
