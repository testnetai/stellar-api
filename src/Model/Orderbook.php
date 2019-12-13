<?php


namespace ZuluCrypto\StellarSdk\Model;

use ZuluCrypto\StellarSdk\Horizon\Api\HorizonResponse;


/**
 * See: https://www.stellar.org/developers/horizon/reference/resources/orderbook.html
 */
class Orderbook extends RestApiModel
{

    public $data;

    /**
     * @return Orderbook
     */
    public static function fromRawResponseData($rawData)
    {
        $object = new Orderbook();

        $object->loadFromRawResponseData($rawData);

        return $object;
    }

    public function loadFromRawResponseData($rawData)
    {
        parent::loadFromRawResponseData($rawData);

        $this->data = $rawData;
    }

    /**
     * @param HorizonResponse $response
     * @return Account
     */
    public static function fromHorizonResponse(HorizonResponse $response)
    {
        $rawData = $response->getRawData();

        return Orderbook::fromRawResponseData($rawData);
    }

    public function getData()
    {
        return $this->data;
    }

}
