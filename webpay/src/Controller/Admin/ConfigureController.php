<?php

declare(strict_types=1);

namespace PrestaShop\Module\WebpayPlus\Controller\Admin;

use PrestaShop\Module\WebpayPlus\Config\OneclickConfig;
use Symfony\Component\HttpFoundation\Request;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithOneclick;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;
use Transbank\Plugin\Helpers\InfoUtil;
use Transbank\Plugin\Helpers\PrestashopInfoUtil;
use PrestaShop\Module\WebpayPlus\Grid\TransactionsFilters;
use PrestaShop\Module\WebpayPlus\Config\WebpayConfig;


class ConfigureController extends FrameworkBundleAdminController
{
    use InteractsWithOneclick;
    const TAB_CLASS_NAME = 'WebpayPlusConfigure';

    /** @Route("/webpay/configure", name="webpayplus") */
    public function webpayplusAction()
    {
        $webpayPlusFormDataHandler = $this->get('webpay.form.webpay_plus_form_data_handler');
        $webpayPlusForm = $webpayPlusFormDataHandler->getForm();

        return $this->render('@Modules/webpay/views/templates/admin/webpay_configure.html.twig', [
            'webpayPlusForm' => $webpayPlusForm->createView(),
            'enableSidebar' => true,
            'layoutTitle' => $this->trans('Configuración Webpay', 'Modules.WebpayPlus.Admin')
        ]);
    }

    /** @Route("/webpay/transaction-list", name="transactionList") */
    public function transactionListAction(
        Request $request,
        TransactionsFilters $transactionsFilters
    ) {
        $productGridFactory = $this->get('webpay.grid.transactions_grid_factory');
        $productGrid = $productGridFactory->getGrid($transactionsFilters);

        return $this->render('@Modules/webpay/views/templates/admin/transaction_list.html.twig', [
            'productsGrid' => $this->presentGrid($productGrid),
            'enableSidebar' => true,
            'layoutTitle' => 'Transacciones Webpay',
        ]);
    }

    /** @Route("/webpay/configure", name="oneclick") */
    public function oneclickAction()
    {
        $oneclickFormDataHandler = $this->get('webpay.form.oneclick_form_data_handler');
        $oneclickForm = $oneclickFormDataHandler->getForm();

        return $this->render('@Modules/webpay/views/templates/admin/oneclick_configure.html.twig', [
            'oneclickForm' => $oneclickForm->createView(),
            'enableSidebar' => true,
            'layoutTitle' => $this->trans('Configuración Webpay', 'Modules.WebpayPlus.Admin')
        ]);
    }

    /** @Route("/webpay/configure", name="diagnosis") */
    public function diagnosisAction()
    {
        $summary = InfoUtil::getSummary();
        $eSummary = PrestashopInfoUtil::getSummary();
        return $this->render('@Modules/webpay/views/templates/admin/diagnosis_configure.html.twig', [
            'enableSidebar' => true,
            'layoutTitle' => $this->trans('Configuración Webpay', 'Modules.WebpayPlus.Admin'),
            'summary' => $summary,
            'eSummary' => $eSummary
        ]);
    }

    /** @Route("/webpay/configure", name="logs") */
    public function logsAction()
    {
        $logger = TbkFactory::createLogger();
        $resume = $logger->getInfo();
        $lastLog = $logger->getLogDetail(basename($resume['last']));
        return $this->render('@Modules/webpay/views/templates/admin/logs_configure.html.twig', [
            'enableSidebar' => true,
            'layoutTitle' => $this->trans('Configuración Webpay', 'Modules.WebpayPlus.Admin'),
            'resume' => $resume,
            'lastLog' => $lastLog
        ]);
    }

    /** @Route("/webpay/configure", name="saveWebpayPlusForm") */
    public function saveWebpayPlusFormAction(Request $request): Response
    {
        $formDataHandler = $this->get('webpay.form.webpay_plus_form_data_handler');
        $form = $formDataHandler->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->getClickedButton() === $form->get('webpay_plus_form_reset_button')) {
                WebpayConfig::loadDefaultConfig();
                $this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));
            } elseif (!$form->isValid()) {
                foreach ($form->getErrors() as $error) {
                    $errors[] = $error->getMessage();
                }
                $this->flashErrors($errors);
            } elseif ($form->getClickedButton() === $form->get('webpay_plus_form_save_button')) {
                $errors = $formDataHandler->save($form->getData());
                if (empty($errors)) {
                    $this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));
                } else {
                    $this->flashErrors($errors);
                }
            }
        }

        return $this->redirectToRoute('ps_controller_webpay_configure_webpayplus');
    }

    /** @Route("/webpay/configure", name="saveOneclickForm") */
    public function saveOneclickFormAction(Request $request): Response
    {
        $formDataHandler = $this->get('webpay.form.oneclick_form_data_handler');
        $form = $formDataHandler->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->getClickedButton() === $form->get('oneclick_form_reset_button')) {
                OneclickConfig::loadDefaultConfig();
                $this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));
            } elseif (!$form->isValid()) {
                foreach ($form->getErrors() as $error) {
                    $errors[] = $error->getMessage();
                }
                $this->flashErrors($errors);
            } elseif ($form->getClickedButton() === $form->get('oneclick_form_save_button')) {
                $errors = $formDataHandler->save($form->getData());
                if (empty($errors)) {
                    $this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));
                } else {
                    $this->flashErrors($errors);
                }
            }
        }

        return $this->redirectToRoute('ps_controller_webpay_configure_oneclick');
    }

}
