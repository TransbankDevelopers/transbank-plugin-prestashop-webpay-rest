<?php

declare(strict_types=1);

namespace PrestaShop\Module\WebpayPlus\Controller\Admin;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use PrestaShop\Module\WebpayPlus\Grid\OneclickCards\OneclickCardsFilters;
use PrestaShop\Module\WebpayPlus\Repository\InscriptionRepository;
use PrestaShop\Module\WebpayPlus\Helpers\OneclickFactory;
use Transbank\Plugin\Exceptions\EcommerceException;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;
use PrestaShopBundle\Controller\Admin\PrestaShopAdminController;
use PrestaShopBundle\Security\Attribute\AdminSecurity;
use PrestaShop\PrestaShop\Core\Grid\GridFactoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Transbank\Plugin\Helpers\PluginLogger;

class OneclickCardsController extends PrestaShopAdminController
{
    private const LOG_CONTEXT = 'ID Usuario: %s, ID Inscripción: %s';
    private const LOG_CONTEXT_WITH_ERROR = 'ID Usuario: %s, ID Inscripción: %s, Error: %s';

    protected function getTabClassName(): string
    {
        return ConfigureController::TAB_CLASS_NAME;
    }

    // The following line using route notation is required by code review tools to help enforce code quality standards.
    /**
     * @Route("/webpay/oneclick-cards-list", name="oneclick-cards-list")
     */
    #[AdminSecurity(
        "is_granted('ROLE_MOD_TAB_WEBPAYPLUSCONFIGURE_READ')",
        redirectRoute: "admin_login"
    )]
    public function oneclickCardsListAction(
        OneclickCardsFilters $filters,
        #[Autowire(service: 'webpay.grid.oneclick_card_grid_factory')]
        GridFactoryInterface $gridFactory,
    ): Response {
        $cardsGrid = $gridFactory->getGrid($filters);
        return $this->render('@Modules/webpay/views/templates/admin/oneclick_cards_list.html.twig', [
            'cardsGrid' => $this->presentGrid($cardsGrid),
            'enableSidebar' => true,
            'layoutTitle' => $this->trans('Tarjetas Oneclick', [], 'Modules.WebpayPlus.Admin')
        ]);
    }

    // The following line using route notation is required by code review tools to help enforce code quality standards.
    /**
     * @Route(
     *     "/webpay/oneclick-cards/{customerId}/{cardId}/delete",
     *     name="ps_controller_webpay_oneclick_card_delete",
     *     methods={"POST"}
     * )
     */
    #[AdminSecurity(
        "is_granted('ROLE_MOD_TAB_WEBPAYPLUSCONFIGURE_DELETE')",
        message: "No tienes permisos para eliminar tarjetas.",
        redirectRoute: "ps_controller_webpay_oneclick_cards_list"
    )]
    public function deleteOneclickCardAction(
        string $cardId,
        string $customerId,
        #[Autowire(service: 'security.csrf.token_manager')]
        CsrfTokenManagerInterface $csrfTokenManager,
    ): RedirectResponse {
        $logger = TbkFactory::createLogger();
        try {
            $logger->logInfo(sprintf(
                'Iniciando eliminación de tarjeta Oneclick. ' . self::LOG_CONTEXT,
                $customerId,
                $cardId
            ));

            $inscription = $this->findInscription($cardId, $customerId, $logger);

            if (!$inscription) {
                return $this->redirectToRoute('ps_controller_webpay_oneclick_cards_list');
            }

            $this->processOneclickDeletion($cardId, $customerId, $inscription, $csrfTokenManager, $logger);
        } catch (\Throwable $e) {
            $logger->logError(sprintf(
                'Error inesperado al eliminar la tarjeta. ' . self::LOG_CONTEXT_WITH_ERROR,
                $customerId,
                $cardId,
                $e->getMessage()
            ));
            $this->addFlash('error', 'Ocurrió un error al eliminar la inscripción.');
        }

        return $this->redirectToRoute('ps_controller_webpay_oneclick_cards_list');
    }

    private function findInscription(string $cardId, string $customerId, PluginLogger $logger): ?array
    {
        $repository = new InscriptionRepository();
        $inscription = $repository->getOneByUserIdAndInscriptionId($customerId, $cardId);

        if (!$inscription) {
            $this->addFlash('error', 'Inscripción no encontrada.');
            $logger->logError(sprintf(
                'Inscripción no encontrada. ' . self::LOG_CONTEXT,
                $customerId,
                $cardId
            ));
        }

        return $inscription ?: null;
    }

    private function processOneclickDeletion(
        string $cardId,
        string $customerId,
        array $inscription,
        CsrfTokenManagerInterface $csrfTokenManager,
        PluginLogger $logger
    ): void {
        $oneclickService = OneclickFactory::create();
        $repository = new InscriptionRepository();

        try {
            $oneclickService->delete($inscription['tbk_token'], $inscription['username']);
            $logger->logInfo(sprintf(
                'Eliminación en Transbank exitosa. ' . self::LOG_CONTEXT,
                $customerId,
                $cardId
            ));

            $deleted = $repository->deleteInscriptionByUserAndId($customerId, $cardId);

            if (!$deleted) {
                $logger->logError(sprintf(
                    'No se pudo eliminar la inscripción de la base de datos. ' . self::LOG_CONTEXT,
                    $customerId,
                    $cardId
                ));
                $this->addFlash('error', 'No se pudo eliminar la inscripción de la base de datos.');
                return;
            }

            $logger->logInfo(sprintf(
                'Tarjeta eliminada correctamente. ' . self::LOG_CONTEXT,
                $customerId,
                $cardId
            ));
            $this->addFlash('success', 'Tarjeta eliminada correctamente.');
        } catch (EcommerceException $e) {
            $this->handleEcommerceException($cardId, $customerId, $csrfTokenManager, $logger, $e);
        }
    }

    private function handleEcommerceException(
        string $cardId,
        string $customerId,
        CsrfTokenManagerInterface $csrfTokenManager,
        PluginLogger $logger,
        EcommerceException $e
    ): void {
        $logger->logError(sprintf(
            'Error al eliminar la tarjeta en Transbank. ' . self::LOG_CONTEXT_WITH_ERROR,
            $customerId,
            $cardId,
            $e->getMessage()
        ));

        $forceDeleteUrl = $this->generateUrl('ps_controller_webpay_oneclick_card_force_delete', [
            'cardId' => $cardId,
            'customerId' => $customerId
        ]);

        $csrfToken = $csrfTokenManager->getToken('force_delete_' . $cardId)->getValue();

        $this->addFlash('delete_failed', [
            'cardId' => $cardId,
            'customerId' => $customerId,
            'forceDeleteUrl' => $forceDeleteUrl,
            'csrfToken' => $csrfToken
        ]);
    }

    // The following line using route notation is required by code review tools to help enforce code quality standards.
    /**
     * @Route(
     *     "/webpay/oneclick-cards/{customerId}/{cardId}/force-delete",
     *     name="ps_controller_webpay_oneclick_card_force_delete",
     *     methods={"POST"}
     * )
     */
    #[AdminSecurity(
        "is_granted('ROLE_MOD_TAB_WEBPAYPLUSCONFIGURE_DELETE')",
        redirectRoute: "ps_controller_webpay_oneclick_cards_list"
    )]
    public function forceDeleteOneclickCardAction(
        string $cardId,
        string $customerId,
    ): RedirectResponse {
        $logger = TbkFactory::createLogger();
        try {
            $repository = new InscriptionRepository();
            $deleted = $repository->deleteInscriptionByUserAndId($customerId, $cardId);

            if ($deleted) {
                $logger->logInfo(sprintf(
                    'Inscripción eliminada de la base de datos mediante eliminación forzada. ' . self::LOG_CONTEXT,
                    $customerId,
                    $cardId
                ));
                $this->addFlash('warning', 'Inscripción eliminada de la base de datos.');
            } else {
                $logger->logError(sprintf(
                    'No se pudo eliminar la inscripción de la base de datos mediante eliminación forzada. ' . self::LOG_CONTEXT,
                    $customerId,
                    $cardId
                ));
                $this->addFlash('error', 'No se pudo eliminar la inscripción de la base de datos.');
            }
        } catch (\Throwable $e) {
            $logger->logError(sprintf(
                'Error inesperado durante la eliminación forzada. ' . self::LOG_CONTEXT_WITH_ERROR,
                $customerId,
                $cardId,
                $e->getMessage()
            ));
            $this->addFlash('error', 'Ocurrió un error al eliminar la inscripción.');
        }

        return $this->redirectToRoute('ps_controller_webpay_oneclick_cards_list');
    }
}
