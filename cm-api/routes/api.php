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
    Route::post('wizard/migrate', 'Api\WizardController@cranMigrate');
    Route::post('db_check', 'HomeController@db_check');
    Route::post('drop_create_db', 'HomeController@drop_create_db');
    Route::post('change_session_driver', 'HomeController@ChangeSessionDriver');

    Route::group(['middleware' => ['auth:api', 'cranberryAuth','cors']], function() {
         Route::post('getUser', 'Api\AuthController@getUser');
         Route::post('folders','Api\ImapController@getFolders');
         Route::post('star_emails','Api\ImapController@starEmails');

         Route::post('trash_emails','Api\ImapController@trashEmails');
         Route::post('untrash_emails','Api\ImapController@unTrashEmails');

         Route::post('save_draft','Api\ImapController@saveDraft');

         Route::post('spam_emails','Api\ImapController@spamEmails');
         Route::post('unspam_emails','Api\ImapController@unSpamEmails');

         Route::post('search_emails','Api\ImapController@searchEmails');

         Route::post('download_attachment','Api\ImapController@downloadAttachment');

         Route::post('emails','Api\ImapController@getEmails');
         Route::post('email', 'Api\ImapController@getEmail');
         Route::post("wizard/inviteadmin",'Api\WizardController@inviteAdmin');

         Route::post("smtp/sendEmail",'Api\SmtpController@sendEmail');

         Route::post('logout', 'Api\AuthController@logout');
     });
 });




