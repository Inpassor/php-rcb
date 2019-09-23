<?php

namespace rcb\web\auth;

/**
 * Class Bearer
 * @package core\auth
 */
class Bearer extends \rcb\base\BaseObject
{

    /**
     * @param bool $throw
     * @throws \Exception
     */
    public function authenticate(bool $throw): void
    {
        $app = $this->app;
        $authHeader = $app->request->getHeader('Authorization');
        if ($authHeader !== null && preg_match('/^Bearer\s+(.*?)$/', $authHeader, $matches)) {
            if ($app->user->authenticate($matches[1])) {
                return;
            }
        }
        if ($throw) {
            $app->response->sendUnauthorized();
        }
    }

}
