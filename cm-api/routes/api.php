<?php
/**
 * ------------------------------------------------------------------------
 * API Routes
 * ------------------------------------------------------------------------
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group. Enjoy building your API!
 * 
 * PHP Version 7.3
 * 
 * @category Router
 * @package  CranberryMail
 * @author   Ayus Mohanty <ayus.mohanty@nettantra.net>
 * @license  GNU AGPL-3.0
 * @link     https://cranberrymail.com
 */

Route::prefix('v1')->group(
    function () {

        Route::post('login', 'Api\AuthController@login');
        Route::post('reg', 'Api\AuthController@register');
        Route::post('wizard/emailsettings', 'Api\WizardController@index');
        Route::post('wizard/migrate', 'Api\WizardController@cranMigrate');
        Route::post('db_check', 'HomeController@dbCheck');
        Route::post('drop_create_db', 'HomeController@dropCreateDB');
        Route::post('change_session_driver', 'HomeController@changeSessionDriver');

        Route::group(
            ['middleware' => ['auth:api', 'cranberryAuth','cors']],
            function () {
                Route::post('getUser', 'Api\AuthController@getUser');
                Route::post('folders', 'Api\ImapController@getFolders');
                Route::post('star_emails', 'Api\ImapController@starEmails');

                Route::post('trash_emails', 'Api\ImapController@trashEmails');
                Route::post('untrash_emails', 'Api\ImapController@unTrashEmails');

                Route::post('save_draft', 'Api\ImapController@saveDraft');

                Route::post('spam_emails', 'Api\ImapController@spamEmails');
                Route::post('unspam_emails', 'Api\ImapController@unSpamEmails');

                Route::post('search_emails', 'Api\ImapController@searchEmails');

                Route::post(
                    'download_attachment',
                    'Api\ImapController@downloadAttachment'
                );

                Route::post('emails', 'Api\ImapController@getEmails');
                Route::post('email', 'Api\ImapController@getEmail');
                Route::post(
                    "wizard/inviteadmin",
                    'Api\WizardController@inviteAdmin'
                );

                Route::post("smtp/sendEmail", 'Api\SmtpController@sendEmail');

                Route::post('logout', 'Api\AuthController@logout');
            }
        );
    }
);




