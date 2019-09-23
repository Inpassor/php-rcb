<?php

namespace rcb\helpers;

use \Exception;

class StringHelper
{

    /**
     * @param int $length
     * @return string
     * @throws Exception
     */
    public static function getRandomString($length = 32): string
    {
        if (!is_int($length)) {
            throw new Exception('Parameter ($length) must be an integer');
        }
        if ($length < 1) {
            throw new Exception('Parameter ($length) must be greater than 0');
        }
        $bytes = random_bytes($length);
        return substr(strtr(base64_encode($bytes), '+/', '-_'), 0, $length);
    }


}
