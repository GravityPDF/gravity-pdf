const gulp = require('gulp')
const wpPot = require('gulp-wp-pot')

/* Generate the latest language files */
gulp.task('language', function () {
  return gulp.src(['src/**/*.php', '*.php'])
    .pipe(wpPot({
      domain: 'gravity-forms-pdf-extended',
      package: 'Gravity PDF'
    }))
    .pipe(gulp.dest('src/assets/languages/gravity-forms-pdf-extended.pot'))
})

gulp.task('default', gulp.series(['language']))
