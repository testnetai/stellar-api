<?php

namespace ZuluCrypto\StellarSdk\Model;

use ZuluCrypto\StellarSdk\XdrModel\Asset;
use ZuluCrypto\StellarSdk\XdrModel\Price;

/**
 * See: https://www.stellar.org/developers/horizon/reference/resources/operation.html#manage-offer
 */
class ManageSellOfferOperation extends Operation
{
    /**
     *
     * @var int
     */
    protected $offerId;

    /**
     * @var Asset
     */
    protected $buyingAsset;

    /**
     * @var Asset
     */
    protected $sellingAsset;

    /**
     * @var
     */
    protected $amount;

    /**
     * Price to buy a buying_asset
     *
     * @var Price
     */
    protected $price;

    /**
     * n: price numerator, d: price denominator
     *
     * @var array
     */
    protected $priceR;

    /**
     * @param array $rawData
     * @return ManageOfferOperation
     */
    public static function fromRawResponseData($rawData)
    {
        $object = new ManageOfferOperation($rawData['id'], Operation::TYPE_MANAGE_SELL_OFFER);

        $object->loadFromRawResponseData($rawData);

        return $object;
    }

    /**
     * @param $id
     * @param $type
     *
     * @link    https://stellar.github.io/js-stellar-sdk/node_modules_stellar-base_src_operations_manage_buy_offer.js.html
     * @link    https://stellar.github.io/js-stellar-sdk/node_modules_stellar-base_src_operations_manage_sell_offer.js.html
     */
    public function __construct($id, $type = Operation::TYPE_MANAGE_SELL_OFFER)
    {
        parent::__construct($id, $type);
    }

    /**
     * @param $rawData
     */
    public function loadFromRawResponseData($rawData)
    {
        // NOTE: we don't receive type or type_i back, so force it in manually
        $rawData['type'] = Operation::TYPE_MANAGE_SELL_OFFER;
        $rawData['type_i'] = \ZuluCrypto\StellarSdk\XdrModel\Operation\Operation::TYPE_MANAGE_SELL_OFFER;

        parent::loadFromRawResponseData($rawData);

        // $this->offerId  = $rawData['offer_id'];
        $this->offerId  = $rawData['id'];
        $this->price    = new Price($rawData['price_r']['n'], $rawData['price_r']['d']);
        $this->priceR   = $rawData['price_r'];

        if (isset($rawData['buying'])) {
            if ($rawData['buying']['asset_type'] === 'native') {
                $this->buyingAsset = Asset::newNativeAsset();
            } else {
                $this->buyingAsset = Asset::newCustomAsset($rawData['buying']['asset_code'], $rawData['buying']['asset_issuer']);
            }
        }

        if (isset($rawData['selling'])) {
            if ($rawData['selling']['asset_type'] === 'native') {
                $this->sellingAsset = Asset::newNativeAsset();
            } else {
                $this->sellingAsset = Asset::newCustomAsset($rawData['selling']['asset_code'], $rawData['selling']['asset_issuer']);
            }
        }

        $this->amount = new StellarAmount($rawData['amount']); // buyAmount for buy_offer
    }

    public function getOfferId()
    {
        return $this->offerId;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function getBuyingAsset()
    {
        return $this->buyingAsset;
    }

    public function getSellingAsset()
    {
        return $this->sellingAsset;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    public function getSourceAccount()
    {
        return $this->sourceAccount;
    }
}
