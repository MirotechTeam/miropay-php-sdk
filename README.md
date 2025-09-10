# PHP SDK for Miro Payment System (Alpha)

⚠️ **Alpha Release Disclaimer**  
This SDK is currently in **alpha version** and **not production ready**.  
It is intended for testing, experimentation, and feedback only.  
Breaking changes may occur in future updates.

---

## Installation

You can install the SDK via **Composer** from [Packagist](https://packagist.org/packages/miropay/payment-sdk):

```bash
composer require miropay/payment-sdk
```

## Quick Start

```php
use Payment\Sdk;

// Initialize SDK
$mode = "sandbox"; // or "production"
$secret = "your_api_secret";
$pv_key = "your_private_key";
$base_url = "https://api.miro-payments.com/";

$sdk = new Sdk($mode, $secret, $pv_key, $base_url);

// Create a new payment
$payment = $sdk->create_payment([
    "title" => "Order #12345",
    "amount" => 49.99,
    "description" => "E-commerce purchase",
    "redirect_url" => "https://your-website.com/payment/callback",
    "gateways" => ["paypal", "credit_card"],
    "collect_customer_email" => true,
    "collect_customer_phone_number" => true,
    "collect_fee_from_customer" => false
]);

echo "Payment Status: " . $payment->status;
```

## Methods

### 1. get_public_keys(): void

Fetches the API public keys used for encryption/verification.
Currently prints the result (var_dump).

#### Input:

None

#### Output:

API public keys (printed directly).

#### 2. create_payment(array $data): MiroPaymentStatus

Creates a new payment request.

#### Input ($data):

title (string, required) → Title of the payment.

amount (float, required) → Payment amount.

description (string, required) → Description of the transaction.

redirect_url (string, required) → URL where the customer is redirected after payment.

gateways (array, required) → List of supported gateways (e.g., ["paypal"]).

collect_customer_email (bool, required) → Whether to collect customer email.

collect_customer_phone_number (bool, required) → Whether to collect customer phone number.

collect_fee_from_customer (bool, required) → Whether to charge processing fee to customer.

#### Output:

Returns a MiroPaymentStatus object with:

status (string) → Current status (pending, completed, canceled, etc.)

paid_via (string|null) → Gateway used, if paid.

paid_at (string|null) → Timestamp when payment was completed.

redirect_url (string) → URL to redirect customer to pay.

payout_amount (string|null) → Final payout after fees.

### 3. get_status(string $id): MiroPaymentStatus

Retrieves the status of a payment by its ID.

#### Input:

id (string, required) → Payment reference ID.

#### Output:

MiroPaymentStatus object (see above).

### 4. cancel(string $id): MiroPaymentStatus

Cancels an existing payment.

#### Input:

id (string, required) → Payment reference ID.

#### Output:

MiroPaymentStatus object with updated status (likely canceled).

## Class: MiroPaymentStatus

Represents a payment status returned by the API.

### Properties

status (string) → Current status.

paid_via (string|null) → Gateway used for payment.

paid_at (string|null) → Time the payment was made.

redirect_url (string) → Payment page redirect link.

payout_amount (string|null) → Payout amount after fees.

### Error Handling

If required fields are missing in create_payment, an Exception is thrown.

Make sure to validate input before sending a request.
