<?php

namespace PrestaShop\Module\WebpayPlus\Hooks;

use Module;
use Context;
use Transbank\Plugin\Helpers\TbkConstants;
use PrestaShop\Module\WebpayPlus\Utils\Template;
use PrestaShop\Module\WebpayPlus\Hooks\AbstractHookHandler;

/**
 * Class DisplayCustomerAccount
 *
 * This class is responsible for displaying Webpay account information and transaction links
 * in the customer account area. It renders a link to view the customer's transaction history
 * and other account-related information for the Webpay module.
 */
class DisplayCustomerAccount extends AbstractHookHandler
{
    /**
     * Constructor.
     * Initializes the class.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Executes the hook logic to display customer account information.
     *
     * @param array $params The parameters passed to the hook.
     * @return string|null Rendered template as a string, or null if there's an error.
     */
    public function execute(array $params): ?string
    {
        $this->logInfo('Ejecutando hook DisplayCustomerAccount');

        $module = $params['module'] ?? Module::getInstanceByName('webpay');
        $context = $params['context'] ?? Context::getContext();

        $this->logInfo('Hook DisplayCustomerAccount ejecutado correctamente');

        $context->smarty->assign([
            'oneclickCardsLink' => $context->link->getModuleLink(
                TbkConstants::MODULE_NAME,
                'oneclickcards'
            ),
        ]);
        return $context->smarty->fetch("module:{$module->name}/views/templates/hook/displayCustomerAccount.tpl");
    }
}
