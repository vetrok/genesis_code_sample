<?php

namespace App\Books\Interfaces;

use GuzzleHttp\Client;

abstract class AbstractApi
{
    protected $requestUrl = '';
    protected $httpClient;
    protected $nextPageLink = '';
    protected $apiAttributes = [];
    protected $apiRequestMethod = 'GET';
    protected $apiUrl;
    protected $headers = [];
    protected $additionalRequestOptions = [];

    /**
     * When request is failed, we need to repeat it
     * max number of repeats is there
     *
     * @var int
     */
    protected $repeatRequestHowManyTimes = 2;
    /**
     * How many attempts occurred
     *
     * @var int
     */
    protected $repeatRequestCounter = 0;

    /**
     * @param string $arrayResponse
     */
    abstract public function composeNextPageLink($arrayResponse);

    /**
     * Different APIs has different amount of pages - calculate it, based on response
     *
     * @param $response
     * @return int|null
     */
    abstract public function calculateTotalPages($response);

    /**
     * @return string
     */
    public function getNextPageLink()
    {
        return $this->nextPageLink;
    }

    /**
     * @param $nextPageLink
     * @return string
     */
    public function setNextPageLink($nextPageLink)
    {
        $this->nextPageLink = $nextPageLink;
    }

    /**
     * @return string
     */
    public function getApiRequestMethod()
    {
        return $this->apiRequestMethod;
    }

    /**
     * Wrapper around HTTP client
     *
     * @return Client
     */
    public function getHttpClient()
    {
        if (empty($this->httpClient)) {
            $this->httpClient = new Client();
        }

        return $this->httpClient;
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return $this->apiUrl;
    }

    /**
     * Format URL to perform request
     *
     * @return string
     */
    public function getRequestUrl()
    {
        $requestUrl = $this->getApiUrl();
        $allVars = $this->getApiAttributes();
        $queryVars = http_build_query($allVars);
        $fullRequestURL = $requestUrl . '?' . $queryVars;

        return $fullRequestURL;
    }

    /**
     * @param $requestUrl
     */
    public function setRequestUrl($requestUrl)
    {
        $this->requestUrl = $requestUrl;
    }

    /**
     * Api next page, can be performed only af
     *
     * @return bool|mixed
     */
    public function getNextPage()
    {
        $requestUrl = $this->getNextPageLink();

        return $this->getApiResponse($requestUrl);
    }

    /**
     * Retrieve first page from API request
     *
     * @return bool|mixed
     */
    public function getFirstPage()
    {
        $requestUrl = $this->getRequestUrl();

        return $this->getApiResponse($requestUrl);
    }

    /**
     * Send request directly to API
     *
     * @param $requestUrl
     * @return bool|mixed
     */
    public function getApiResponse($requestUrl)
    {
        $client = $this->getHttpClient();
        $apiRequestMethod = $this->getApiRequestMethod();
        $headers = $this->getHeaders();
        $res = $client->request(
            $apiRequestMethod,
            $requestUrl,
            //Merge options with header
            ['headers' => $headers] + $this->getAdditionalRequestOptions()
        );
        $statusCode = $res->getStatusCode();
        if (!$this->isApiResponseOk($statusCode)) {
            //Repeat request
            //Get current request counter
            $currentRequestCounter = $this->getRepeatRequestCounter();
            $howManyRepeatsAvailable = $this->getRepeatRequestHowManyTimes();
            $response = false;
            if ($currentRequestCounter < $howManyRepeatsAvailable) {
                //Increase request counter
                $this->setRepeatRequestCounter($currentRequestCounter + 1);
                $response = $this->getApiResponse($requestUrl);
            } else {
                //If max repeats are reached - reset counter
                $this->setRepeatRequestCounter(0);
            }

            return $response;
        }

        $responseBody = $res->getBody()->getContents();
        $jsonResponse = json_decode($responseBody, true);
        if (empty($jsonResponse)) {

            return false;
        }

        //Response can has next page link, save it
        $this->composeNextPageLink($jsonResponse);

        return $jsonResponse;
    }

    /**
     * Check api response for success
     *
     * @param $statusCode
     * @return bool
     */
    public function isApiResponseOk($statusCode)
    {
        if ($statusCode == '200') {

            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public function getApiAttributes()
    {
        return $this->apiAttributes;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    public function setHeader($headerName, $headerValue)
    {
        $this->headers[$headerName] = $headerValue;
    }

    /**
     * @return int
     */
    public function getRepeatRequestHowManyTimes()
    {
        return $this->repeatRequestHowManyTimes;
    }

    /**
     * @return int
     */
    public function getRepeatRequestCounter()
    {
        return $this->repeatRequestCounter;
    }

    /**
     * @param int $repeatRequestCounter
     */
    public function setRepeatRequestCounter($repeatRequestCounter)
    {
        $this->repeatRequestCounter = $repeatRequestCounter;
    }

    /**
     * @return array
     */
    public function getAdditionalRequestOptions()
    {
        return $this->additionalRequestOptions;
    }

    /**
     * @param array $additionalRequestOptions
     */
    public function setAdditionalRequestOptions($additionalRequestOptions)
    {
        $this->additionalRequestOptions = $additionalRequestOptions;
    }
}