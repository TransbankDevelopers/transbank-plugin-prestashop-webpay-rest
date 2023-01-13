<?php

declare(strict_types=1);

namespace PrestaShop\Module\WebpayPlus\Controller\Admin;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShop\Module\WebpayPlus\Grid\Definition\Factory\TransactionGridDefinitionFactory;
use PrestaShop\Module\WebpayPlus\Grid\Filters\TransactionFilters;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OrdersController extends FrameworkBundleAdminController
{
    const TAB_CLASS_NAME = 'WebpayPlusOrders';


    /**
     * List transactions
     *
     * @param TransactionFilters $filters
     *
     * @return Response
     */
    public function indexAction(TransactionFilters $filters)
    {
        $gridFactory = $this->get('webpay.grid.factory.transactions');
        $grid = $gridFactory->getGrid($filters);

        return $this->render('@Modules/webpay/views/templates/admin/transaction_grid.html.twig',
        [
            'enableSidebar' => true,
            'layoutTitle' => $this->trans('Listado de órdenes Webpay', 'Modules.WebpayPlus.Admin'),
            'transactionGrid' => $this->presentGrid($grid),
        ]);
    }

    /**
     * Provides filters functionality.
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function searchAction(Request $request)
    {
        $responseBuilder = $this->get('prestashop.bundle.grid.response_builder');

        return $responseBuilder->buildSearchResponse(
            $this->get('webpay.grid.definition.factory.transactions'),
            $request,
            TransactionGridDefinitionFactory::GRID_ID,
            'ps_controller_webpay_orders'
        );
    }
}
