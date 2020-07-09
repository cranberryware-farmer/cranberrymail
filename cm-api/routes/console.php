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
 * @author   Ayus Mohanty <ayus.mohanty@nettantra.net>
 * @license  GNU AGPL-3.0
 * @link     https://cranberrymail.com
 */
use Illuminate\Foundation\Inspiring;

Artisan::command(
    'inspire',
    function () {
        $this->comment(Inspiring::quote());
    }
)->describe('Display an inspiring quote');
