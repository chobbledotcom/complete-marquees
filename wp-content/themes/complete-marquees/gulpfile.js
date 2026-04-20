var gulp = require('gulp'),
    sass = require('gulp-ruby-sass'),
    cssnano = require('gulp-cssnano'),
    uglify = require('gulp-uglify'),
    rename = require('gulp-rename'),
    concat = require('gulp-concat'),
    sourcemaps = require('gulp-sourcemaps'),
    notify = require('gulp-notify')


//Compile sass!
gulp.task('styles', function() {
  return sass('scss/global.scss', { style: 'expanded' })
    .pipe(gulp.dest('src/scss'))
    .pipe(cssnano())
    //.pipe(sourcemaps.write())
    .pipe(gulp.dest('build/css'))
    .pipe(notify({ message: 'Styles task complete' }));
});


//compile js
gulp.task('scripts', function() {
  return gulp.src('js/**/*.js')
    .pipe(concat('global.js'))
    .pipe(gulp.dest('build/js'))
    .pipe(rename({suffix: '.min'}))
    .pipe(uglify())
    .pipe(gulp.dest('build/js'))
    .pipe(notify({ message: 'Scripts task complete' }));
});

//watch it all
gulp.task('watch', function() {

  // Watch .scss files
  gulp.watch('scss/**/*.scss', ['styles']);

  // Watch .js files
  gulp.watch('js/**/*.js', ['scripts']);

});
;