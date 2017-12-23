<?php


namespace ZuluCrypto\StellarSdk\XdrModel;


use ZuluCrypto\StellarSdk\Xdr\Iface\XdrEncodableInterface;
use ZuluCrypto\StellarSdk\Xdr\XdrEncoder;

/**
 * https://github.com/stellar/stellar-core/blob/master/src/xdr/Stellar-ledger-entries.x#L47
 *
 * todo: implement ::fromNumber() for easy calculation of numerator and denominator
 */
class Price implements XdrEncodableInterface
{
    /**
     * @var int
     */
    protected $numerator;

    /**
     * @var int
     */
    protected $denominator;

    public function __construct($numerator, $denominator = 1)
    {
        $this->numerator = $numerator;
        $this->denominator = $denominator;
    }

    public function toXdr()
    {
        $bytes = '';

        $bytes .= XdrEncoder::unsignedInteger($this->numerator);
        $bytes .= XdrEncoder::unsignedInteger($this->denominator);

        return $bytes;
    }

    /**
     * @return int
     */
    public function getNumerator()
    {
        return $this->numerator;
    }

    /**
     * @param int $numerator
     */
    public function setNumerator($numerator)
    {
        $this->numerator = $numerator;
    }

    /**
     * @return int
     */
    public function getDenominator()
    {
        return $this->denominator;
    }

    /**
     * @param int $denominator
     */
    public function setDenominator($denominator)
    {
        $this->denominator = $denominator;
    }
}