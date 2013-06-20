<?php

namespace Marks\Pusher;

class Helpers {

    public static function getHomeDirectory($uid = getmyuid())
    {
        $information = posix_getpwuid($uid);
        return $information['dir'];
    }

}
