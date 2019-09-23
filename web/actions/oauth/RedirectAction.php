<?php

namespace rcb\web\actions\oauth;

class RedirectAction extends \rcb\web\Action
{

    /**
     * @inheritdoc
     */
    public function run(array $parameters = []): void
    {
        session_start();
        $providerId = $this->app->request->getParam('provider');
        $_SESSION['oauth-providerId'] = $providerId;
        $token = $this->app->request->getParam('token');
        $_SESSION['user-token'] = $token;
        if (!isset($this->app->oauth->providers[$providerId])) {
            $this->app->response->sendBadRequest();
        }
        $provider = $this->app->oauth->providers[$providerId];
        $redirectUrl = $this->app->baseUrl . '/oauth/index';
        if (APP_ENV_PROD) {
            $redirectUrl = $provider['url']['auth'] . '?' . http_build_query([
                    'client_id' => $provider['clientId'],
                    'scope' => 'offline',
                    'redirect_uri' => $redirectUrl,
                    'response_type' => 'code',
                ]);
        }
        $this->app->response->redirect($redirectUrl);
    }

}
