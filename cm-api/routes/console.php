<?php
/**
 * ------------------------------------------------------------------------
 * Console Routes
 * ------------------------------------------------------------------------
 * This file is where you may define all of your Closure based console
 * commands. Each Closure is bound to a command instance allowing a
 * simple approach to interacting with each command's IO methods.
 * 
 * PHP Version 7.3
 * 
 * @category Router
 * @package  CranberryMail
 * @author   CranberryWare Development Team (NetTantra Technologies) <support@oss.nettantra.com>
 * @license  GNU AGPL-3.0 https://github.com/cranberryware/cranberrymail/blob/master/LICENSE
 * @link     https://github.com/cranberryware/cranberrymail
 */
use Illuminate\Foundation\Inspiring;

Artisan::command(
    'inspire',
    function () {
        $this->comment(Inspiring::quote());
    }
)->describe('Display an inspiring quote');
