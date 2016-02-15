
var gulp  	  = require('gulp'),
	minifyCss = require('gulp-minify-css'),
	uglify    = require('gulp-uglify'),
	rename    = require('gulp-rename');

/* Minify our CSS */
gulp.task('minify', function() {
  return gulp.src(['src/assets/css/*.css', '!src/assets/css/*.min.css'])
    .pipe(minifyCss({compatibility: 'ie8'}))
    .pipe(rename({
        suffix: '.min'
    }))
    .pipe(gulp.dest('src/assets/css/'));
});

/* Minify our JS */
gulp.task('compress', function() {
  return gulp.src(['src/assets/js/*.js', '!src/assets/js/*.min.js'])
    .pipe(uglify())
    .pipe(rename({
        suffix: '.min'
    }))
    .pipe(gulp.dest('src/assets/js/'));
});

gulp.task('default', function() {
    gulp.start('minify', 'compress');
});
