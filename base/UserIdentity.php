<?php

namespace rcb\base;

use \rcb\models\User;

/**
 * Class UserIdentity
 * @package rcb\base
 *
 * @method array getProfileInfo()
 * @method void setData(array $data)
 * @method bool save()
 *
 * @property User $model
 * @property \rcb\models\UserAuth $oauthModel
 * @property string $token
 * @property int $tokenExpires
 * @property bool $isGuest
 */
class UserIdentity extends \rcb\base\BaseObject
{

    /**
     * @var string
     */
    public $userClass = '\rcb\models\User';

    /**
     * @var string
     */
    public $oauthClass = '\rcb\models\UserAuth';

    /**
     * Access token expiration time, in days.
     * @var int
     */
    public $tokenExpirationTime = 1;

    /**
     * @var User
     */
    protected $_model = null;

    /**
     * @param string $name
     * @param array $params
     * @return mixed
     */
    public function __call(string $name, array $params)
    {
        $model = $this->model;
        return call_user_func_array([$model, $name], $params);
    }

    /**
     * @inheritdoc
     */
    public function __get(string $name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }
        $model = $this->model;
        return $model->$name;
    }

    /**
     * @inheritdoc
     */
    public function __set(string $name, $value): void
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        } else {
            $model = $this->model;
            $model->$name = $value;
        }
    }

    /**
     * @return User
     */
    public function getModel()
    {
        if (!$this->_model) {
            $this->_model = new $this->userClass();
        }
        return $this->_model;
    }

    /**
     * @param User $model
     */
    public function setModel(User $model): void
    {
        $this->_model = $model;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->model->accessToken;
    }

    /**
     * @param string|null $token
     * @throws \Exception
     */
    public function setToken(string $token = null): void
    {
        $this->model->setAccessToken($token);
    }

    /**
     * @return int
     */
    public function getTokenExpires(): int
    {
        return strtotime($this->model->access_token_expires);
    }

    /**
     * @return bool
     */
    public function getIsGuest(): bool
    {
        return $this->model->isNewRecord;
    }

    /**
     * @return User|null
     * @throws \Exception
     */
    public function getDemoUser()
    {
        $this->getModel();
        return ($this->_model = $this->_model::getDemoUser());
    }

    /**
     * @param string $token
     * @return User|null
     * @throws \Exception
     */
    public function getByToken(string $token)
    {
        $this->getModel();
        return ($this->_model = $this->_model::getByToken($token));
    }

    /**
     * @param string $providerId
     * @param string|int $id
     * @return User|null
     * @throws \Exception
     */
    public function getByOauthId(string $providerId, string $id)
    {
        $this->getModel();
        /** @var \rcb\models\UserAuth $oauth */
        $oauth = new $this->oauthClass(); // TODO: remove this magic
        $oauth = $oauth::getById($providerId, $id);
        $this->_model = $oauth ? $this->_model::find([
            'id' => $oauth->user_id,
        ])->with('auth')->one() : null;
        return $this->_model;
    }

    /**
     * @param string $providerId
     * @param string|int $id
     * @param array|null $data
     */
    public function createByOauthId(string $providerId, $id, array $data = null): void
    {
        $this->_model = new $this->userClass();
        if ($data) {
            $this->_model->setData($data);
        }
        $this->_model->save();
        /** @var \rcb\models\UserAuth $oauth */
        $oauth = new $this->oauthClass(); // TODO: remove this magic
        $oauth->setData([
            'user_id' => $this->_model->id,
            'source' => $providerId,
            'source_id' => $id,
        ]);
        $oauth->save();
        $this->_model->auth = [
            $oauth,
        ];
    }

    /**
     * @param string $token
     * @return bool
     * @throws \Exception
     */
    public function authenticate(string $token): bool
    {
        if (!$this->getByToken($token)) {
            return false;
        }
        $tokenExpirationTime = $this->getTokenExpires();
        if ($tokenExpirationTime < APP_BEGIN_TIME) {
            return false;
        }
        if (($tokenExpirationTime + (60 * 60)) < APP_BEGIN_TIME) {
            // TODO: send new centrifugo connection parameters
            $this->setToken();
            $this->save();
        }
        return true;
    }

    /**
     * @return array
     */
    public function getInfo(): array
    {
        return [
            'isGuest' => $this->isGuest,
        ];
    }

}
