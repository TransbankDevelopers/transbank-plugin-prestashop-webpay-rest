<?php

declare(strict_types=1);

namespace PrestaShop\Module\WebpayPlus\Controller\Admin;

use Symfony\Component\HttpFoundation\RedirectResponse;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use PrestaShop\Module\WebpayPlus\Grid\OneclickCards\OneclickCardsFilters;
use PrestaShop\Module\WebpayPlus\Repository\InscriptionRepository;
use PrestaShop\Module\WebpayPlus\Helpers\OneclickFactory;
use Transbank\Plugin\Exceptions\EcommerceException;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;


class OneclickCardsController extends FrameworkBundleAdminController
{
    protected function getTabClassName(): string
    {
        return ConfigureController::TAB_CLASS_NAME;
    }

    /**
     * @Route("/webpay/oneclick-cards-list", name="oneclick-cards-list")
     * @AdminSecurity(
     *     "is_granted('ROLE_MOD_TAB_WEBPAYPLUSCONFIGURE_READ')",
     *     redirectRoute="admin_login"
     * )
     */
    public function oneclickCardsListAction(OneclickCardsFilters $filters): Response
    {
        $gridFactory = $this->get('webpay.grid.oneclick_card_grid_factory');

        $cardsGrid = $gridFactory->getGrid($filters);
        return $this->render('@Modules/webpay/views/templates/admin/oneclick_cards_list.html.twig', [
            'cardsGrid' => $this->presentGrid($cardsGrid),
            'enableSidebar' => true,
            'layoutTitle' => $this->trans('Tarjetas Oneclick', 'Modules.WebpayPlus.Admin')
        ]);
    }

    /**
     * @AdminSecurity(
     *     "is_granted('ROLE_MOD_TAB_WEBPAYPLUSCONFIGURE_DELETE')",
     *     message="No tienes permisos para eliminar tarjetas.",
     *     redirectRoute="ps_controller_webpay_oneclick_cards_list"
     * )
     */
    public function deleteOneclickCardAction(
        string $cardId,
        string $customerId,
    ): RedirectResponse {
        $logger = TbkFactory::createLogger();
        try {
            $logger->logInfo("Iniciando eliminación de tarjeta Oneclick. ID Usuario: " . $customerId . ", ID Inscripción: " . $cardId);
            $repository = new InscriptionRepository();
            $inscription = $repository->getOneByUserIdAndInscriptionId($customerId, $cardId);

            if (!$inscription) {
                $this->addFlash('error', 'Inscripción no encontrada.');
                $logger->logError("Inscripción no encontrada. ID Usuario: " . $customerId . ", ID Inscripción: " . $cardId);
                return $this->redirectToRoute('ps_controller_webpay_oneclick_cards_list');
            }

            $oneclickService = OneclickFactory::create();

            try {
                $oneclickService->delete($inscription['tbk_token'], $inscription['username']);
                $logger->logInfo("Eliminación en Transbank exitosa. ID Usuario: " . $customerId . ", ID Inscripción: " . $cardId);
                $deleted = $repository->deleteInscriptionByUserAndId($customerId, $cardId);

                if (!$deleted) {
                    $logger->logError("No se pudo eliminar la inscripción de la base de datos. ID Usuario: " . $customerId . ", ID Inscripción: " . $cardId);
                    $this->addFlash('error', 'No se pudo eliminar la inscripción de la base de datos.');
                    return $this->redirectToRoute('ps_controller_webpay_oneclick_cards_list');
                }

                $logger->logInfo("Tarjeta eliminada correctamente. ID Usuario: " . $customerId . ", ID Inscripción: " . $cardId);
                $this->addFlash('success', 'Tarjeta eliminada correctamente. ID Usuario: ' . $customerId . ', ID Inscripción: ' . $cardId);
            } catch (EcommerceException $e) {
                $logger->logError("Error al eliminar la tarjeta en Transbank. ID Usuario:" . $customerId . ", ID Inscripción: " . $cardId . ", Error: " . $e->getMessage());
                $forceDeleteUrl = $this->generateUrl('ps_controller_webpay_oneclick_card_force_delete', [
                    'cardId' => $cardId,
                    'customerId' => $customerId
                ]);

                $csrfToken = $this->container->get('security.csrf.token_manager')
                    ->getToken('force_delete_' . $cardId)
                    ->getValue();

                $this->addFlash('delete_failed', json_encode([
                    'cardId' => $cardId,
                    'customerId' => $customerId,
                    'error' => $e->getMessage(),
                    'forceDeleteUrl' => $forceDeleteUrl,
                    'csrfToken' => $csrfToken
                ]));
            }
        } catch (\Throwable $e) {
            $logger->logError("Error inesperado al eliminar la tarjeta. ID Usuario: " . $customerId . ", ID Inscripción: " . $cardId . ", Error: " . $e->getMessage());
            $this->addFlash('error', 'Error inesperado: ' . $e->getMessage());
        }

        return $this->redirectToRoute('ps_controller_webpay_oneclick_cards_list');
    }

    /**
     * @AdminSecurity(
     *     "is_granted('ROLE_MOD_TAB_WEBPAYPLUSCONFIGURE_DELETE')",
     *     redirectRoute="ps_controller_webpay_oneclick_cards_list"
     * )
     */
    public function forceDeleteOneclickCardAction(
        string $cardId,
        string $customerId,
    ): RedirectResponse {
        $logger = TbkFactory::createLogger();
        try {
            $repository = new InscriptionRepository();
            $deleted = $repository->deleteInscriptionByUserAndId($customerId, $cardId);

            if ($deleted) {
                $logger->logInfo("Inscripción eliminada de la base de datos mediante eliminación forzada. ID Usuario: " . $customerId . ", ID Inscripción: " . $cardId);
                $this->addFlash('warning', 'Inscripción eliminada de la base de datos.');
            } else {
                $logger->logError("No se pudo eliminar la inscripción de la base de datos mediante eliminación forzada. ID Usuario: " . $customerId . ", ID Inscripción: " . $cardId);
                $this->addFlash('error', 'No se pudo eliminar la inscripción de la base de datos.');
            }
        } catch (\Throwable $e) {
            $logger->logError("Error inesperado durante la eliminación forzada. ID Usuario: " . $customerId . ", ID Inscripción: " . $cardId . ", Error: " . $e->getMessage());
            $this->addFlash('error', 'Error al eliminar: ' . $e->getMessage());
        }

        return $this->redirectToRoute('ps_controller_webpay_oneclick_cards_list');
    }
}
