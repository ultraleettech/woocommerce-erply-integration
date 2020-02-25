<?php

namespace Ultraleet\WcErply\Components;

use Exception;

class ErplyApi extends AbstractComponent
{
    /**
     * @var \EAPI
     */
    private $EAPI;

    /**
     * @param string $name
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function request(string $name, array $params = []): array
    {
        if (! isset($this->EAPI)) {
            $this->initApi();
        }
        $this->logger->debug("API request: $name", $params);
        $response = json_decode($this->EAPI->sendRequest($name, $params), true);
        $this->handleError($response);
        return $response;
    }

    /**
     * Perform a test API query.
     *
     * @return bool
     */
    public function test(): bool
    {
        delete_option('wcerply_api_error');
        try {
            $this->request('getProducts', [
                'recordsOnPage' => 1,
            ]);
        } catch (Exception $exception) {
            add_option('wcerply_api_error', [
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ]);
            return false;
        }
        return true;
    }

    private function initApi()
    {
        session_start();

        // Initialise class
        $this->EAPI = new \EAPI();

        // Configuration settings
        $settings = $this->settings;
        $this->EAPI->clientCode = $settings->getSettingValue('customer_code', 'api', 'general');
        $this->EAPI->username = $settings->getSettingValue('username', 'api', 'general');
        $this->EAPI->password = $settings->getSettingValue('password', 'api', 'general');
        $this->EAPI->url = "https://".$this->EAPI->clientCode.".erply.com/api/";
    }

    /**
     * @param array $response
     * @throws \Exception
     */
    private function handleError(array $response)
    {
        if (! isset($response['status']['responseStatus'])) {
            throw new Exception('Malformed Erply API response');
        } elseif ($response['status']['responseStatus'] == 'error') {
            $code = $response['status']['errorCode'];
            $field = $response['status']['errorField'] ?? '';
            if ($field) {
                $message = sprintf("Erply API returned error %d (field: %s)", $code, $field);
            } else {
                $message = sprintf("Erply API returned error %d", $code);
            }
            throw new Exception($message, $code);
        }
    }
}
