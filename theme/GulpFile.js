var gulp = require('gulp');
var concat = require('gulp-concat');
var rename = require('gulp-rename');
var sass = require('gulp-sass');
var uglify = require('gulp-uglify');
var cleancss = require('gulp-clean-css');
var imgmin = require('gulp-imagemin');

/**
 * Styles Task
 */
gulp.task('styles',function(){
    return gulp.src(['resources/sass/style.scss'])
        .pipe(sass())
        .pipe(cleancss())
        .pipe(concat('main.css'))
        .pipe(rename({suffix : '.min'}))
        .pipe(gulp.dest('assets/css/'))
});

/**
 * Images Task
 */
gulp.task('images', function () {
    return gulp.src(['resources/images/**/*'])
        .pipe(imgmin())
        .pipe(gulp.dest('assets/images/'));
});

/**
 * Scripts Task
 */
gulp.task('scripts', function(){
    return gulp.src(['resources/js/**/*.js'])
        .pipe(concat('all.min.js'))
        .pipe(uglify())
        .pipe(gulp.dest('assets/js/'))

});

/**
 * Watch Task
 */
gulp.task('watch', function () {
    gulp.watch('resources/sass/**/*.scss', ['styles']);
    gulp.watch('resources/js/*.js', ['scripts']);
});

/**
 * Default Task
 */
gulp.task('default', ['styles','scripts','images','watch']);

