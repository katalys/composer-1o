<?php

namespace OneO\Model;

/**
 * PasetoToken class
 * @deprecated
 */
class PasetoToken
{
    /**
     * @var null|string
     */
    protected $token = null;

    /**
     * @param $sharedKey
     * @param string $footer
     * @param string $exp
     * @return string|null
     * @throws \ParagonIE\Paseto\Exception\InvalidKeyException
     * @throws \ParagonIE\Paseto\Exception\InvalidPurposeException
     * @throws \ParagonIE\Paseto\Exception\PasetoException
     * @deprecated
     */
    public function getSignedToken($sharedKey, string $footer = '', string $exp = 'P01D')
    {
        if (!$this->token) {
            $sharedKey = new \ParagonIE\Paseto\Keys\SymmetricKey(base64_decode($sharedKey), new \ParagonIE\Paseto\Protocol\Version2);
            $token = (new \ParagonIE\Paseto\Builder())
                ->setKey($sharedKey)
                ->setVersion(new \ParagonIE\Paseto\Protocol\Version2)
                ->setPurpose(\ParagonIE\Paseto\Purpose::local())
                ->setIssuedAt()
                ->setNotBefore()
                ->setExpiration((new \DateTime())->add(new \DateInterval($exp)))
                ->setFooter($footer);

            $this->token = $token->toString();
        }

        return $this->token;
    }

    /**
     * @param $token
     * @param $sharedKey
     * @param $footer
     * @return bool
     * @throws \ParagonIE\Paseto\Exception\PasetoException
     * @throws \SodiumException
     * @deprecated
     */
    public function verifyToken($token, $sharedKey, $footer)
    {
        $key = new \ParagonIE\Paseto\Keys\SymmetricKey(base64_decode($sharedKey), new \ParagonIE\Paseto\Protocol\Version2);
        $footer = \ParagonIE\ConstantTime\Base64UrlSafe::decode($footer);
        $decryptedToken = \ParagonIE\Paseto\Protocol\Version2::decrypt($token, $key, $footer);
        $rawDecryptedToken = json_decode($decryptedToken);

        if (is_object($rawDecryptedToken) && isset($rawDecryptedToken->exp)) {
            $checkTime = new \DateTime('NOW');
            $checkTime = $checkTime->format(\DateTime::ATOM);
            $tokenExp = new \DateTime($rawDecryptedToken->exp);
            $tokenExp = $tokenExp->format(\DateTime::ATOM);
            if ($checkTime > $tokenExp) {
                return false; // expired - do nothing else!!
            } else {
                return true; // trust token - not expired and has valid signature.
            }
        } else {
            return false;
        }
    }
}
