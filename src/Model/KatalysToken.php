<?php

namespace OneO\Model;

/**
 * Class KatalysToken
 */
class KatalysToken
{
    /**
     * @var string
     */
    protected $keyId;

    /**
     * @var string
     */
    protected $secret;

    /**
     * @var string
     */
    protected $version = 'ks01';

    /**
     * @param string $keyId
     * @return $this
     */
    public function setKeyId(string $keyId)
    {
        $this->keyId = $keyId;
        return $this;
    }

    /**
     * @param string $secret
     * @return $this
     */
    public function setSecret(string $secret)
    {
        $this->secret = $secret;
        return $this;
    }

    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function getKeyId(): string
    {
        return $this->keyId;
    }

    /**
     * @return string
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version ?? 'ks01';
    }

    /**
     * Generate a one-time-use token for the raw payload being sent to the Katalys API.
     *
     * @param string $body
     * @param string $expireData
     * @return string
     * @throws \Exception
     */
    public function createToken(string $body, string $expireData = 'PT03H'): string
    {
        if (!$body) {
            throw new \Exception("rawPayload must be a string or JSON object");
        }

        if (!$this->getKeyId()) {
            throw new \Exception('Key must be informed');
        }

        if (!$this->getSecret()) {
            throw new \Exception('Secret must be informed');
        }

        $date = new \DateTime();
        $date->add(new \DateInterval($expireData));
        $expireEpoch = $date->getTimestamp();
        $nonce = bin2hex(random_bytes(6));
        $expiryPayload = \json_encode([
            'iat' => $expireEpoch,
            'nonce' => $nonce
        ]);
        $base64Payload = base64_encode($expiryPayload);

        $signature = $this->getHashHmac($base64Payload, $body);
        $base64Signature = base64_encode($signature);
        return "{$this->getVersion()}:{$this->getKeyId()}.{$base64Payload}.{$base64Signature}";
    }

    /**
     * Verify that the payload matches the signature of the received token.
     *
     * @param string $token
     * @param string $body
     * @return bool
     */
    public function verifyToken(string $token, string $body): bool
    {
        if (!$body) {
            throw new \Exception("body must be a string or JSON object");
        }

        if (!$token) {
            throw new \Exception("no token provided");
        }

        $token = str_replace('Bearer', '', $token);
        $token = trim($token);
        $tokenArray = explode('.', $token);
        $keyArray = explode(':', $tokenArray[0]);
        $version = $keyArray[0];
        $keyId = $keyArray[1];
        $base64Payload = $tokenArray[1];
        $base64Signature = $tokenArray[2];
        if ($version !== $this->getVersion()) {
            throw new \Exception("token version does not match expected");
        }

        if ($keyId !== $this->getKeyId()) {
            throw new \Exception('keyId of token does not match expected');
        }

        try {
            $expiryPayload = base64_decode($base64Payload);
            $expiryPayload = \json_decode($expiryPayload);
        } catch (\Exception $e) {
            throw new \Exception('key exp not found within JSON payload');
        }

        if ($expiryPayload->iat < 9999) {
            throw new \Exception('key exp is not valid date');
        }
        $now = new \DateTime('NOW');
        $checkTime = $now->format(\DateTime::ATOM);
        $tokenExp = new \DateTime();
        $tokenExp->setTimestamp($expiryPayload->iat);
        $tokenExp = $tokenExp->format(\DateTime::ATOM);
        if ($checkTime > $tokenExp) {
            throw new \Exception('token is expired');
        }

        $signature = $this->getHashHmac($base64Payload, $body);
        $expectedSignature = base64_decode($base64Signature);
        if (!hash_equals($signature, $expectedSignature)) {
            throw new \Exception('signatures do not match');
        }
        return true;
    }

    /**
     * @param string $base64Payload
     * @param string $body
     * @return string
     */
    protected function getHashHmac(string $base64Payload, string $body): string
    {
        $signature = hash_init('sha256', HASH_HMAC, $this->getSecret());
        hash_update($signature, $base64Payload);
        hash_update($signature, $body);
        $signature = hash_final($signature, true);
        return $signature;
    }
}
