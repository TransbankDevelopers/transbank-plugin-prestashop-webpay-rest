<?php

namespace PrestaShop\Module\WebpayPlus\Helpers;

trait InteractsWithWebpayLog
{
    public function logWebpayPlusInstallConfigLoad($webpayCommerceCode, $webpayDefaultOrderStateIdAfterPayment){
        $this->logInfo('Configuración de WEBPAY PLUS se cargo de forma correcta');
        $this->logInfo('webpayCommerceCode: '.$webpayCommerceCode.', webpayDefaultOrderStateIdAfterPayment: '
            .$webpayDefaultOrderStateIdAfterPayment);
    }

    public function logWebpayPlusInstallConfigLoadDefault(){
        $this->logInfo('Configuración por defecto de WEBPAY PLUS se cargo de forma correcta');
    }

    public function logWebpayPlusInstallConfigLoadDefaultPorIncompleta(){
        $this->logInfo('Configuración por defecto de WEBPAY PLUS se cargo de forma
            correcta porque los valores de producción estan incompletos');
    }

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
            $this->logInfo('amount: '.$amount.', sessionId: '.$sessionId.', buyOrder: '.$buyOrder.
                ', returnUrl: '.$returnUrl);
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
        $this->logError('C.2. Error tipo Flujo 2: El pago fue anulado por tiempo de espera => tbkIdSesion: '
            .$tbkIdSesion);
    }

    public function logWebpayPlusRetornandoDesdeTbkFujo3Error($tbktoken){
        $this->logError('C.2. Error tipo Flujo 3: El pago fue anulado por el usuario => tbktoken: '.$tbktoken);
    }
    public function logWebpayPlusRetornandoDesdeTbkFujo3TxError($tbktoken, $webpayTransaction){
        $this->logError('C.2. Error tipo Flujo 3 => tbktoken: '.$tbktoken);
        $this->logError(json_encode($webpayTransaction));
    }

    public function logWebpayPlusRetornandoDesdeTbkFujo4Error($tokenWs, $tbktoken){
        $this->logError('C.2. Error tipo Flujo 4: El pago es inválido  => tokenWs: '.$tokenWs.', tbktoken: '
            .$tbktoken);
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

    public function logWebpayPlusAntesValidateOrderPrestashop($token, $amount, $cartId, $okStatus, 
        $currencyId, $customerSecureKey){
        if($this->getDebugActive()==1){
            $this->logInfo('C.6. Procesando pago - antes de validateOrder');
            $this->logInfo('token : '.$token.', amount : '.$amount.', cartId: '.$cartId.', okStatus: '.$okStatus.
                ', currencyId: '.$currencyId.', customer_secure_key: '.$customerSecureKey);
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

}
