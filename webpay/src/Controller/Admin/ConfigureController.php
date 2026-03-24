<?php

declare(strict_types=1);

namespace PrestaShop\Module\WebpayPlus\Controller\Admin;

use PrestaShop\Module\WebpayPlus\Config\OneclickConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;
use Transbank\Plugin\Helpers\InfoUtil;
use Transbank\Plugin\Helpers\PrestashopInfoUtil;
use PrestaShop\Module\WebpayPlus\Grid\TransactionsFilters;
use PrestaShop\Module\WebpayPlus\Config\WebpayConfig;
use PrestaShopBundle\Controller\Admin\PrestaShopAdminController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use PrestaShop\PrestaShop\Core\Form\FormHandlerInterface;
use PrestaShop\PrestaShop\Core\Grid\GridFactoryInterface;

class ConfigureController extends PrestaShopAdminController
{
    const TAB_CLASS_NAME = 'WebpayPlusConfigure';
    const LAYOUT_TITLE = 'Configuración Webpay';
    const SUCCESSFUL_UPDATE = 'Successful update.';

    /** @Route("/webpay/configure", name="webpayplus") */
    public function webpayplusAction(
        #[Autowire(service: 'webpay.form.webpay_plus_form_data_handler')]
        FormHandlerInterface $webpayPlusFormDataHandler,
    ): Response {
        $webpayPlusForm = $webpayPlusFormDataHandler->getForm();

        return $this->render('@Modules/webpay/views/templates/admin/webpay_configure.html.twig', [
            'webpayPlusForm' => $webpayPlusForm->createView(),
            'enableSidebar' => true,
            'layoutTitle' => $this->trans(self::LAYOUT_TITLE, [], 'Modules.WebpayPlus.Admin')
        ]);
    }

    /** @Route("/webpay/transaction-list", name="transactionList") */
    public function transactionListAction(
        Request $request,
        TransactionsFilters $transactionsFilters,
        #[Autowire(service: 'webpay.grid.transactions_grid_factory')]
        GridFactoryInterface $productGridFactory,
    ): Response {
        $productGrid = $productGridFactory->getGrid($transactionsFilters);

        return $this->render('@Modules/webpay/views/templates/admin/transaction_list.html.twig', [
            'productsGrid' => $this->presentGrid($productGrid),
            'enableSidebar' => true,
            'layoutTitle' => 'Transacciones Webpay',
        ]);
    }

    /** @Route("/webpay/configure", name="oneclick") */
    public function oneclickAction(
        #[Autowire(service: 'webpay.form.oneclick_form_data_handler')]
        FormHandlerInterface $oneclickFormDataHandler,
    ): Response {
        $oneclickForm = $oneclickFormDataHandler->getForm();

        return $this->render('@Modules/webpay/views/templates/admin/oneclick_configure.html.twig', [
            'oneclickForm' => $oneclickForm->createView(),
            'enableSidebar' => true,
            'layoutTitle' => $this->trans(self::LAYOUT_TITLE, [], 'Modules.WebpayPlus.Admin')
        ]);
    }

    /** @Route("/webpay/configure", name="diagnosis") */
    public function diagnosisAction(): Response
    {
        $summary = InfoUtil::getSummary();
        $eSummary = PrestashopInfoUtil::getSummary();
        return $this->render('@Modules/webpay/views/templates/admin/diagnosis_configure.html.twig', [
            'enableSidebar' => true,
            'layoutTitle' => $this->trans(self::LAYOUT_TITLE, [], 'Modules.WebpayPlus.Admin'),
            'summary' => $summary,
            'eSummary' => $eSummary
        ]);
    }

    /** @Route("/webpay/configure", name="logs") */
    public function logsAction(): Response
    {
        $logger = TbkFactory::createLogger();
        $resume = $logger->getInfo();
        $lastLog = $logger->getLogDetail(basename($resume['last']));
        return $this->render('@Modules/webpay/views/templates/admin/logs_configure.html.twig', [
            'enableSidebar' => true,
            'layoutTitle' => $this->trans(self::LAYOUT_TITLE, [], 'Modules.WebpayPlus.Admin'),
            'resume' => $resume,
            'lastLog' => $lastLog
        ]);
    }

    /** @Route("/webpay/configure", name="saveWebpayPlusForm") */
    public function saveWebpayPlusFormAction(
        Request $request,
        #[Autowire(service: 'webpay.form.webpay_plus_form_data_handler')]
        FormHandlerInterface $formDataHandler,
    ): Response {
        $this->handleFormSubmission(
            $request,
            $formDataHandler,
            'webpay_plus_form_reset_button',
            'webpay_plus_form_save_button',
            fn() => WebpayConfig::loadDefaultConfig()
        );

        return $this->redirectToRoute('ps_controller_webpay_configure_webpayplus');
    }

    /** @Route("/webpay/configure", name="saveOneclickForm") */
    public function saveOneclickFormAction(
        Request $request,
        #[Autowire(service: 'webpay.form.oneclick_form_data_handler')]
        FormHandlerInterface $formDataHandler,
    ): Response {
        $this->handleFormSubmission(
            $request,
            $formDataHandler,
            'oneclick_form_reset_button',
            'oneclick_form_save_button',
            fn() => OneclickConfig::loadDefaultConfig()
        );

        return $this->redirectToRoute('ps_controller_webpay_configure_oneclick');
    }

    private function handleFormSubmission(
        Request $request,
        FormHandlerInterface $formDataHandler,
        string $resetButtonName,
        string $saveButtonName,
        callable $onReset
    ): void {
        $form = $formDataHandler->getForm();
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return;
        }

        if ($form->getClickedButton() === $form->get($resetButtonName)) {
            $onReset();
            $this->addFlash('success', $this->trans(self::SUCCESSFUL_UPDATE, [], 'Admin.Notifications.Success'));
            return;
        }

        if (!$form->isValid()) {
            $errors = [];
            foreach ($form->getErrors() as $error) {
                $errors[] = $error->getMessage();
            }

            $this->addFlashErrors($errors);
            return;
        }

        if ($form->getClickedButton() === $form->get($saveButtonName)) {
            $errors = $formDataHandler->save($form->getData());
            if (empty($errors)) {
                $this->addFlash('success', $this->trans(self::SUCCESSFUL_UPDATE, [], 'Admin.Notifications.Success'));
            } else {
                $this->addFlashErrors($errors);
            }
        }
    }
}
