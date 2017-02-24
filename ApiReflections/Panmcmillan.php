<?php

namespace App\Books\ApiReflections;

class Panmcmillan extends \App\Books\Interfaces\AbstractApi
{
    protected $apiUrl = 'http://extracts.panmacmillan.com/getextracts';


    /**
     * @param mixed $titlecontains
     */
    public function setTitlecontains($titlecontains)
    {
        $this->apiAttributes['titlecontains'] = $titlecontains;
    }

    /**
     * @param mixed $authorcontains
     */
    public function setAuthorcontains($authorcontains)
    {
        $this->apiAttributes['authorcontains'] = $authorcontains;
    }

    /**
     * @param mixed $isbn
     */
    public function setIsbn($isbn)
    {
        $this->apiAttributes['isbn'] = $isbn;
    }

    /**
     * @param mixed $readingtimelessthan
     */
    public function setReadingtimelessthan($readingtimelessthan)
    {
        $this->apiAttributes['readingtimelessthan'] = $readingtimelessthan;
    }

    /**
     * @param mixed $readingtimegreaterthan
     */
    public function setReadingtimegreaterthan($readingtimegreaterthan)
    {
        $this->apiAttributes['readingtimegreaterthan'] = $readingtimegreaterthan;
    }

    /**
     * @param mixed $publicationdatelessthan
     */
    public function setPublicationdatelessthan($publicationdatelessthan)
    {
        $this->apiAttributes['publicationdatelessthan'] = $publicationdatelessthan;
    }

    /**
     * @param mixed $publicationdategreaterthan
     */
    public function setPublicationdategreaterthan($publicationdategreaterthan)
    {
        $this->apiAttributes['publicationdategreaterthan'] = $publicationdategreaterthan;
    }

    /**
     * @param string $arrayResponse
     */
    public function composeNextPageLink($arrayResponse)
    {
        $nextPageUrl = $arrayResponse['NextPageUrl'];
        if (!empty($nextPageUrl)) {
            $this->setNextPageLink($nextPageUrl);
        } else {
            //If next page empty - clear previously
            $this->setNextPageLink('');
        }
    }

    /**
     * @param $response
     * @return int|null
     */
    public function calculateTotalPages($response)
    {
        if (!isset($response['PageCount'])) {

            return null;
        }

        return (int)$response['PageCount'];
    }
}