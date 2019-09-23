<?php

namespace rcb\db;

class Model extends \rcb\base\BaseObject
{

    public static function className(): string
    {
        return get_called_class();
    }

}
