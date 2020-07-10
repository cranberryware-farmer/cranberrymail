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
 * @author   CranberryWare Development Team (NetTantra Technologies) <support@oss.nettantra.com>
 * @license  GNU AGPL-3.0 https://github.com/cranberryware/cranberrymail/blob/master/LICENSE
 * @link     https://github.com/cranberryware/cranberrymail
 */

Broadcast::channel(
    'App.User.{id}',
    function ($user, $id) {
        return (int) $user->id === (int) $id;
    }
);
