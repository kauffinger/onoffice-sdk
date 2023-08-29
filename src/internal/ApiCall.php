<?php

namespace onOffice\SDK\internal;

use onOffice\SDK\Cache\onOfficeSDKCache;
use onOffice\SDK\Exception\ApiCallFaultyResponseException;
use onOffice\SDK\Exception\HttpFetchNoResultException;

/**
 * @internal
 */
class ApiCall
{
    /** @var Request[] */
    private $_requestQueue = [];

    /** @var array */
    private $_responses = [];

    /** @var array */
    private $_errors = [];

    /** @var string */
    private $_apiVersion = 'stable';

    /** @var onOfficeSDKCache[] */
    private $_caches = [];

    /** @var string */
    private $_server = null;

    /** @var array */
    private $_curlOptions = [];

    /**
     * @param  string  $actionId
     * @param  string  $resourceId
     * @param  string  $identifier
     * @param  string  $resourceType
     * @param  array  $parameters
     * @return int the request handle
     */
    public function callByRawData($actionId, $resourceId, $identifier, $resourceType, $parameters = [])
    {
        $pApiAction = new ApiAction($actionId, $resourceType, $parameters, $resourceId, $identifier);

        $pRequest = new Request($pApiAction);
        $requestId = $pRequest->getRequestId();
        $this->_requestQueue[$requestId] = $pRequest;

        return $requestId;
    }

    /**
     * @param  string  $token
     * @param  string  $secret
     *
     * @throws HttpFetchNoResultException
     */
    public function sendRequests($token, $secret, HttpFetch $httpFetch = null)
    {
        $this->collectOrGatherRequests($token, $secret, $httpFetch);
    }

    /**
     * @param  string  $token
     * @param  \onOffice\SDK\internal\HttpFetch|null  $httpFetch
     *
     * @throws HttpFetchNoResultException
     */
    private function sendHttpRequests(
        $token,
        array $actions,
        array $actionsOrder,
        HttpFetch $httpFetch = null
    ) {
        if (count($actions) === 0) {
            return;
        }

        $responseHttp = $this->getFromHttp($token, $actions, $httpFetch);

        $result = json_decode($responseHttp, true);

        if (! isset($result['response']['results'])) {
            throw new HttpFetchNoResultException;
        }

        $idsForCache = [];

        foreach ($result['response']['results'] as $requestNumber => $resultHttp) {
            $pRequest = $actionsOrder[$requestNumber];
            $requestId = $pRequest->getRequestId();

            if ($resultHttp['status']['errorcode'] == 0) {
                $this->_responses[$requestId] = new Response($pRequest, $resultHttp);
                $idsForCache[] = $requestId;
            } else {
                $this->_errors[$requestId] = $resultHttp;
            }
        }
        $this->writeCacheForResponses($idsForCache);
    }

    /**
     * @param  string  $token
     * @param  string  $secret
     *
     * @throws HttpFetchNoResultException
     */
    private function collectOrGatherRequests($token, $secret, HttpFetch $httpFetch = null)
    {
        $actions = [];
        $actionsOrder = [];

        foreach ($this->_requestQueue as $requestId => $pRequest) {
            $usedParameters = $pRequest->getApiAction()->getActionParameters();
            $cachedResponse = $this->getFromCache($usedParameters);

            if ($cachedResponse !== null) {
                $this->_responses[$requestId] = new Response($pRequest, $cachedResponse);

                continue;
            }

            $actions[] = $pRequest->createRequest($token, $secret);
            $actionsOrder[] = $pRequest;
        }

        $this->sendHttpRequests($token, $actions, $actionsOrder, $httpFetch);
        $this->_requestQueue = [];
    }

    private function writeCacheForResponses(array $responses)
    {
        if (count($this->_caches) === 0) {
            return;
        }

        $responseObjects = array_intersect_key($this->_responses, array_flip($responses));

        foreach ($responseObjects as $pResponse) {
            /* @var $pResponse Response */
            if ($pResponse->isCacheable()) {
                $responseData = $pResponse->getResponseData();
                $requestParameters = $pResponse->getRequest()->getApiAction()->getActionParameters();
                $this->writeCache(serialize($responseData), $requestParameters);
            }
        }
    }

    /**
     * @param  array  $parameters
     * @return array|null
     */
    private function getFromCache($parameters)
    {
        foreach ($this->_caches as $pCache) {
            $resultCache = $pCache->getHttpResponseByParameterArray($parameters);

            if ($resultCache != null) {
                return unserialize($resultCache);
            }
        }

        return null;
    }

    /**
     * @param  string  $result
     */
    private function writeCache($result, $actionParameters)
    {
        foreach ($this->_caches as $pCache) {
            $pCache->write($actionParameters, $result);
        }
    }

    /**
     * @param  array  $curlOptions
     */
    public function setCurlOptions($curlOptions)
    {
        $this->_curlOptions = $curlOptions;
    }

    /**
     * @param  string  $token
     * @param  array  $action
     * @param  \onOffice\SDK\internal\HttpFetch|null  $httpFetch
     * @return string
     *
     * @throws HttpFetchNoResultException
     */
    private function getFromHttp(
        $token,
        $action,
        HttpFetch $httpFetch = null
    ) {

        $request = [
            'token' => $token,
            'request' => ['actions' => $action],
        ];

        if (null === $httpFetch) {
            $httpFetch = new HttpFetch($this->getApiUrl(), json_encode($request));
            $httpFetch->setCurlOptions($this->_curlOptions);
        }

        $response = $httpFetch->send();

        return $response;
    }

    /**
     * @param  int  $handle
     * @return array
     *
     * @throws ApiCallFaultyResponseException
     */
    public function getResponse($handle)
    {
        if (array_key_exists($handle, $this->_responses)) {
            /* @var $pResponse Response */
            $pResponse = $this->_responses[$handle];

            if (! $pResponse->isValid()) {
                throw new ApiCallFaultyResponseException('Handle: '.$handle);
            }

            unset($this->_responses[$handle]);

            // do not return $pResponse itself
            return $pResponse->getResponseData();
        }

        return [];
    }

    /**
     * @return string
     */
    private function getApiUrl()
    {
        return $this->_server.urlencode($this->_apiVersion).'/api.php';
    }

    /**
     * @param  string  $apiVersion
     */
    public function setApiVersion($apiVersion)
    {
        $this->_apiVersion = $apiVersion;
    }

    /**
     * @param  string  $server
     */
    public function setServer($server)
    {
        $this->_server = $server;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    public function addCache(onOfficeSDKCache $pCache)
    {
        $this->_caches[] = $pCache;
    }

    public function removeCacheInstances()
    {
        $this->_caches = [];
    }
}
