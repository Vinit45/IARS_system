<?php
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
/*
Route::get('/', function () {
    return view('welcome');
});
Route::get('/register/admin', 'Auth\RegisterController@showAdminRegisterForm');
Route::post('/register/admin', 'Auth\RegisterController@createAdmin');
*/
// Auth::routes();
Auth::routes(['verify'=>true]);
Route::get('/','PagesController@index');
// Route::get('/home', 'HomeController@index')->name('home');
Route::get('/login/admin', 'Auth\LoginController@showAdminLoginForm');
Route::get('/login/teacher', 'Auth\LoginController@showTeacherLoginForm');
Route::get('/register/teacher', 'Auth\RegisterController@showTeacherRegisterForm');
Route::get('/download', 'ExportController@export');
Route::post('/login/admin', 'Auth\LoginController@adminLogin');
Route::post('/login/teacher', 'Auth\LoginController@teacherLogin');
Route::post('/register/teacher', 'Auth\RegisterController@createTeacher');
Route::post('/save', 'HomeController@store')->middleware('auth');
// Route::view('/home', 'home')->middleware('auth');
Route::view('/admin', 'admin')->middleware('auth:admin');
// Route::view('/teacher', 'teacher')->middleware('auth:teacher');
Route::post('/teacher/password/email', 'Auth\TeacherForgotPasswordController@sendResetLinkEmail');
Route::get('/teacher/password/reset', 'Auth\TeacherForgotPasswordController@showLinkRequestForm');
Route::post('/teacher/password/reset', 'Auth\TeacherResetPasswordController@reset');
Route::get('/teacher/password/reset/{token}', 'Auth\TeacherResetPasswordController@showResetForm');
//Route::get('/home', 'HomeController@index')->name('home');
//Route::get('/teacher', 'TeachersController@index')->middleware('auth:teacher');
Route::group(['prefix' => 'teacher', 'middleware' => 'auth:teacher'], 
function () 
{
    Route::get('checkstatus', 'TeachersController@checkstatus');
    Route::get('test3','TeachersController@showTestThree')->name('test3');
    Route::post('test3','TeachersController@storeTestThree');
    // Route::get('send', 'MailsController@send');
    Route::view('','Teacher.dashboard');
    Route::get('putmarks', 'TeachersController@showClassesSubjects');
    Route::get('password/teacher', 'TeachersController@resetPassword');
    Route::post('status','TeachersController@status');
    Route::get('status','TeachersController@showStatus');
    Route::get('profile', 'ProfileController@index');
    Route::get('updateprofile','ProfileController@indexTeacher');
    Route::patch('updateprofile/{id}', 'ProfileController@update');
    Route::get('studentmarks','TeachersController@showStudents');
    Route::post('studentmarks','TeachersController@createSessionForTeacher');
    Route::post('submitmarks','TeachersController@store');
    Route::post('marks','TeachersController@marks');
}
);
Route::group(['prefix' => 'home', 'middleware' => ['auth']], function () 
{
    Route::view('','auth.home');
    Route::get('application/{id}','HomeController@application')->name('home.application');
    Route::post('application','HomeController@storeApplication');
    Route::get('marks','HomeController@index');
    Route::get('profile', 'ProfileController@indexStudent');
    Route::patch('profile/{id}', 'ProfileController@updateStudent');
}  
);
Route::group(['prefix' => 'admin', 'middleware' => ['auth:admin']], function () 
{
    Route::get('','AdminsController@index');
    Route::get('applications','AdminsController@showApplications');
    Route::get('applications/{id}','AdminsController@Application');
    Route::post('applications/{id}','AdminsController@storeApplication');
}  
);