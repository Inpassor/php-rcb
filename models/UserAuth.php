<?php

namespace rcb\models;

class UserAuth extends \rcb\db\postgres\Model
{

    /**
     * @inheritdoc
     */
    public $attributes = [
        'id',
        'user_id',
        'source',
        'source_id',
        'token',
        'token_expires',
    ];

    /**
     * @param string $providerId
     * @param string|int $id
     * @return \rcb\db\Model|UserAuth
     */
    public static function getById(string $providerId, $id)
    {
        return static::find([
            'source' => $providerId,
            'source_id' => $id,
        ])->one();
    }

}
