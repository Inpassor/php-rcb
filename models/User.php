<?php

namespace rcb\models;

use \rcb\helpers\StringHelper;

/**
 * Class User
 * @package rcb\models
 *
 * @property int $id
 * @property string $accessToken
 * @property string $access_token
 * @property string $access_token_expires
 */
class User extends \rcb\db\postgres\Model
{

    /**
     * @inheritdoc
     */
    public $attributes = [
        'id',
        'access_token',
        'access_token_expires',
    ];

    /**
     * @return string
     * @throws \Exception
     */
    protected function _getRandomToken(): string
    {
        return StringHelper::getRandomString(128);
    }

    /**
     * @param string|null $token
     * @return string
     * @throws \Exception
     */
    protected function _createAccessToken(string $token = null): string
    {
        $this->access_token_expires = date('Y-m-d H:i:s', strtotime('+ ' . $this->app->user->tokenExpirationTime . ' day'));
        return $this->access_token = $token ?: $this->_getRandomToken();
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getAccessToken(): string
    {
        return $this->access_token ?: $this->_createAccessToken();
    }

    /**
     * @param string $token
     * @throws \Exception
     */
    public function setAccessToken(string $token = null): void
    {
        $this->access_token = $this->_createAccessToken($token);
    }

    /**
     * @inheritdoc
     */
    public function beforeSave(): void
    {
        if (!$this->accessToken) {
            $this->access_token = $this->_createAccessToken();
        }
    }

    /**
     * @param string $token
     * @return mixed
     * @throws \Exception
     */
    public static function getByToken(string $token)
    {
        return static::find([
            'access_token' => $token,
        ])->with('auth')->one();
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public static function getDemoUser()
    {
        $user = new static([
            'id' => 0,
        ]);
        $user->accessToken = StringHelper::getRandomString(128);
        return $user;
    }

    /**
     * @return \rcb\db\postgres\Relation
     */
    public function getAuth()
    {
        return $this->hasMany(UserAuth::className(), 'user_id');
    }

    /**
     * @return array
     */
    public function getProfileInfo()
    {
        return [
            'id' => $this->_data['id'],
        ];
    }

}
