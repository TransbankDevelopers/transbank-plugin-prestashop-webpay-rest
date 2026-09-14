<?php

namespace PrestaShop\Module\WebpayPlus\Utils;

class Utils
{
    /**
     * Generate a random string to be used as identifier
     *
     * @param int $length Length of the random string to generate
     *
     * @return string Randomly generated string
     */
    public function generateSecureId($length = 32)
    {
        return bin2hex(random_bytes((int) ceil($length / 2)));
    }
}
