<?php
/**
 * ------------------------------------------------------------------------
 * Web Routes
 * ------------------------------------------------------------------------
 * Here is where you can register web routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * contains the "web" middleware group. Now create something great!
 * 
 * PHP Version 7.3
 * 
 * @category Router
 * @package  CranberryMail
 * @author   Ayus Mohanty <ayus.mohanty@nettantra.net>
 * @license  GNU AGPL-3.0
 * @link     https://cranberrymail.com
 */

Route::get('/', 'HomeController@index');

Route::get('/home', 'HomeController@index')->name('home');

