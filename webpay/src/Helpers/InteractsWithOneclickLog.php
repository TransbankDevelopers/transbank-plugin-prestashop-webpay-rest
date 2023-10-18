<?php

namespace PrestaShop\Module\WebpayPlus\Helpers;

trait InteractsWithOneclickLog
{

    public function logOneclickInstallConfigLoad($oneclickMallCommerceCode,
        $oneclickChildCommerceCode, $oneclickDefaultOrderStateIdAfterPayment){
        $this->logInfo('Configuración de ONECLICK se cargo de forma correcta');
        $this->logInfo('oneclickMallCommerceCode: '.$oneclickMallCommerceCode.
            ', oneclickChildCommerceCode: '.$oneclickChildCommerceCode.
            ', oneclickDefaultOrderStateIdAfterPayment: '.$oneclickDefaultOrderStateIdAfterPayment);
    }

    public function logOneclickInstallConfigLoadDefault(){
        $this->logInfo('Configuración por defecto de ONECLICK se cargo de forma correcta');
    }

    public function logOneclickInstallConfigLoadDefaultPorIncompleta(){
        $this->logInfo('Configuración por defecto de ONECLICK se cargo de
            forma correcta porque los valores de producción estan incompletos');
    }

    public function logOneclickConfigError(){
        $this->logError('Configuración de ONECLICK incorrecta, revise los valores');
    }

    public function logOneclickPaymentIniciando(){
        if($this->getDebugActive()==1){
            $this->logInfo('B.1. Iniciando medio de pago Oneclick');
        }
    }

    public function logOneclickPaymentAntesObtenerInscripcion($inscriptionId, $cartId, $amount){
        if($this->getDebugActive()==1){
            $this->logInfo('B.2. Antes de obtener inscripción de la BD => inscriptionId: '
                .$inscriptionId.', cartId: '.$cartId.', amount: '.$amount);
        }
    }

    public function logOneclickPaymentDespuesObtenerInscripcion($inscriptionId, $ins){
        if($this->getDebugActive()==1){
            $this->logInfo('B.2. Despues de obtener inscripción de la BD => inscriptionId: '.$inscriptionId);
            $this->logInfo(json_encode($ins));
        }
    }

    public function logOneclickPaymentAntesCrearTxBd($inscriptionId, $transaction){
        if($this->getDebugActive()==1){
            $this->logInfo('B.3. Preparando datos antes de crear la transacción en BD => inscriptionId: '
                .$inscriptionId);
            $this->logInfo(json_encode($transaction));
        }
    }

    public function logOneclickPaymentCrearTxBdError($inscriptionId, $transaction){
        $this->logError('B.4. No se pudo crear la transacción en BD => inscriptionId: '.$inscriptionId);
        $this->logError(json_encode($transaction));
    }

    public function logOneclickPaymentAntesAutorizarTx($username, $tbkToken, $parentBuyOrder, $childBuyOrder, $amount){
        if($this->getDebugActive()==1){
            $this->logInfo('B.4. Preparando datos antes de autorizar la transacción en Transbank');
            $this->logInfo('username: '.$username.', tbkToken: '.$tbkToken.', parentBuyOrder: '
                .$parentBuyOrder.', childBuyOrder: '.$childBuyOrder.', amount: '.$amount);
        }
    }

    public function logOneclickPaymentDespuesAutorizarTx($username, $tbkToken, $parentBuyOrder,
        $childBuyOrder, $amount, $result){
        $this->logInfo('B.5. Transacción con autorización en Transbank => username: '.$username.', tbkToken: '
            .$tbkToken.', parentBuyOrder: '.$parentBuyOrder.', childBuyOrder: '.$childBuyOrder.', amount: '.$amount);
        $this->logInfo(json_encode($result));
        if (!is_array($result) && $result->isApproved()){
            $this->logInfo('***** AUTORIZADO POR TBK OK *****');
            $this->logInfo('TRANSACCION VALIDADA POR TBK => username: '.$username.', tbkToken: '.$tbkToken.
                ', parentBuyOrder: '.$parentBuyOrder.', childBuyOrder: '.$childBuyOrder.', amount: '.$amount);
            $this->logInfo('SI NO SE ENCUENTRA VALIDACION POR PRESTASHOP DEBE ANULARSE');
        }
    }

    public function logOneclickPaymentDespuesAutorizarTxError($parentBuyOrder, $childBuyOrder, $result){
        $this->logError('B.6. Transacción con autorización con error => parentBuyOrder: '
            .$parentBuyOrder.', childBuyOrder: '.$childBuyOrder);
        $this->logError(json_encode($result));
    }

    public function logOneclickPaymentDespuesAutorizarRechazadoTxError($parentBuyOrder, $childBuyOrder, $result){
        $this->logError('B.6. Transacción con autorización rechazada => parentBuyOrder: '
            .$parentBuyOrder.', childBuyOrder: '.$childBuyOrder);
        $this->logError(json_encode($result));
    }

    public function logOneclickPaymentDespuesValidateOrderPrestashop($inscriptionId, $webpayTransaction){
        if($this->getDebugActive()==1){
            $this->logInfo('B.7. Procesando pago despues de validateOrder => inscriptionId: '.$inscriptionId);
            $this->logInfo(json_encode($webpayTransaction));
        }
    }

    public function logOneclickPaymentTodoOk($inscriptionId, $webpayTransaction){
        $this->logInfo('***** TODO OK *****');
        $this->logInfo('TRANSACCION VALIDADA POR PRESTASHOP Y POR TBK EN ESTADO STATUS_APPROVED => INSCRIPTION_ID: '
            .$inscriptionId);
        $this->logInfo(json_encode($webpayTransaction));
    }
}
