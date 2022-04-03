<?php

namespace OneO\Model;

class PasetoToken
{
    protected $token = null;

    public function getSignedToken($sharedKey, string $footer = '', string $exp = 'P01D')
    {
        if (!$this->token) {
            $sharedKey = new \ParagonIE\Paseto\Keys\SymmetricKey(base64_decode($sharedKey));
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
    
    public function verifyToken($token, $sharedKey, $footer)
    {
        $key = new \ParagonIE\Paseto\Keys\SymmetricKey(base64_decode($sharedKey));
        $decryptedToken = \ParagonIE\Paseto\Protocol\Version2::decrypt($token, $key, $footer);
        $rawDecryptedToken = json_decode($decryptedToken);
        if (is_object($rawDecryptedToken) && isset($rawDecryptedToken->exp)) {
            $checkTime = new \DateTime('NOW');
            $checkTime = $checkTime->format(\DateTime::ATOM);
            $tokenExp = new \DateTime($rawDecryptedToken->exp);
            $tokenExp = $tokenExp->format(\DateTime::ATOM);
            if ($checkTime > $tokenExp) {
                return true; // expired - do nothing else!!
            } else {
                return false; // trust token - not expired and has valid signature.
            }
        } else {
            return true;
        }
    }
}