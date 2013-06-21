<?php

namespace Marks\Pusher;

class Helpers
{

    public static function getHomeDirectory($uid = null)
    {
        if ($uid === null) $uid = getmyuid();
        $information = posix_getpwuid($uid);
        return $information['dir'];
    }

}
