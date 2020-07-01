<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('v1')->group(function(){

    Route::post('login', 'Api\AuthController@login');
    Route::post('reg', 'Api\AuthController@register');
    Route::post('wizard/emailsettings', 'Api\WizardController@index');
    Route::post('db_check', 'HomeController@db_check');
    Route::post('drop_create_db', 'HomeController@drop_create_db');

    Route::group(['middleware' => ['auth:api', 'cranberryAuth','cors']], function() {
         Route::post('getUser', 'Api\AuthController@getUser');
         Route::post('folders','Api\ImapController@get_folders');
         Route::post('star_emails','Api\ImapController@star_emails');

         Route::post('trash_emails','Api\ImapController@trash_emails');
         Route::post('untrash_emails','Api\ImapController@untrash_emails');

         Route::post('save_draft','Api\ImapController@saveDraft');

         Route::post('spam_emails','Api\ImapController@spam_emails');
         Route::post('unspam_emails','Api\ImapController@unspam_emails');

         Route::post('search_emails','Api\ImapController@search_emails');

         Route::post('emails','Api\ImapController@get_emails');
         Route::post('email', 'Api\ImapController@get_email');
         Route::post("wizard/inviteadmin",'Api\WizardController@inviteAdmin');

         Route::post("smtp/sendEmail",'Api\SmtpController@sendEmail');

         Route::post('logout', 'Api\AuthController@logout');
     });
 });




