<?php

declare(strict_types=1);

namespace PrestaShop\Module\WebpayPlus\Controller\Admin;

use Exception;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PrestaShop\Module\WebpayPlus\Utils\LogHandler;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithWebpay;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithOneclick;
use PrestaShop\Module\WebpayPlus\Utils\MetricsUtil;
use Configuration;
use Transbank\Plugin\Helpers\InfoUtil;
use Transbank\Plugin\Helpers\TbkConstans;
use Transbank\Webpay\Options;

class ConfigureController extends FrameworkBundleAdminController
{
    use InteractsWithWebpay;
    use InteractsWithOneclick;
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
        $data = InfoUtil::getResume(TbkConstans::ECOMMERCE_PRESTASHOP);

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
            'lastLog' => $lastLog
        ]);
    }

    public function info(Request $request)
    {

        ob_start();
        phpinfo();
        $phpinfo = ob_get_contents();
        ob_end_clean();
        $phpinfo = preg_replace('~<style(.*?)</style>~Usi', "", $phpinfo); 
        return $this->render('@Modules/webpay/views/templates/admin/info_configure.html.twig', [
            'enableSidebar' => true,
            'phpinfo' => $phpinfo,
            'layoutTitle' => $this->trans('Configuración Webpay', 'Modules.WebpayPlus.Admin'),
            
        ]);
    }

    public function saveWebpayPlusForm(Request $request): Response
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
                    $this->sendMetrics('webpay', $this->getWebpayEnvironment());
                    $this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));
                } else {
                    $this->flashErrors($errors);
                }
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
                    $this->sendMetrics('oneclick', $this->getOneclickEnvironment());
                    $this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));
                } else {
                    $this->flashErrors($errors);
                }
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

    private function sendMetrics($product, $enviroment) {
        if ($enviroment === Options::ENVIRONMENT_INTEGRATION)
        {
            return;
        }
        $info = InfoUtil::getResume(TbkConstans::ECOMMERCE_PRESTASHOP);
        //$shops = Shop::getShops();
        return MetricsUtil::sendMetrics(
            $info['php']['version'],//$phpVersion, 
            'prestashop',//$plugin, 
            $info['commerce_info']['current_plugin_version'],//$pluginVersion
            $info['commerce_info']['current_ecommerce_version'],//$ecommerceVersion
            1,//$ecommerceId, 
            $product, 
            $enviroment, 
            $this->getWebpayCommerceCode(),//$commerceCode
            $this->getMeta()
        );
    }

    private function getMeta(){
        return [
            'PS_SHOP_NAME' => Configuration::get('PS_SHOP_NAME'),
            'PS_SHOP_EMAIL' => Configuration::get('PS_SHOP_EMAIL'),
            'PS_SHOP_PHONE' => Configuration::get('PS_SHOP_PHONE'),
            'systemInformationSummary' => $this->getSystemInformationSummary()
        ];
    }    

    /**
     * @return \PrestaShop\PrestaShop\Adapter\System\SystemInformation
     */
    private function getSystemInformationSummary()
    {
        /* Ejemplo de data entregada por el objeto */
        /*{
            "notHostMode":true,
            "server":{"version":"Apache\/2.4.52 (Debian)","php":{"version":"7.4.28","memoryLimit":"256M","maxExecutionTime":"30","maxFileSizeUpload":"20M"}},
            "instaWebInstalled":false,"uname":"Linux #1 SMP Fri Apr 2 22:23:49 UTC 2021 x86_64",
            "database":{"version":"10.8.3-MariaDB-1:10.8.3+maria~jammy","server":"webpay_mariadb_1.7.8.5-7.4:3306","name":"prestashop","user":"root","prefix":"ps_","engine":"InnoDB","driver":"DbPDO"},
            "overrides":[],
            "shop":{"version":"1.7.8.5","url":"http:\/\/localhost:8080\/","path":"\/var\/www\/html","theme":"classic"},
            "isNativePHPmail":true,
            "smtp":{"server":"smtp.","user":"","password":"","encryption":"off","port":"25"}
          }
        */
        try {
            return $this->get('prestashop.adapter.system_information')->getSummary();
        } catch (Exception $ex) {
            return 'No compatible';
        }
        
    }

}
