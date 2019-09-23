<?php

namespace rcb\web\actions\oauth;

class IndexAction extends \rcb\web\Action
{

    /**
     * @var string
     */
    public $providerId = null;

    /**
     * @var array
     */
    public $provider = [];

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        session_start();
        $this->providerId = $_SESSION['oauth-providerId'];
        if (!$this->providerId || !isset($this->app->oauth->providers[$this->providerId])) {
            $this->app->response->sendBadRequest();
        }
        $this->provider = $this->app->oauth->providers[$this->providerId];
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        $request = $this->app->request;
        $error = $request->getParam('error');
        if ($error) {
            $error_description = $request->getParam('error_description');
            if ($error_description) {
                $error .= ': ' . str_replace('+', ' ', $error_description);
            }
            $this->app->end($error);
        }
        $code = $request->getParam('code');
        if (!$code) {
            $this->app->end('Could not receive the code from ' . $this->providerId);
        }
        return $code;
    }

    public function getToken(): array
    {
        $tokenResult = @file_get_contents($this->provider['url']['token'] . '?' . http_build_query([
                'client_id' => $this->provider['clientId'],
                'client_secret' => $this->provider['clientSecret'],
                'redirect_uri' => $this->app->baseUrl . '/oauth/index',
                'code' => $this->getCode(),
            ]));
        if (!$tokenResult) {
            $this->app->end('Could not receive the token from ' . $this->providerId);
        }
        $tokenResult = json_decode($tokenResult, true);
        if (
            !isset($tokenResult['access_token'])
            || !isset($tokenResult['user_id'])
            || !isset($tokenResult['expires_in'])
        ) {
            $this->app->end('Incorrect token responce from ' . $this->providerId);
        }
        return $tokenResult;
    }

    /**
     * @param string $userId
     * @param string|null $token
     * @return array
     * @throws \Exception
     */
    public function getProfile(string $userId, string $token = null): array
    {
        $queryParams = [
            'user_ids' => $userId,
            'fields' => implode(',', $this->provider['fields']),
            'v' => $this->provider['version'],
        ];
        if ($token) {
            $queryParams['access_token'] = $token;
        }
        $profileResult = @file_get_contents($this->provider['url']['base'] . '/users.get?' . http_build_query($queryParams));
        if (!$profileResult) {
            $this->app->end('Could not receive the user profile info from ' . $this->providerId);
        }
        $profileResult = json_decode($profileResult, true);
        if (
            !isset($profileResult['response'])
            || !is_array($profileResult['response'])
            || count($profileResult['response']) !== 1
        ) {
            $this->app->end('Incorrect profile response from ' . $this->providerId);
        }
        return $this->app->oauth->normalizeFields($this->providerId, $profileResult['response'][0]);
    }

    /**
     * @inheritdoc
     */
    public function run(array $parameters = []): void
    {
        $app = $this->app;
        $user = $app->user;
        if (APP_ENV_PROD) {
            $token = $this->getToken();
            $profile = $this->getProfile($token['user_id'], $token['access_token']);
            if (!$user->getByOauthId($this->providerId, $token['user_id'])) {
                $user->createByOauthId($this->providerId, $token['user_id'], $profile);
            } else {
                if ($user->tokenExpires < APP_BEGIN_TIME) {
                    $user->setToken();
                }
                $user->setData($profile);
                $user->save();
            }
        } else {
            $user->getDemoUser();
        }

        $app->centrifugo->send('user_' . $_SESSION['user-token'], [
            'action' => 'login',
            'centrifugo' => $app->centrifugo->getConnectionParams($user->token),
            'user' => [
                'isGuest' => $user->isGuest,
                'profile' => $user->getProfileInfo(),
            ],
        ]);

        $this->render('oauth/redirect');
    }

}
