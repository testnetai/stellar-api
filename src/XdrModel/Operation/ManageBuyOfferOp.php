<?php


namespace ZuluCrypto\StellarSdk\XdrModel\Operation;


use phpseclib\Math\BigInteger;
use ZuluCrypto\StellarSdk\Model\AssetAmount;
use ZuluCrypto\StellarSdk\Model\StellarAmount;
use ZuluCrypto\StellarSdk\Util\Debug;
use ZuluCrypto\StellarSdk\Xdr\XdrBuffer;
use ZuluCrypto\StellarSdk\Xdr\XdrEncoder;
use ZuluCrypto\StellarSdk\XdrModel\AccountId;
use ZuluCrypto\StellarSdk\XdrModel\Asset;
use ZuluCrypto\StellarSdk\XdrModel\Price;

/**
 * https://github.com/stellar/stellar-core/blob/master/src/xdr/Stellar-transaction.x#L93
 *
 * To update an offer, pass the $offerId
 *
 * To cancel an offer, pass the $offerId and set the $amount to 0
 */
class ManageBuyOfferOp extends Operation
{
    /**
     * @var Asset
     */
    protected $sellingAsset;

    /**
     * @var Asset
     */
    protected $buyingAsset;

    /**
     *
     * @var StellarAmount
     */
    protected $buyAmount;

    /**
     * @var Price
     */
    protected $price;

    /**
     * @var int
     */
    protected $offerId;

    /**
     * ManageOfferOp constructor.
     *
     * @param Asset $sellingAsset
     * @param Asset $buyingAsset
     * @param int|BigInteger $buyAmount int representing lumens or BigInteger representing stroops
     * @param Price $price
     * @param null  $offerId
     * @param null  $sourceAccount
     */
    public function __construct(Asset $sellingAsset, Asset $buyingAsset, $buyAmount, Price $price, $offerId = null, $sourceAccount = null)
    {
        parent::__construct(Operation::TYPE_MANAGE_BUY_OFFER, $sourceAccount);

        $this->sellingAsset = $sellingAsset;
        $this->buyingAsset = $buyingAsset;
        $this->buyAmount = new StellarAmount($buyAmount);
        $this->price = $price;
        $this->offerId = $offerId;
    }

    /**
     * @return string XDR bytes
     */
    public function toXdr()
    {
        $bytes = parent::toXdr();

        $bytes .= $this->sellingAsset->toXdr();
        $bytes .= $this->buyingAsset->toXdr();
        $bytes .= XdrEncoder::signedBigInteger64($this->buyAmount->getUnscaledBigInteger());
        $bytes .= $this->price->toXdr();
        $bytes .= XdrEncoder::unsignedInteger64($this->offerId);

        return $bytes;
    }

    /**
     * @deprecated Do not call this directly, instead call Operation::fromXdr()
     * @param XdrBuffer $xdr
     * @return ManageOfferOp|Operation
     * @throws \ErrorException
     */
    public static function fromXdr(XdrBuffer $xdr)
    {
        $sellingAsset = Asset::fromXdr($xdr);
        $buyingAsset = Asset::fromXdr($xdr);
        $buyAmount = StellarAmount::fromXdr($xdr);
        $price = Price::fromXdr($xdr);
        $offerId = $xdr->readUnsignedInteger64();

        return new ManageBuyOfferOp($sellingAsset,
            $buyingAsset,
            $buyAmount->getUnscaledBigInteger(),
            $price,
            $offerId
        );
    }

    /**
     * @return Asset
     */
    public function getSellingAsset()
    {
        return $this->sellingAsset;
    }

    /**
     * @param Asset $sellingAsset
     */
    public function setSellingAsset($sellingAsset)
    {
        $this->sellingAsset = $sellingAsset;
    }

    /**
     * @return Asset
     */
    public function getBuyingAsset()
    {
        return $this->buyingAsset;
    }

    /**
     * @param Asset $buyingAsset
     */
    public function setBuyingAsset($buyingAsset)
    {
        $this->buyingAsset = $buyingAsset;
    }

    /**
     * @return StellarAmount
     */
    public function getBuyAmount()
    {
        return $this->buyAmount;
    }

    /**
     * @param int $amount
     */
    public function setBuyAmount($buyAmount)
    {
        $this->buyAmount = new StellarAmount($buyAmount);
    }

    /**
     * @return Price
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param Price $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * @return int
     */
    public function getOfferId()
    {
        return $this->offerId;
    }

    /**
     * @param int $offerId
     */
    public function setOfferId($offerId)
    {
        $this->offerId = $offerId;
    }
}