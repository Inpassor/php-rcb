<?php

namespace rcb\components;

use \Exception;
use \rcb\helpers\ArrayHelper;

class Oauth extends \rcb\base\BaseObject
{

    /**
     * @var array
     */
    public $providers = [];

    /**
     * @param string $providerId
     * @return bool
     */
    public function hasProvider(string $providerId): bool
    {
        return isset($this->providers[$providerId]);
    }

    /**
     * @return array
     */
    public function getProvidersInfo(): array
    {
        $providers = [];
        foreach ($this->providers as $providerId => $providerData) {
            $providers[] = [
                'id' => $providerId,
                'title' => $providerData['title'],
            ];
        }
        return $providers;
    }

    /**
     * @param string $providerId
     * @param array $fields
     * @return array
     * @throws Exception
     */
    public function normalizeFields(string $providerId, array $fields): array
    {
        if (!$this->hasProvider($providerId)) {
            throw new Exception('Can not find the OAuth provider "' . $providerId . '"');
        }
        if (isset($this->providers[$providerId]['normalizeFieldsMap'])) {
            foreach ($this->providers[$providerId]['normalizeFieldsMap'] as $fieldName => $fieldParam) {
                if (is_callable($fieldParam)) {
                    $fields[$fieldName] = call_user_func($fieldParam, $fields);
                } elseif (isset($fields[$fieldParam])) {
                    $fields[$fieldName] = $fields[$fieldParam];
                }
            }
        }
        if (isset($this->providers[$providerId]['filterFields'])) {
            $fields = ArrayHelper::filterKeys($fields, $this->providers[$providerId]['filterFields']);
        }
        return $fields;
    }

}
