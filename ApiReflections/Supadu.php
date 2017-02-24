<?php

namespace App\Books\ApiReflections;


class Supadu extends \App\Books\Interfaces\AbstractApi
{

    protected $apiUrl = 'http://macmillan-folioservices.supadu.com/search';
    protected $apiSingleBookUrl = 'http://macmillan-folioservices.supadu.com/book/';

    /**
     * Supadu constructor.
     * Set auth header
     */
    public function __construct()
    {
        //Set Auth header for API
        $token = env('SUPADU_API_AUTH_TOKEN');
        $this->setHeader('x-apikey', $token);
        $this->setHeader('Accept-Encoding', 'gzip');
        $this->apiAttributes['category_data'] = 1;
    }

    /**
     * @return array
     */
    public function getAuthHeader()
    {
        return $this->getHeaders();
    }

    /**
     * @param $keyword
     */
    public function setKeyword($keyword)
    {
        $this->apiAttributes['keyword'] = $keyword;
    }

    /**
     * @param $page
     */
    public function setPage($page)
    {
        $this->apiAttributes['page'] = $page;
    }
    
    /**
     * @param $amount
     */
    public function setAmount($amount)
    {
        $this->apiAttributes['amount'] = $amount;
    }

    /**
     * @param $collectionName
     */
    public function setCollection($collectionName)
    {
        $this->apiAttributes['collection'] = $collectionName;
    }

    /**
     * @param string $arrayResponse
     * @return bool
     */
    public function composeNextPageLink($arrayResponse)
    {
        $nextPageLink = $this->validateAndComposeNextPageLink($arrayResponse);
        $this->setNextPageLink($nextPageLink);
    }

    /**
     * Next page link can exists in few ways - check it
     *
     * @param $arrayResponse
     * @return bool|string
     */
    public function validateAndComposeNextPageLink($arrayResponse)
    {
        //Get next page number
        if (empty($arrayResponse['data']['pagination']['pages'])) {

            return false;
        }

        $pages = $arrayResponse['data']['pagination']['pages'];

        if (empty($pages['next'])) {

            return false;
        }
        $nextPage = $pages['next'];

        //Get max page number
        if (empty($pages['total'])) {

            return false;
        }

        $maxPages = $pages['total'];

        //Get current page
        if (empty($pages['current'])) {

            return false;
        }

        $currentPage = $pages['current'];

        //If this page is equal to max page = return false
        if ($currentPage === $maxPages) {

            return false;
        }

        $requestUrl = $this->getRequestUrl();
        $questionMarkOrAmpersand = '&';
        if (strpos($requestUrl, '?') === false) {

            $questionMarkOrAmpersand = '?';
        }
        $nextPageLink = $this->getRequestUrl() . $questionMarkOrAmpersand . 'page=' . $nextPage;

        return $nextPageLink;
    }

    /**
     * @param $response
     * @return int|null
     */
    public function calculateTotalPages($response)
    {
        if (!isset($response['data']['pagination']['pages']['total'])) {

            return null;
        }

        return (int)$response['data']['pagination']['pages']['total'];
    }

    public function getBookByIsbn($isbn)
    {
        $singleBookUrl = $this->getApiSingleBookUrl();
        $composedSingleBookUrl = $singleBookUrl . $isbn;
        
        return $this->getApiResponse($composedSingleBookUrl);
    }

    /**
     * @return string
     */
    public function getApiSingleBookUrl()
    {
        return $this->apiSingleBookUrl;
    }
}