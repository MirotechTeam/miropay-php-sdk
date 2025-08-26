<?php

require_once "./Client.php";

class MiroPaymentStatus
{
    public readonly string $status;
    public readonly ?string $paid_via;
    public readonly ?string $paid_at;
    public readonly string $redirect_url;
    public readonly ?string $payout_amount;

    public function __construct(array $data)
    {
        $this->reference_code = $data["referenceCode"];
        $this->status = $data["status"];
        $this->paid_via = $data["paidVia"];
        $this->paid_at = $data["paidAt"];
        $this->redirect_url = $data["redirectUrl"];
        $this->payout_amount = $data["payoutAmount"];
    }
}

class Sdk
{
    private readonly MiroHttpClient $client;
    private readonly string $base_url;
    private readonly string $create_payment_url;
    private readonly string $cancel_payment_url;
    private readonly string $get_payment_status_url;
    private readonly string $get_public_keys_url;
    private readonly string $secret;
    private readonly string $pv_key;

    public function __construct(string $mode, string $secret, string $pv_key, string $base_url)
    {
        $this->base_url = $base_url;
        $this->create_payment_url = "payment/rest/$mode/create";
        $this->get_payment_status_url = "payment/rest/$mode/status";
        $this->cancel_payment_url = "payment/rest/$mode/cancel";
        $this->get_public_keys_url = "payment/rest/$mode/get-public-keys";
        $this->secret = $secret;
        $this->pv_key = $pv_key;

        $this->client = new MiroHttpClient($base_url, "application/json", $pv_key, $secret);
    }

    public function get_public_keys()
    {
        $response = $this->client->get($this->get_public_keys_url);
        var_dump($response);
    }

    public function create_payment(array $data): MiroPaymentStatus
    {
        if (
            empty($data["title"]) ||
            empty($data["amount"]) ||
            (empty($data["gateways"]) && $data["gateways"] !== []) ||
            empty($data["description"]) ||
            empty($data["redirect_url"]) ||
            empty($data["collect_customer_email"]) ||
            empty($data["collect_fee_from_customer"]) ||
            empty($data["collect_customer_phone_number"])
        ) {
            throw new Exception("Missing required fields");
        }

        $response = $this->client->post($this->create_payment_url, [
            "amount" => $data["amount"],
            "title" => $data["title"],
            "description" => $data["description"],
            "redirectUrl" => $data["redirect_url"],
            "gateways" => $data["gateways"],
            "collectCustomerEmail" => $data["collect_customer_email"],
            "collectCustomerPhoneNumber" => $data["collect_customer_phone_number"],
            "collectFeeFromCustomer" => $data["collect_fee_from_customer"]
        ]);

        return new MiroPaymentStatus($response->data);
    }

    public function get_status(string $id): MiroPaymentStatus
    {
        $url = "$this->get_payment_status_url/$id";
        $response = $this->client->get($url);

        return new MiroPaymentStatus($response->data);
    }

    public function cancel(string $id): MiroPaymentStatus
    {
        $url = "$this->cancel_payment_url/$id";
        $response = $this->client->patch($url);

        return new MiroPaymentStatus($response->data);
    }
}
