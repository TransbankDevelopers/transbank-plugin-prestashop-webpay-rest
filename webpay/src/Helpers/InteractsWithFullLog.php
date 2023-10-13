<?php

namespace PrestaShop\Module\WebpayPlus\Helpers;


/**
 * Trait InteractsWithFullLog.
 */
trait InteractsWithFullLog
{
    /* Logs para la instalación WEBPAY */

    public function logWebpayPlusInstallConfigLoad($webpayCommerceCode, $webpayDefaultOrderStateIdAfterPayment){
        $this->logInfo('Configuración de WEBPAY PLUS se cargo de forma correcta');
        $this->logInfo('webpayCommerceCode: '.$webpayCommerceCode.', webpayDefaultOrderStateIdAfterPayment: '.$webpayDefaultOrderStateIdAfterPayment);
    }

    public function logWebpayPlusInstallConfigLoadDefault(){
        $this->logInfo('Configuración por defecto de WEBPAY PLUS se cargo de forma correcta');
    }

    public function logWebpayPlusInstallConfigLoadDefaultPorIncompleta(){
        $this->logInfo('Configuración por defecto de WEBPAY PLUS se cargo de forma correcta porque los valores de producción estan incompletos');
    }

    /* Logs para la instalación ONECLICK */

    public function logOneclickInstallConfigLoad($oneclickMallCommerceCode, $oneclickChildCommerceCode, $oneclickDefaultOrderStateIdAfterPayment){
        $this->logInfo('Configuración de ONECLICK se cargo de forma correcta');
        $this->logInfo('oneclickMallCommerceCode: '.$oneclickMallCommerceCode.', oneclickChildCommerceCode: '.$oneclickChildCommerceCode.', oneclickDefaultOrderStateIdAfterPayment: '.$oneclickDefaultOrderStateIdAfterPayment);
    }

    public function logOneclickInstallConfigLoadDefault(){
        $this->logInfo('Configuración por defecto de ONECLICK se cargo de forma correcta');
    }

    public function logOneclickInstallConfigLoadDefaultPorIncompleta(){
        $this->logInfo('Configuración por defecto de ONECLICK se cargo de forma correcta porque los valores de producción estan incompletos');
    }

    /* LOGS PARA WEBPAY PLUS */

    public function logWebpayPlusConfigError(){
        $this->logError('Configuración de WEBPAY PLUS incorrecta, revise los valores');
    }

    public function logWebpayPlusIniciando(){
        if($this->getDebugActive()==1){
            $this->logInfo('B.1. Iniciando medio de pago Webpay Plus');
        }
    }

    public function logWebpayPlusAntesCrearTx($amount, $sessionId, $buyOrder, $returnUrl){
        if($this->getDebugActive()==1){
            $this->logInfo('B.2. Preparando datos antes de crear la transacción en Transbank');
            $this->logInfo('amount: '.$amount.', sessionId: '.$sessionId.', buyOrder: '.$buyOrder.', returnUrl: '.$returnUrl);
        }
    }

    public function logWebpayPlusDespuesCrearTx($result){
        if($this->getDebugActive()==1){
            $this->logInfo('B.3. Transacción creada en Transbank');
            $this->logInfo(json_encode($result));
        }
    }

    public function logWebpayPlusDespuesCrearTxError($result){
        $this->logError('B.3. Transacción creada con error en Transbank');
        $this->logError(json_encode($result));
    }

    public function logWebpayPlusAntesCrearTxEnTabla($transaction){
        if($this->getDebugActive()==1){
            $this->logInfo('B.4. Preparando datos antes de crear la transacción en la tabla webpay_transactions');
            $this->logInfo(json_encode($transaction));
        }
    }

    public function logWebpayPlusDespuesCrearTxEnTabla($transaction){
        if($this->getDebugActive()==1){
            $this->logInfo('B.5. Transacción creada en la tabla webpay_transactions');
            $this->logInfo(json_encode($transaction));
        }
    }

    public function logWebpayPlusDespuesCrearTxEnTablaError($transaction){
        $this->logError('B.5. Transacción no se pudo crear en la tabla webpay_transactions => ');
        $this->logError(json_encode($transaction));
    }

    public function logWebpayPlusRetornandoDesdeTbk($method, $params){
        if($this->getDebugActive()==1){
            $this->logInfo('C.1. Iniciando validación luego de redirección desde tbk => method: '.$method);
            $this->logInfo(json_encode($params));
        }
    }
    
    public function logWebpayPlusDespuesObtenerTx($token, $tx){
        if($this->getDebugActive()==1){
            $this->logInfo('C.2. Tx obtenido desde la tabla webpay_transactions => token: '.$token);
            $this->logInfo(json_encode($tx));
        }
    }

    public function logWebpayPlusRetornandoDesdeTbkFujo2Error($tbkIdSesion){
        $this->logError('C.2. Error tipo Flujo 2: El pago fue anulado por tiempo de espera => tbkIdSesion: '.$tbkIdSesion);
    }

    public function logWebpayPlusRetornandoDesdeTbkFujo3Error($tbktoken){
        $this->logError('C.2. Error tipo Flujo 3: El pago fue anulado por el usuario => tbktoken: '.$tbktoken);
    }
    public function logWebpayPlusRetornandoDesdeTbkFujo3TxError($tbktoken, $webpayTransaction){
        $this->logError('C.2. Error tipo Flujo 3 => tbktoken: '.$tbktoken);
        $this->logError(json_encode($webpayTransaction));
    }

    public function logWebpayPlusRetornandoDesdeTbkFujo4Error($tokenWs, $tbktoken){
        $this->logError('C.2. Error tipo Flujo 4: El pago es inválido  => tokenWs: '.$tokenWs.', tbktoken: '.$tbktoken);
    }

    public function logWebpayPlusAntesCommitTx($token, $tx, $cart){
        if($this->getDebugActive()==1){
            $this->logInfo('C.3. Transaccion antes del commit  => token: '.$token);
            $this->logInfo(json_encode($tx));
            $this->logPrintCart($cart);
        }
    }

    public function logWebpayPlusCommitTxYaAprobadoError($token, $tx){
        $this->logError('C.3. Transacción ya estaba aprobada => token: '.$token);
        $this->logError(json_encode($tx));
    }

    public function logWebpayPlusCommitTxNoInicializadoError($token, $tx){
        $this->logError('C.3. Transacción se encuentra en estado rechazado o cancelado => token: '.$token);
        $this->logError(json_encode($tx));
    }

    public function logWebpayPlusCommitTxCarroAprobadoError($token, $tx){
        $this->logError('C.3. El carro de compras ya fue pagado con otra Transacción => token: '.$token);
        $this->logError(json_encode($tx));
    }

    public function logWebpayPlusCommitTxCarroManipuladoError($token, $tx){
        $this->logError('C.3. El carro de compras ya fue pagado con otra Transacción => token: '.$token);
        $this->logError(json_encode($tx));
    }
    
    public function logWebpayPlusDespuesCommitTx($token, $result){
        $this->logInfo('C.4. Transacción con commit en Transbank => token: '.$token);
        $this->logInfo(json_encode($result));
        if (!is_array($result) && isset($result->buyOrder) && $result->responseCode === 0){
            $this->logInfo('***** COMMIT TBK OK *****');
            $this->logInfo('TRANSACCION VALIDADA POR TBK => TOKEN: '.$token);
            $this->logInfo('SI NO SE ENCUENTRA VALIDACION POR PRESTASHOP DEBE ANULARSE');
        }
    }

    public function logWebpayPlusGuardandoCommitExitoso($token){
        if($this->getDebugActive()==1){
            $this->logInfo('C.5. Transacción con commit exitoso en Transbank y guardado => token: '.$token);
        }
    }

    public function logWebpayPlusGuardandoCommitError($token, $result){
        $this->logError('C.5. No se pudo guardar en base de datos el resultado del commit => token: '.$token);
        $this->logError(json_encode($result));
    }

    public function logWebpayPlusCommitFallidoError($token, $result){
        $this->logError('C.5. Respuesta de tbk commit fallido => token: '.$token);
        $this->logError(json_encode($result));
    }

    public function logWebpayPlusAntesValidateOrderPrestashop($token, $amount, $cartId, $OkStatus, $currencyId, $customerSecureKey){
        if($this->getDebugActive()==1){
            $this->logInfo('C.6. Procesando pago - antes de validateOrder');
            $this->logInfo('token : '.$token.', amount : '.$amount.', cartId: '.$cartId.', OKStatus: '.$OkStatus.', currencyId: '.$currencyId.', customer_secure_key: '.$customerSecureKey);
        }
    }

    public function logWebpayPlusDespuesValidateOrderPrestashop($token){
        if($this->getDebugActive()==1){
            $this->logInfo('C.7. Procesando pago despues de validateOrder => token: '.$token);
        }
    }

    public function logWebpayPlusTodoOk($token, $webpayTransaction){
        $this->logInfo('***** TODO OK *****');
        $this->logInfo('TRANSACCION VALIDADA POR PRESTASHOP Y POR TBK EN ESTADO STATUS_APPROVED => TOKEN: '.$token);
        $this->logInfo(json_encode($webpayTransaction));
    }

    public function logPrintCart($cart){
        $this->cartToLog($cart);
    }


    /* LOGS PARA ONECLICK */

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
            $this->logInfo('B.2. Antes de obtener inscripción de la BD => inscriptionId: '.$inscriptionId.', cartId: '.$cartId.', amount: '.$amount);
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
            $this->logInfo('B.3. Preparando datos antes de crear la transacción en BD => inscriptionId: '.$inscriptionId);
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
            $this->logInfo('username: '.$username.', tbkToken: '.$tbkToken.', parentBuyOrder: '.$parentBuyOrder.', childBuyOrder: '.$childBuyOrder.', amount: '.$amount);
        }
    }

    public function logOneclickPaymentDespuesAutorizarTx($username, $tbkToken, $parentBuyOrder, $childBuyOrder, $amount, $result){
        $this->logInfo('B.5. Transacción con autorización en Transbank => username: '.$username.', tbkToken: '.$tbkToken.', parentBuyOrder: '.$parentBuyOrder.', childBuyOrder: '.$childBuyOrder.', amount: '.$amount);
        $this->logInfo(json_encode($result));
        if (!is_array($result) && $result->isApproved()){
            $this->logInfo('***** AUTORIZADO POR TBK OK *****');
            $this->logInfo('TRANSACCION VALIDADA POR TBK => username: '.$username.', tbkToken: '.$tbkToken.', parentBuyOrder: '.$parentBuyOrder.', childBuyOrder: '.$childBuyOrder.', amount: '.$amount);
            $this->logInfo('SI NO SE ENCUENTRA VALIDACION POR PRESTASHOP DEBE ANULARSE');
        }
    }

    public function logOneclickPaymentDespuesAutorizarTxError($parentBuyOrder, $childBuyOrder, $result){
        $this->logError('B.6. Transacción con autorización con error => parentBuyOrder: '.$parentBuyOrder.', childBuyOrder: '.$childBuyOrder);
        $this->logError(json_encode($result));
    }

    public function logOneclickPaymentDespuesAutorizarRechazadoTxError($parentBuyOrder, $childBuyOrder, $result){
        $this->logError('B.6. Transacción con autorización rechazada => parentBuyOrder: '.$parentBuyOrder.', childBuyOrder: '.$childBuyOrder);
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
        $this->logInfo('TRANSACCION VALIDADA POR PRESTASHOP Y POR TBK EN ESTADO STATUS_APPROVED => INSCRIPTION_ID: '.$inscriptionId);
        $this->logInfo(json_encode($webpayTransaction));
    }
}
