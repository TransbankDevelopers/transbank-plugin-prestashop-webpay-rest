<?php

namespace PrestaShop\Module\WebpayPlus\Service;

use Transbank\Plugin\Exceptions\EcommerceException;
use Transbank\Plugin\Helpers\PluginLogger;
use PrestaShop\Module\WebpayPlus\Model\TransbankInscriptions;
use PrestaShop\Module\WebpayPlus\Repository\InscriptionRepository;

/**
 * Persists Oneclick inscription records shared by the card-management and checkout inscription flows.
 */
class OneclickInscriptionService
{
    /** @var InscriptionRepository */
    private $repository;

    /** @var PluginLogger */
    private $logger;

    /** @var string */
    private $environment;

    /** @var string */
    private $commerceCode;

    /**
     * @param InscriptionRepository $repository Repository used to persist inscription records
     * @param PluginLogger $logger Logger used to record failures that cannot otherwise be surfaced
     * @param string $environment Transbank environment for the current controller
     * @param string $commerceCode Commerce code associated with the current controller's Oneclick flow
     */
    public function __construct(InscriptionRepository $repository, PluginLogger $logger, string $environment, string $commerceCode)
    {
        $this->repository = $repository;
        $this->logger = $logger;
        $this->environment = $environment;
        $this->commerceCode = $commerceCode;
    }

    /**
     * Persists an inscription record with the given token and status.
     *
     * @param string $username Oneclick username for the inscription
     * @param string $email Customer email associated with the inscription
     * @param int $userId Customer ID associated with the inscription
     * @param string $token Inscription token returned by Transbank, or the placeholder when unavailable
     * @param string $status Inscription status to persist
     * @param string $from Origin of the inscription attempt
     * @param int|null $orderId Order ID associated with the inscription, when available
     * @return void
     * @throws EcommerceException When the inscription record could not be created
     */
    public function save(string $username, string $email, int $userId, string $token, string $status, string $from, ?int $orderId = null): void
    {
        $data = [
            'token' => $token,
            'username' => $username,
            'email' => $email,
            'user_id' => $userId,
            'pay_after_inscription' => false,
            'from' => $from,
            'status' => $status,
            'environment' => $this->environment,
            'commerce_code' => $this->commerceCode,
        ];

        if ($orderId !== null) {
            $data['order_id'] = $orderId;
        }

        $inscriptionId = $this->repository->createInscription($data);

        if ($inscriptionId === 0) {
            throw new EcommerceException('No se pudo crear el registro en transbank_inscriptions.');
        }
    }

    /**
     * Attempts to persist the inscription with a failed status after an error occurred.
     * Any failure from this write is only logged, so it does not replace the caller's original error.
     *
     * @param string $username Oneclick username generated for the inscription attempt
     * @param string $email Customer email associated with the inscription
     * @param int $userId Customer ID associated with the inscription
     * @param string $token Transbank token already issued for this attempt, or the placeholder when none was issued
     * @param string $from Origin of the inscription attempt
     * @param int|null $orderId Order ID associated with the inscription, when available
     * @return void
     */
    public function markAsFailed(string $username, string $email, int $userId, string $token, string $from, ?int $orderId = null): void
    {
        try {
            $this->save($username, $email, $userId, $token, TransbankInscriptions::STATUS_FAILED, $from, $orderId);
        } catch (\Throwable $e) {
            $this->logger->logError('No se pudo registrar el estado de error en transbank_inscriptions: ' . $e->getMessage());
        }
    }
}
