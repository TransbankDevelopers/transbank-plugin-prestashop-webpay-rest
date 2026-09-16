<?php

namespace PrestaShop\Module\WebpayPlus\Utils;

class Utils
{
    /**
     * Generates the Oneclick username with format {integratorCode}:{customerId}:{uid},
     * for example ps:42:1a0c8e5b3d7f2946. The uid is 16 hex characters from
     * random_bytes(8) (64 bits of randomness); it is not derived from the database or
     * from any personal data.
     *
     * @param int $customerId Internal ID of the authenticated customer
     *
     * @return string
     */
    public static function generateOneclickUsername(int $customerId): string
    {
        $integratorCode = 'ps';
        $uidByteLength = 8;

        $uid = bin2hex(random_bytes($uidByteLength));

        return sprintf('%s:%d:%s', $integratorCode, $customerId, $uid);
    }
}
