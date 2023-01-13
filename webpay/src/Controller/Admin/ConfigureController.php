<?php

declare(strict_types=1);

namespace PrestaShop\Module\WebpayPlus\Controller\Admin;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PrestaShop\Module\WebpayPlus\Utils\HealthCheck;
use PrestaShop\Module\WebpayPlus\Utils\LogHandler;

class ConfigureController extends FrameworkBundleAdminController
{
    const TAB_CLASS_NAME = 'WebpayPlusConfigure';

    public function webpayplus(Request $request)
    {
        $webpayPlusFormDataHandler = $this->get('webpay.form.webpay_plus_form_data_handler');
        $webpayPlusForm = $webpayPlusFormDataHandler->getForm();

        return $this->render('@Modules/webpay/views/templates/admin/webpay_configure.html.twig', [
            'webpayPlusForm' => $webpayPlusForm->createView(),
            'enableSidebar' => true,
            'layoutTitle' => $this->trans('Configuración Webpay', 'Modules.WebpayPlus.Admin')
        ]);
    }

    public function oneclick(Request $request)
    {
        $oneclickFormDataHandler = $this->get('webpay.form.oneclick_form_data_handler');
        $oneclickForm = $oneclickFormDataHandler->getForm();

        return $this->render('@Modules/webpay/views/templates/admin/oneclick_configure.html.twig', [
            'oneclickForm' => $oneclickForm->createView(),
            'enableSidebar' => true,
            'layoutTitle' => $this->trans('Configuración Webpay', 'Modules.WebpayPlus.Admin')
        ]);
    }

    public function diagnosis(Request $request)
    {
        $diagnosisFormDataHandler = $this->get('webpay.form.diagnosis_form_data_handler');
        $diagnosisForm = $diagnosisFormDataHandler->getForm();

        $healthcheck = new HealthCheck(array(
            'ENVIRONMENT' => 1,
            'COMMERCE_CODE' => 2,
            'API_KEY_SECRET' => 3,
            'ECOMMERCE' => 'prestashop'
        ));
        $data = $healthcheck->getFullResume();

        return $this->render('@Modules/webpay/views/templates/admin/diagnosis_configure.html.twig', [
            'diagnosisForm' => $diagnosisForm->createView(),
            'enableSidebar' => true,
            'layoutTitle' => $this->trans('Configuración Webpay', 'Modules.WebpayPlus.Admin'),
            'data' => $data
        ]);
    }

    public function logs(Request $request)
    {
        $log = new LogHandler();
        $res = $log->getResumeBase();
        $lastLog = $log->setLastLog();
        
        return $this->render('@Modules/webpay/views/templates/admin/logs_configure.html.twig', [
            'enableSidebar' => true,
            'layoutTitle' => $this->trans('Configuración Webpay', 'Modules.WebpayPlus.Admin'),
            'resume' => $res,
            'lastLog' => $lastLog,
            'logsList' => json_encode($res['logs_list'], JSON_PRETTY_PRINT)
        ]);
    }

    public function info(Request $request)
    {
        return $this->render('@Modules/webpay/views/templates/admin/info_configure.html.twig', [
            'enableSidebar' => true,
            'layoutTitle' => $this->trans('Configuración Webpay', 'Modules.WebpayPlus.Admin'),
            
        ]);
    }

    public function saveWebpayPlusForm(Request $request): Response
    {
        $formDataHandler = $this->get('webpay.form.webpay_plus_form_data_handler');
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

        return $this->redirectToRoute('ps_controller_webpay_configure_webpayplus');
    }

    public function saveOneclickForm(Request $request): Response
    {
        $formDataHandler = $this->get('webpay.form.oneclick_form_data_handler');
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

        return $this->redirectToRoute('ps_controller_webpay_configure_oneclick');
    }

    public function saveDiagnosisForm(Request $request): Response
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
