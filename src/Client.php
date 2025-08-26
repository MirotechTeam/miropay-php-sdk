<?php

class MiroHttpClient
{
    private readonly string $x_id;
    private readonly string $secret;
    private readonly string $secret_key_64;
    private readonly string $base_url;
    private readonly string $content_type;

    public function __construct(string $base_url, string $content_type, string $decrypted_pem, string $x_id)
    {
        $this->base_url = $base_url;
        $this->content_type = $content_type;
        $this->x_id = $x_id;
        $this->secret_key_64 = $this->generate_secret_key_64($decrypted_pem);
    }

    public function generate_secret_key_64(string $decrypted_pem): string
    {
        $replace_pem = preg_replace('/-----.* PRIVATE KEY-----|\s+/', '', $decrypted_pem);
        $der = base64_decode($replace_pem);
        $seed = substr($der, -32);
        $key_pair = sodium_crypto_sign_seed_keypair($seed);
        $secret_key_64 = sodium_crypto_sign_secretkey($key_pair);

        return $secret_key_64;
    }

    public function create_signature(string $method, string $path, string $secret): string
    {
        $raw_str = "$method || $secret || $path";
        $signature = sodium_crypto_sign_detached($raw_str, $this->secret_key_64);

        return base64_encode($signature);
    }

    public function get(string $path): MiroHttpResponse
    {
        $signature = $this->create_signature("GET", "/v1/$path", $this->x_id);
        $url = $this->generate_url($path);

        return $this->make_request($url, $signature, 'GET');
    }

    public function post(string $path, array $data): MiroHttpResponse
    {
        $signature = $this->create_signature("POST", "/v1/$path", $this->x_id);
        $url = $this->generate_url($path);

        return $this->make_request($url, $signature, 'POST', $data);
    }

    public function patch(string $path, array $data = []): MiroHttpResponse
    {
        $signature = $this->create_signature("PATCH", "/v1/$path", $this->x_id);
        $url = $this->generate_url($path);

        return $this->make_request($url, $signature, 'PATCH', $data);
    }

    private function make_request(string $url, string $signature, string $method, ?array $data = null): MiroHttpResponse
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response as string
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects if any
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "x-id: $this->x_id",
            "x-signature: $signature",
            "content-type: application/json"
        ]);

        if (in_array($method, ["POST", "PATCH"])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new ErrorException("Couldn't complete the request");
        }

        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $parsed_content = json_decode($result, true);

        return new MiroHttpResponse($status_code, $parsed_content);
    }

    /* ================================ Privates ================================ */

    private function generate_url(string $path): string
    {
        return "$this->base_url/$path";
    }

    private function generate_response(): array
    {
        return [
            'status' => http_response_code()
        ];
    }
}

class MiroHttpResponse
{
    private int $status;
    public array $data;

    public function __construct(int $status, array $data)
    {
        $this->status = $status;
        $this->data = $data;
    }

    public function get_status(): int
    {
        return $this->status;
    }
}
