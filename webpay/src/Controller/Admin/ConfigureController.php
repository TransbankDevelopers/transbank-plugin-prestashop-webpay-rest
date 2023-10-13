<?php

declare(strict_types=1);

namespace PrestaShop\Module\WebpayPlus\Controller\Admin;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Response;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithWebpay;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithOneclick;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;
use Transbank\Plugin\Helpers\InfoUtil;
use Transbank\Plugin\Helpers\PrestashopInfoUtil;


class ConfigureController extends FrameworkBundleAdminController
{
    use InteractsWithWebpay;
    use InteractsWithOneclick;
    const TAB_CLASS_NAME = 'WebpayPlusConfigure';

    public function webpayplus()
    {
        $webpayPlusFormDataHandler = $this->get('webpay.form.webpay_plus_form_data_handler');
        $webpayPlusForm = $webpayPlusFormDataHandler->getForm();

        return $this->render('@Modules/webpay/views/templates/admin/webpay_configure.html.twig', [
            'webpayPlusForm' => $webpayPlusForm->createView(),
            'enableSidebar' => true,
            'layoutTitle' => $this->trans('Configuración Webpay', 'Modules.WebpayPlus.Admin')
        ]);
    }

    public function oneclick()
    {
        $oneclickFormDataHandler = $this->get('webpay.form.oneclick_form_data_handler');
        $oneclickForm = $oneclickFormDataHandler->getForm();

        return $this->render('@Modules/webpay/views/templates/admin/oneclick_configure.html.twig', [
            'oneclickForm' => $oneclickForm->createView(),
            'enableSidebar' => true,
            'layoutTitle' => $this->trans('Configuración Webpay', 'Modules.WebpayPlus.Admin')
        ]);
    }

    public function diagnosis()
    {
        $diagnosisFormDataHandler = $this->get('webpay.form.diagnosis_form_data_handler');
        $diagnosisForm = $diagnosisFormDataHandler->getForm();
        $summary = InfoUtil::getSummary();
        $eSummary = PrestashopInfoUtil::getSummary();
        return $this->render('@Modules/webpay/views/templates/admin/diagnosis_configure.html.twig', [
            'diagnosisForm' => $diagnosisForm->createView(),
            'enableSidebar' => true,
            'layoutTitle' => $this->trans('Configuración Webpay', 'Modules.WebpayPlus.Admin'),
            'summary' => $summary,
            'eSummary' => $eSummary
        ]);
    }

    public function logs()
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

    public function info()
    {
        $phpInfo = InfoUtil::getPhpInfo();
        return $this->render('@Modules/webpay/views/templates/admin/info_configure.html.twig', [
            'enableSidebar' => true,
            'content' => $phpInfo['content'],
            'layoutTitle' => $this->trans('Configuración Webpay', 'Modules.WebpayPlus.Admin'),
            
        ]);
    }

    public function saveWebpayPlusForm(): Response
    {
        $formDataHandler = $this->get('webpay.form.webpay_plus_form_data_handler');
        $form = $formDataHandler->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->getClickedButton() === $form->get('webpay_plus_form_reset_button')){
                $this->loadDefaultWebpay();
                $this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));
            }
            else if (!$form->isValid()){
                foreach ($form->getErrors() as $key => $error) {
                    $errors[] = $error->getMessage();
                }
                $this->flashErrors($errors);
            }
            else if ($form->getClickedButton() === $form->get('webpay_plus_form_save_button')){
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

    public function saveOneclickForm(): Response
    {
        $formDataHandler = $this->get('webpay.form.oneclick_form_data_handler');
        $form = $formDataHandler->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->getClickedButton() === $form->get('oneclick_form_reset_button')){
                $this->loadDefaultOneclick();
                $this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));
            }
            else if (!$form->isValid()){
                foreach ($form->getErrors() as $key => $error) {
                    $errors[] = $error->getMessage();
                }
                $this->flashErrors($errors);
            }
            else if ($form->getClickedButton() === $form->get('oneclick_form_save_button')){
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

    public function saveDiagnosisForm(): Response
    {
        $formDataHandler = $this->get('webpay.form.diagnosis_form_data_handler');
        $form = $formDataHandler->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $errors = $formDataHandler->save($form->getData());

            if (empty($errors)) {
                $this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));
            } else {
                $this->flashErrors($errors);
            }
        }

        return $this->redirectToRoute('ps_controller_webpay_configure_diagnosis');
    }

}
