<?php
/**
 * ------------------------------------------------------------------------
 * Broadcast Channels
 * ------------------------------------------------------------------------
 * Here you may register all of the event broadcasting channels that your
 * application supports. The given channel authorization callbacks are
 * used to check if an authenticated user can listen to the channel.
 * 
 * PHP Version 7.3
 * 
 * @category Router
 * @package  CranberryMail
 * @author   Ayus Mohanty <ayus.mohanty@nettantra.net>
 * @license  GNU AGPL-3.0
 * @link     https://cranberrymail.com
 */

Broadcast::channel(
    'App.User.{id}',
    function ($user, $id) {
        return (int) $user->id === (int) $id;
    }
);
