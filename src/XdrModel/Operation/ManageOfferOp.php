<?php


namespace ZuluCrypto\StellarSdk\XdrModel\Operation;

/**
 * https://github.com/stellar/stellar-core/blob/master/src/xdr/Stellar-transaction.x#L93
 *
 * To update an offer, pass the $offerId
 *
 * To cancel an offer, pass the $offerId and set the $amount to 0
 */
class ManageOfferOp extends ManageSellOfferOp
{

}