var gulp = require('gulp'),
  cleanCSS = require('gulp-clean-css'),
  rename = require('gulp-rename'),
  wpPot = require('gulp-wp-pot'),
  sort = require('gulp-sort')

/* Minify our CSS */
gulp.task('minify', function () {
  return gulp.src([ 'src/assets/css/*.css', '!src/assets/css/*.min.css' ])
    .pipe(cleanCSS())
    .pipe(rename({
      suffix: '.min'
    }))
    .pipe(gulp.dest('src/assets/css/'))
})

/* Generate the latest language files */
gulp.task('language', function () {
  return gulp.src('**/*.php')
    .pipe(sort())
    .pipe(wpPot({
      domain: 'gravity-forms-pdf-extended',
      destFile: 'gravity-forms-pdf-extended.pot',
      package: 'gravity-forms-pdf-extended'
    }))
    .pipe(gulp.dest('src/assets/languages'))
})

gulp.task('default', function () {
  gulp.start('language', 'minify')
})