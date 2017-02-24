<?php

namespace App\Books\ApiReflections;


class Goodreads extends \App\Books\Interfaces\AbstractApi
{
    protected $isbns = [];
    protected $apiUrl = 'https://www.goodreads.com/book/review_counts.json';

    public function __construct()
    {
        $key = env('GOODREADS_API_KEY');
        $this->setKey($key);
    }
    
    /**
     * @param string $arrayResponse
     */
    public function composeNextPageLink($arrayResponse)
    {
        //This api doesn't contain next page they propose to enter next values
    }

    /**
     * @param array $isbns
     */
    public function setIsbns($isbns)
    {
        $this->apiAttributes['isbns'] = implode(',', $isbns);
    }

    /**
     * @param $key
     */
    public function setKey($key)
    {
        $this->apiAttributes['key'] = $key;
    }

    /**
     * Different APIs has different amount of pages - calculate it, based on response
     *
     * @param $response
     * @return int|null
     */
    public function calculateTotalPages($response)
    {
        return 1;
    }
}