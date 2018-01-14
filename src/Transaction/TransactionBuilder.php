<?php


namespace ZuluCrypto\StellarSdk\Transaction;

use phpseclib\Math\BigInteger;
use ZuluCrypto\StellarSdk\Horizon\ApiClient;
use ZuluCrypto\StellarSdk\Keypair;
use ZuluCrypto\StellarSdk\Model\StellarAmount;
use ZuluCrypto\StellarSdk\Signing\SigningInterface;
use ZuluCrypto\StellarSdk\Util\MathSafety;
use ZuluCrypto\StellarSdk\Xdr\Iface\XdrEncodableInterface;
use ZuluCrypto\StellarSdk\Xdr\Type\VariableArray;
use ZuluCrypto\StellarSdk\Xdr\XdrEncoder;
use ZuluCrypto\StellarSdk\XdrModel\AccountId;
use ZuluCrypto\StellarSdk\XdrModel\Asset;
use ZuluCrypto\StellarSdk\XdrModel\DecoratedSignature;
use ZuluCrypto\StellarSdk\XdrModel\Memo;
use ZuluCrypto\StellarSdk\XdrModel\Operation\AccountMergeOp;
use ZuluCrypto\StellarSdk\XdrModel\Operation\AllowTrustOp;
use ZuluCrypto\StellarSdk\XdrModel\Operation\ChangeTrustOp;
use ZuluCrypto\StellarSdk\XdrModel\Operation\CreateAccountOp;
use ZuluCrypto\StellarSdk\XdrModel\Operation\ManageDataOp;
use ZuluCrypto\StellarSdk\XdrModel\Operation\Operation;
use ZuluCrypto\StellarSdk\XdrModel\Operation\PaymentOp;
use ZuluCrypto\StellarSdk\XdrModel\TimeBounds;
use ZuluCrypto\StellarSdk\XdrModel\TransactionEnvelope;


/**
 * todo: rename to Transaction
 * Helper class to build a transaction on the Stellar network
 *
 * References:
 *  Debugging / testing:
 *      https://www.stellar.org/laboratory/
 *
 *  Retrieve fee information from:
 *      https://www.stellar.org/developers/horizon/reference/endpoints/ledgers-single.html
 *      https://www.stellar.org/developers/horizon/reference/resources/ledger.html
 *
 * Notes:
 *  - Per-operation fee is 100 stroops (0.00001 XLM)
 *  - Base reserve is 10 XLM
 *      - Minimum balance for an account is base reserve * 2
 *      - Each additional trustline, offer, signer, and data entry requires another 10 XLM
 *
 *
 * Format of a transaction:
 *  Source Address (AddressId)
 *      type
 *      address
 *  Fee (Uint32)
 *  Next sequence number (SequenceNumber - uint64)
 *      ...
 *  Time bounds (TimeBounds)
 *  Memo (Memo)
 *  Operations (Operation[])
 *  ext (TransactionExt) - extra? currently is a union with no arms
 */
class TransactionBuilder implements XdrEncodableInterface
{
    /**
     * Base-32 account ID
     *
     * @var AccountId
     */
    private $accountId;

    /**
     * @var TimeBounds
     */
    private $timeBounds;

    /**
     * @var Memo
     */
    private $memo;

    /**
     * @var VariableArray[]
     */
    private $operations;

    /**
     * Horizon API client, used for retrieving sequence numbers and validating
     * transaction
     *
     * @var ApiClient
     */
    private $apiClient;

    /**
     * @var SigningInterface
     */
    protected $signingProvider;

    /**
     * @var DecoratedSignature[]
     */
    protected $signatures;

    /**
     * If null, this is retrieved from the network
     *
     * @var BigInteger
     */
    protected $sequenceNumber;

    /**
     * TransactionBuilder constructor.
     *
     * @param $sourceAccountId
     * @return TransactionBuilder
     */
    public function __construct($sourceAccountId)
    {
        $this->accountId = new AccountId($sourceAccountId);

        $this->timeBounds = new TimeBounds();
        $this->memo = new Memo(Memo::MEMO_TYPE_NONE);
        $this->operations = new VariableArray();

        $this->signatures = [];

        return $this;
    }

    /**
     * Uses $signer to add a new DecoratedSignature to this TransactionBuilder
     *
     * @param SigningInterface $signer
     * @return DecoratedSignature
     */
    public function signWith(SigningInterface $signer)
    {
        $decoratedSignature = $signer->signTransaction($this);

        $this->signatures[] = $decoratedSignature;

        return $decoratedSignature;
    }

    /**
     * @return TransactionEnvelope
     */
    public function getTransactionEnvelope()
    {
        $txEnvelope = new TransactionEnvelope($this);

        foreach ($this->signatures as $signature) {
            $txEnvelope->addDecoratedSignature($signature);
        }

        return $txEnvelope;
    }

    /**
     * @param $secretKeyString
     * @return TransactionEnvelope
     */
    public function sign($secretKeyString = null)
    {
        // If $secretKeyString is null, check for a SigningProvider
        if (!$secretKeyString) {
            if (!$this->signingProvider) throw new \ErrorException('$secretKeyString was empty and no signingProvider is set');

            $this->signWith($this->signingProvider);

            return $this->getTransactionEnvelope();
        }
        else {
            return (new TransactionEnvelope($this))->sign($secretKeyString);
        }
    }

    public function hash()
    {
        return $this->apiClient->hash($this);
    }

    public function getHashAsString()
    {
        return $this->apiClient->getHashAsString($this);
    }

    /**
     * @param $secretKeyString string|Keypair
     * @return \ZuluCrypto\StellarSdk\Horizon\Api\HorizonResponse
     */
    public function submit($secretKeyString = null)
    {
        if ($secretKeyString instanceof Keypair) {
            $secretKeyString = $secretKeyString->getSecret();
        }

        return $this->apiClient->submitTransaction($this, $secretKeyString);
    }

    public function getFee()
    {
        // todo: calculate real fee
        return 100;
    }

    /**
     * @param string|Keypair          $destination
     * @param number|BigInteger       $amount int representing lumens or BigInteger representing stroops
     * @param null                    $sourceAccountId
     * @return TransactionBuilder
     */
    public function addLumenPayment($destination, $amount, $sourceAccountId = null)
    {
        return $this->addOperation(PaymentOp::newNativePayment($destination, $amount, $sourceAccountId));
    }

    /**
     * @param string            $newAccountId
     * @param number|BigInteger $amount int representing lumens or BigInteger representing stroops
     * @param string            $sourceAccountId
     * @return TransactionBuilder
     */
    public function addCreateAccountOp($newAccountId, $amount, $sourceAccountId = null)
    {
        return $this->addOperation(new CreateAccountOp(new AccountId($newAccountId), $amount, $sourceAccountId));
    }

    /**
     * @param Asset               $asset
     * @param number|BigInteger   $amount number representing lumens or BigInteger representing stroops
     * @param string|Keypair      $destinationAccountId
     * $param null|string|Keypair $sourceAccountId
     * @return TransactionBuilder
     */
    public function addCustomAssetPaymentOp(Asset $asset, $amount, $destinationAccountId, $sourceAccountId = null)
    {
        return $this->addOperation(
            PaymentOp::newCustomPayment($destinationAccountId, $amount, $asset->getAssetCode(), $asset->getIssuer()->getAccountIdString(), $sourceAccountId)
        );
    }

    /**
     * @param Asset $asset
     * @param int   $amount defaults to maximum if null
     * @param null  $sourceAccountId
     * @return TransactionBuilder
     */
    public function addChangeTrustOp(Asset $asset, $amount = null, $sourceAccountId = null)
    {
        if ($amount === null) {
            $amount = StellarAmount::newMaximum();
        }

        return $this->addOperation(new ChangeTrustOp($asset, $amount, $sourceAccountId));
    }

    /**
     * This is called by asset issuers to authorize a trustline established by
     * a client account
     *
     * @param Asset $asset
     * @param       $trustorId
     * @param null  $sourceAccountId
     * @return TransactionBuilder
     */
    public function authorizeTrustline(Asset $asset, $trustorId, $sourceAccountId = null)
    {
        if ($trustorId instanceof Keypair) {
            $trustorId = $trustorId->getPublicKey();
        }

        $op = new AllowTrustOp($asset, new AccountId($trustorId), $sourceAccountId);
        $op->setIsAuthorized(true);

        return $this->addOperation($op);
    }

    /**
     * This is called by asset issuers to revoke a trustline established by
     * a client account
     *
     * @param Asset $asset
     * @param       $trustorId
     * @param null  $sourceAccountId
     * @return TransactionBuilder
     */
    public function revokeTrustline(Asset $asset, $trustorId, $sourceAccountId = null)
    {
        if ($trustorId instanceof Keypair) {
            $trustorId = $trustorId->getPublicKey();
        }

        $op = new AllowTrustOp($asset, new AccountId($trustorId), $sourceAccountId);
        $op->setIsAuthorized(false);

        return $this->addOperation($op);
    }

    /**
     * Adds an operation to merge the balance of the source account to $destinationAccountId
     * @param      $destinationAccountId
     * @param null $sourceAccountId
     * @return TransactionBuilder
     */
    public function addMergeOperation($destinationAccountId, $sourceAccountId = null)
    {
        if ($destinationAccountId instanceof Keypair) {
            $destinationAccountId = $destinationAccountId->getPublicKey();
        }

        return $this->addOperation(new AccountMergeOp($destinationAccountId, $sourceAccountId));
    }

    /**
     * @param      $key
     * @param      $value
     * @param null $sourceAccountId
     * @return TransactionBuilder
     */
    public function setAccountData($key, $value = null, $sourceAccountId = null)
    {
        return $this->addOperation(new ManageDataOp($key, $value, $sourceAccountId));
    }

    /**
     * @param      $key
     * @param null $sourceAccountId
     * @return TransactionBuilder
     */
    public function clearAccountData($key, $sourceAccountId = null)
    {
        return $this->addOperation(new ManageDataOp($key, null, $sourceAccountId));
    }

    /**
     * @return string
     */
    public function toXdr()
    {
        $bytes = '';

        // todo: $sequenceNumber should always be a BigInteger
        $sequenceNumber = $this->sequenceNumber->toString();
        if (!$sequenceNumber) {
            $sequenceNumber = $this->generateSequenceNumber();
        }

        // Account ID (36 bytes)
        $bytes .= $this->accountId->toXdr();
        // Fee (4 bytes)
        $bytes .= XdrEncoder::unsignedInteger($this->getFee());
        // Sequence number (8 bytes)
        $bytes .= XdrEncoder::unsignedInteger64($sequenceNumber);
        // Time Bounds (4 bytes if empty, 20 bytes if set)
        $bytes .= $this->timeBounds->toXdr();
        // Memo (4 bytes if empty, 36 bytes maximum)
        $bytes .= $this->memo->toXdr();

        // Operations
        $bytes .= $this->operations->toXdr();

        // TransactionExt (union reserved for future use)
        $bytes .= XdrEncoder::unsignedInteger(0);

        return $bytes;
    }

    /**
     * @param $operation
     * @return TransactionBuilder
     */
    public function addOperation($operation)
    {
        $this->operations->append($operation);

        return $this;
    }

    /**
     * @param $memo
     * @return $this
     */
    public function setTextMemo($memo)
    {
        $this->memo = new Memo(Memo::MEMO_TYPE_TEXT, $memo);

        return $this;
    }

    /**
     * @param $memo
     * @return $this
     */
    public function setIdMemo($memo)
    {
        $this->memo = new Memo(Memo::MEMO_TYPE_ID, $memo);

        return $this;
    }

    /**
     * Note: this should be called with the raw sha256 hash
     *
     * For example:
     *  $builder->setHashMemo(hash('sha256', 'example thing being hashed', true));
     *
     * @param $memo 32-byte sha256 hash
     * @return $this
     */
    public function setHashMemo($memo)
    {
        $this->memo = new Memo(Memo::MEMO_TYPE_HASH, $memo);

        return $this;
    }

    /**
     * Note: this should be called with the raw sha256 hash
     *
     * For example:
     *  $builder->setReturnMemo(hash('sha256', 'example thing being hashed', true));
     *
     * @param $memo 32-byte sha256 hash
     * @return $this
     */
    public function setReturnMemo($memo)
    {
        $this->memo = new Memo(Memo::MEMO_TYPE_RETURN, $memo);

        return $this;
    }

    /**
     * @param \DateTime $lowerTimebound
     * @return $this
     */
    public function setLowerTimebound(\DateTime $lowerTimebound)
    {
        $this->timeBounds->setMinTime($lowerTimebound);

        return $this;
    }

    /**
     * @param \DateTime $upperTimebound
     * @return $this
     */
    public function setUpperTimebound(\DateTime $upperTimebound)
    {
        $this->timeBounds->setMaxTime($upperTimebound);

        return $this;
    }

    protected function generateSequenceNumber()
    {
        $this->ensureApiClient();

        return $this->apiClient
                ->getAccount($this->accountId->getAccountIdString())
                ->getSequence() + 1
        ;
    }

    protected function ensureApiClient()
    {
        if (!$this->apiClient) throw new \ErrorException("An API client is required, call setApiClient before using this method");
    }

    /**
     * @return ApiClient
     */
    public function getApiClient()
    {
        return $this->apiClient;
    }

    /**
     * @param ApiClient $apiClient
     * @return TransactionBuilder
     */
    public function setApiClient($apiClient)
    {
        $this->apiClient = $apiClient;

        return $this;
    }

    /**
     * @return SigningInterface
     */
    public function getSigningProvider()
    {
        return $this->signingProvider;
    }

    /**
     * @param SigningInterface $signingProvider
     */
    public function setSigningProvider($signingProvider)
    {
        $this->signingProvider = $signingProvider;

        return $this;
    }

    /**
     * @return BigInteger
     */
    public function getSequenceNumber()
    {
        return $this->sequenceNumber;
    }

    /**
     * @param BigInteger $sequenceNumber
     */
    public function setSequenceNumber($sequenceNumber)
    {
        $this->sequenceNumber = $sequenceNumber;

        return $this;
    }
}