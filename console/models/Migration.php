<?php

namespace rcb\console\models;

class Migration extends \rcb\db\postgres\Model
{

    /**
     * @inheritdoc
     */
    public $attributes = [
        'version',
        'apply_time',
    ];

}
