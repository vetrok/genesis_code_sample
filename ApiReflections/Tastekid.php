<?php

namespace App\Books\ApiReflections;


class Tastekid extends \App\Books\Interfaces\AbstractApi
{
    protected $apiUrl = 'https://www.tastekid.com/api/similar';

    public function __construct()
    {
        $key = env('TASTEKID_API_KEY');
        $this->setKey($key);
        $this->setAdditionalRequestOptions(
            [
                'stream' => true,
                'stream_context' => [
                    'ssl' => [
                        "verify_peer" => false,
                        "verify_peer_name" => false,
                    ],

                ]
            ]
        );
    }

    /**
     * @param string $arrayResponse
     */
    public function composeNextPageLink($arrayResponse)
    {
        //Don't separate to pages
    }

    /**
     * @param $key
     */
    public function setKey($key)
    {
        $this->apiAttributes['k'] = $key;
    }

    /**
     * @param $queryString
     */
    public function setQuery($queryString)
    {
        $this->apiAttributes['q'] = $queryString;
    }

    /**
     * @param $searchType
     */
    public function setType($searchType)
    {
        $this->apiAttributes['type'] = $searchType;
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