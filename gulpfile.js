var gulp = require('gulp'),
  uglify = require('gulp-uglify'),
  cleanCSS = require('gulp-clean-css'),
  rename = require('gulp-rename'),
  wpPot = require('gulp-wp-pot'),
  sort = require('gulp-sort'),
  watch = require('gulp-watch')

/* Minify our CSS */
gulp.task('minify', function () {
  return gulp.src('src/assets/css/*.css')
    .pipe(cleanCSS({target: 'dist/assets/css'}))
    .pipe(rename({
      suffix: '.min'
    }))
    .pipe(gulp.dest('dist/assets/css/'))
})

/* Minify our non-react JS (handled by webpack) */
gulp.task('compress', function () {
  return gulp.src('src/assets/js/*.js')
    .pipe(uglify())
    .pipe(rename({
      suffix: '.min'
    }))
    .pipe(gulp.dest('dist/assets/js/'))
})

/* Generate the latest language files */
gulp.task('language', function () {
  return gulp.src('**/*.php')
    .pipe(wpPot({
      domain: 'gravity-forms-pdf-extended',
      package: 'Gravity PDF'
    }))
    .pipe(gulp.dest('src/assets/languages/gravity-forms-pdf-extended.pot'))
})

gulp.task('watch', function () {
  watch('src/assets/js/*.js', function () { gulp.start('compress') })
  watch('src/assets/css/*.css', function () { gulp.start('minify') })
})

gulp.task('default', function () {
  gulp.start('language', 'minify', 'compress')
})