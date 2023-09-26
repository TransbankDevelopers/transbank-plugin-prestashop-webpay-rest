<?php

declare(strict_types=1);

namespace PrestaShop\Module\WebpayPlus\Controller\Admin;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;

class ConfigureController extends FrameworkBundleAdminController
{
    const TAB_CLASS_NAME = 'WebpayPlusConfigure';

    public function configure(Request $request)
    {
        return $this->render('@Modules/webpay/views/templates/admin/configure.html.twig', []);
    }
}
