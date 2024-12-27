<?php

namespace PrestaShop\Module\WebpayPlus\Utils;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Template
{
    const TEMPLATE_PATH = _PS_MODULE_DIR_ . 'webpay/views/templates/';
    private $twig;

    public function __construct() {
        $loader = new FilesystemLoader(self::TEMPLATE_PATH);
        $this->twig = new Environment($loader);
    }

    public function render(string $name, array $parameters = []): string {
        return $this->twig->render($name, $parameters);
    }
}
