let gulp = require('gulp'),
  cleanCSS = require('gulp-clean-css'),
  rename = require('gulp-rename'),
  wpPot = require('gulp-wp-pot'),
  watch = require('gulp-watch')

/* Minify our CSS */
gulp.task('minify', function () {
  return gulp.src('src/assets/css/*.css')
    .pipe(cleanCSS({rebaseTo: 'dist/assets/css/'}))
    .pipe(rename({
      suffix: '.min'
    }))
    .pipe(gulp.dest('dist/assets/css/'))
})

/* Generate the latest language files */
gulp.task('language', function () {
  return gulp.src(['src/**/*.php', '*.php'])
    .pipe(wpPot({
      domain: 'gravity-forms-pdf-extended',
      package: 'Gravity PDF'
    }))
    .pipe(gulp.dest('src/assets/languages/gravity-forms-pdf-extended.pot'))
})

gulp.task('watch', function () {
  watch('src/assets/css/*.css', gulp.series('compress'))
})

gulp.task('default', gulp.series(['language', 'minify']))
