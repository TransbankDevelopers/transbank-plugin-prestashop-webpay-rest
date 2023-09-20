<?php

namespace Transbank\Plugin\Helpers;

use Transbank\Plugin\Repository\IApiServiceLogRepository;
use Transbank\Plugin\Repository\IConfigRepository;
use Transbank\Plugin\Repository\IExecutionErrorLogRepository;
use Transbank\Plugin\Repository\IInscriptionRepository;
use Transbank\Plugin\Repository\ITransactionRepository;
use Transbank\Plugin\Repository\IUtilRepository;
use Transbank\Plugin\Service\ApiServiceLogService;
use Transbank\Plugin\Service\ExecutionErrorLogService;
use Transbank\Plugin\Service\InscriptionService;
use Transbank\Plugin\Service\TransactionService;
use Transbank\Plugin\Service\TbkWebpayplusService;
use Transbank\Plugin\Service\TbkOneclickService;
use Transbank\Plugin\Service\TbkAdminService;

abstract class BaseTbkFactory {

    /**
     * @var ILogger
     */
    private $logger;
    /**
     * @var IConfigRepository
     */
    private $configRepository;
    /**
     * @var IUtilRepository
     */
    private $utilRepository;
    /**
     * @var IApiServiceLogRepository
     */
    private $apiServiceLogRepository;
    /**
     * @var IExecutionErrorLogRepository
     */
    private $executionErrorLogRepository;
    /**
     * @var IInscriptionRepository
     */
    private $inscriptionRepository;
    /**
     * @var ITransactionRepository
     */
    private $transactionRepository;
    /**
     * @var ExecutionErrorLogService
     */
    private $executionErrorLogService;
    /**
     * @var ApiServiceLogService
     */
    private $apiServiceLogService;
    /**
     * @var TransactionService
     */
    private $transactionService;
    /**
     * @var InscriptionService
     */
    private $inscriptionService;
    /**
     * @var TbkWebpayplusService
     */
    private $tbkWebpayplusService;
    /**
     * @var TbkOneclickService
     */
    private $tbkOneclickService;
    /**
     * @var TbkAdminService
     */
    private $tbkAdminService;
        
    public function __construct(){
        $this->configRepository = $this->createConfigRepository();
        $this->logger = new PluginLogger($this->configRepository->getLogConfig());
        $this->utilRepository = $this->createUtilRepository();
        $this->executionErrorLogRepository = $this->createExecutionErrorLogRepository($this->logger,
            $this->utilRepository);
        $this->apiServiceLogRepository = $this->createApiServiceLogRepository($this->logger,
            $this->utilRepository);
        $this->transactionRepository = $this->createTransactionRepository($this->logger,
            $this->utilRepository);
        $this->inscriptionRepository = $this->createInscriptionRepository($this->logger,
            $this->utilRepository);
        $this->executionErrorLogService = new ExecutionErrorLogService($this->logger,
            $this->executionErrorLogRepository);
        $this->apiServiceLogService = new ApiServiceLogService($this->logger,
            $this->apiServiceLogRepository, $this->executionErrorLogService);
        $this->transactionService = new TransactionService($this->logger,
            $this->configRepository, $this->transactionRepository, $this->executionErrorLogService);
        $this->inscriptionService = new InscriptionService($this->logger,
            $this->inscriptionRepository, $this->executionErrorLogService);
        $this->tbkWebpayplusService = new TbkWebpayplusService($this->logger,
            $this->configRepository, $this->apiServiceLogService,
            $this->executionErrorLogService, $this->transactionService);
        $this->tbkOneclickService = new TbkOneclickService($this->logger, $this->configRepository,
            $this->apiServiceLogService, $this->executionErrorLogService,
            $this->transactionService, $this->inscriptionService);
        $this->tbkAdminService = new TbkAdminService($this->logger,
            $this->configRepository, $this->apiServiceLogService, $this->executionErrorLogService,
            $this->transactionService, $this->inscriptionService);
    }

    abstract public function createUtilRepository();
    abstract public function createConfigRepository();
    abstract public function createExecutionErrorLogRepository($logger, $utilRepository);
    abstract public function createApiServiceLogRepository($logger, $utilRepository);
    abstract public function createInscriptionRepository($logger, $utilRepository);
    abstract public function createTransactionRepository($logger, $utilRepository);

    public function newTbkWebpayplusService()
    {
        return $this->tbkWebpayplusService;
    }

    public function newTbkOneclickService()
    {
        return $this->tbkOneclickService;
    }

    public function newTbkAdminService()
    {
        return $this->tbkAdminService;
    }

    public function newTransactionService()
    {
        return $this->transactionService;
    }

    public function newLogger()
    {
        return $this->logger;
    }
}

