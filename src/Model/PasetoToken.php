<?php

namespace oneO\Model;

class PasetoToken
{
    protected $token = null;

    public function getSignedToken($sharedKey, string $footer = '', string $exp = 'P01D')
    {
        if (!$this->token) {
            $sharedKey = new \ParagonIE\Paseto\Keys\SymmetricKey($sharedKey);
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
}