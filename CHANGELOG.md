### 0.8.0

This change represents all additions completed by testnet.ai.

 * Updated ApiClient to support the passing of GET url parameters as an array. This allows for integration with BlockDaemon Stellar Horizon nodes.
 * Updated ApiClient to include new method for `getOrderbook()`
 * Exposed ApiClient's `getHttpClient()` method to allow for user's to define their own custom requests should they want to (i.e. if the SDK lags behind API advancements).
 * Added `getOffers()` and `getFlags()` to the `Account` model
 * Added `ManageBuyOfferOperation`, `ManageSellOfferOperation`, and aliased (deprecated) `ManageOfferOperation` to extend `ManageSellOfferOperation`
 * Added `Operation::TYPE_MANAGE_BUY_OFFER` and `Operation::TYPE_MANAGE_SELL_OFFER` to reflect more recent Horizon API changes
 * Updated `Operation::fromRawResponseData()` to support the newer representations of `TYPE_MANAGE_BUY_OFFER` and `TYPE_MANAGE_SELL_OFFER`
 * Added `setFee()` method to `TransactionBuilder` to be able to dynamically set a custom fee for a given transaction.
 * 

### 0.7.0

 * Additional support for BumpSequenceOp: it is now read correctly when listing operations
 * Convenience method for checking if an account is valid: `Account::isValidAccount`
 * Convenience method for checking if an account exists on the network (is funded): `Server::accountExists`
 * `getTransactionHash()` is now supported on all operations (if Horizon includes it in the response)
 * HTTP Client can now be retrieved and overridden for testing or if you want to use a custom one
 * `TransactionBuilder` now always uses a `BigInteger` internally when working with account sequence numbers
 * Reduced the number of times an API call is made to Horizon to get the account's sequence number. Thanks to @rhysied for pointing out this issue! 

### 0.6.4

Maintenance release

 * TransactionBuilder setMemo() now returns the TransactionBuilder
 * Improved error reporting and error messages
 * Fixed stream payments example

### 0.6.3

Added support for [BumpSequenceOp](https://github.com/stellar/stellar-protocol/issues/53)

Note that this is for forward compatibility. This operation is not yet available in the Stellar test or production networks.

### 0.6.2

Use `empty()` instead of `count()` for checking if `operationResultCodes` are present (thanks @omarfurrer)

### 0.6.1

Fix "Only variables should be passed by reference" notice in several XdrDecoder / XdrEncoder methods (thanks @natrod)

### 0.6.0

Initial support for parsing binary XDR into PHP objects.

See examples/transaction-add-signature.php

To sign existing XDR:

```php
$server = Server::testNet();

$transactionEnvelope = TransactionEnvelope::fromXdr(new XdrBuffer($xdr));
$transactionEnvelope->sign($keypair, $server);

$server->submitB64Transaction($transactionEnvelope->toBase64());
```

### 0.5.0

This release adds parsing of the Transaction response so you can get detailed information
on the result of each operation.

 * When submitting a transaction, you will now get back an instance of `PostTransactionResponse`
 * If a transaction fails due to a failing operation, a `PostTransactionException` is now thrown
 
These two new classes have methods for retrieveing details about each operation within the transaction.

In addition, the text transaction result and operation results from the Horizon response are now included (thanks @omarfurrer)  

### 0.4.0

**Breaking Changes / Incompatibilities**
 * Refactored `getPayments` and `streamPayments` to return a new `AssetTransferInterface` instead of a `Payment` object. See details below.

This release is to refactor how payments are handled. The Horizon `/accounts/<account ID>/payments` endpoint
returns a variety of operations, all of which represent XLM or another asset being transferred:

 * Payment
 * Path payment
 * Create account
 * Merge account
 
Methods that previously returned a `Payment` will now return `AssetTransferInterface`

To migrate your code:

1. Replace any calls to `$payment->getType()` with `$payment->getAssetTransferType()`. This will return a string describing the operation type
2. Replace `$payment->getAmount()` with `$payment->getAssetAmount()`

### 0.3.0

**Breaking Changes / Incompatibilities**
 * Removed previously deprecated `CreateAccountXdrOperation`

New Features / Fixes:
 * "Create Account" operations are no longer included when streaming payments.
 Previously, they would cause a crash since they could not be decoded to `Payment`
 objects.
 * Fixes and clarifications on several of the "getting started" examples
 * `Account::getData` has been improved to base64-decode the account data automatically
 * Added `Payment::getTransactionHash()` (contributed by @cballou)
 * Several improvements to signature handling so that pre-authorized transactions can be submitted
 * Fixed an issue encoding variable length opaque structures
 * Fees are now calculated correctly for transactions with multiple operations

### 0.2.2

 * `Keypair` objects can now be created from public keys (a secret key is no longer required). See `Keypair::newFromPublicKey`
 * Fix to `ManageDataOp` XDR encoding
 
### 0.2.1

 * Fixed an issue where the private key was used instead of the public key when building signer XDR

### 0.2.0

**Breaking Changes / Incompatibilities**
 * **IMPORTANT**: arguments to `PaymentOp` have changed (the destination is now the first argument and source is an optional argument) 
 * `Server->getAccount()` now returns `null` if an account is not found. Previously,
 it threw an exception.
 * `CreateAccountXdrOperation` has been deprecated and replaced with `CreateAccountOp`.
 It will be removed in version 1.2.0
 * `PaymentOp::newNativePayment` now has `sourceAccountId` as an optional argument that comes last
 * `PaymentOp::newCustomPayment` now has `sourceAccountId` as an optional argument that comes last
 * `TransactionBuilder::addCustomAssetPaymentOp` now has `sourceAccountId` as an optional argument that comes last
 
New Features
 * All operations are now supported by `TransactionBuilder`
 * Better error handling with `HorizonException` class that provides detailed
 information pulled from Horizon's REST API error messages.
 * Added `StellarAmount` class for working with numbers that may exceed PHP integer limits
 * General improvements to large number checks and more tests added 

### 0.1.0
 * Initial beta version
