<?php

namespace PrestaShop\Module\WebpayPlus\Helpers;

use PrestaShop\Module\WebpayPlus\Repository\ApiServiceLogRepository;
use PrestaShop\Module\WebpayPlus\Repository\TransactionRepository;
use PrestaShop\Module\WebpayPlus\Repository\InscriptionRepository;
use PrestaShop\Module\WebpayPlus\Repository\ExecutionErrorLogRepository;
use PrestaShop\Module\WebpayPlus\Repository\UtilRepository;
use PrestaShop\Module\WebpayPlus\Repository\ConfigRepository;
use Transbank\Plugin\Helpers\BaseTbkFactory;

class TbkFactory extends BaseTbkFactory
{
    public function createUtilRepository(){
        return new UtilRepository();
    }

    public function createConfigRepository(){
        return new ConfigRepository();
    }

    public function createExecutionErrorLogRepository($logger, $utilRepository){
        return new ExecutionErrorLogRepository($logger, $utilRepository);
    }

    public function createApiServiceLogRepository($logger, $utilRepository){
        return new ApiServiceLogRepository($logger, $utilRepository);
    }

    public function createInscriptionRepository($logger, $utilRepository){
        return new InscriptionRepository($logger, $utilRepository);
    }

    public function createTransactionRepository($logger, $utilRepository){
        return new TransactionRepository($logger, $utilRepository);
    }

    public static function createTbkWebpayplusService($storeId = '0')
    {
        return (new TbkFactory())->newTbkWebpayplusService($storeId);
    }

    public static function createTbkOneclickService($storeId = '0')
    {
        return (new TbkFactory())->newTbkOneclickService($storeId);
    }

    public static function createTbkAdminService()
    {
        return (new TbkFactory())->newTbkAdminService();
    }

    public static function createTransactionService()
    {
        return (new TbkFactory())->newTransactionService();
    }

    public static function createLogger()
    {
        return (new TbkFactory())->newLogger();
    }
}

