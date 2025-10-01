<?php

use Transbank\Webpay\Options;
use Transbank\Plugin\Exceptions\EcommerceException;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;
use PrestaShop\Module\WebpayPlus\Helpers\OneclickFactory;
use PrestaShop\Module\WebpayPlus\Repository\InscriptionRepository;

class WebPayOneclickCardsModuleFrontController extends ModuleFrontController
{
    public $auth = true;
    public $ssl = true;

    /** @var InscriptionRepository */
    private $repository;

    /** @var Transbank\Plugin\Helpers\PluginLogger */
    private $log;

    /** @var PrestaShop\Module\WebpayPlus\Utils\TransbankSdkOneclick */
    private $oneclickService;

    /** @var string */
    private $environment;

    /**
     * Initializes the controller with required dependencies for handling Oneclick card operations.
     * Sets up repository, logger, service, and environment configuration.
     */
    public function __construct()
    {
        parent::__construct();
        $this->repository = new InscriptionRepository();
        $this->log = TbkFactory::createLogger();
        $this->oneclickService = OneclickFactory::create();
        $this->environment = $this->oneclickService->getEnvironment();
    }

    /**
     * Processes POST requests for card deletion operations.
     * Validates the request, authenticates the user, and handles card deletion workflow.
     *
     * @return void
     */
    public function postProcess(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                return;
            }

            $this->validatePostRequest();

            $idCard = Tools::getValue('id_card');
            $idCustomer = $this->context->customer->id;

            $this->handleDeleteCard($idCard, $idCustomer);
        } catch (Exception $e) {
            $this->errors[] = "No se pudo eliminar la tarjeta, por favor intente nuevamente. En caso de persistir el error, contacte al comercio.";
            $this->log->logError($e->getMessage());
            return;
        }
    }

    /**
     * Initializes the content for the Oneclick cards management page.
     * Retrieves user cards, generates CSRF token, and assigns template variables.
     *
     * @return void
     */
    public function initContent()
    {
        parent::initContent();

        $idCustomer = (int) $this->context->customer->id;
        $cards = $this->repository->getCardsByUserId($idCustomer);
        $cards = $this->formatCardData($cards);

        $this->context->smarty->assign([
            'cards' => $cards,
            'csrf_token' => Tools::getToken(true),
            'list_url' => $this->context->link->getModuleLink($this->module->name, 'oneclickcards'),
            'back_to_account_url' => $this->context->link->getPageLink('my-account', true),
            'oneclick_image_url' => $this->context->link->getMediaLink(
                $this->module->getPathUri() . '/views/img/oneclick.png'
            ),
            'strings' => [
                'title' => $this->trans('Tarjetas Oneclick Inscritas', [], 'Modules.WebPay.Shop'),
                'delete' => $this->trans('Eliminar', [], 'Modules.WebPay.Shop'),
                'make_default' => $this->trans('Hacer predeterminada', [], 'Modules.WebPay.Shop'),
                'default' => $this->trans('Predeterminada', [], 'Modules.WebPay.Shop'),
                'no_cards' => $this->trans('Aún no tienes tarjetas inscritas.', [], 'Modules.WebPay.Shop'),
                'back' => $this->trans('Volver a mi cuenta', [], 'Modules.WebPay.Shop'),
            ],
        ]);

        $this->setTemplate("module:{$this->module->name}/views/templates/front/oneclick_cards.tpl");
    }

    /**
     * Generates breadcrumb navigation links for the page.
     * Adds navigation path from home -> account -> oneclick cards.
     *
     * @return array Breadcrumb structure with navigation links
     */
    public function getBreadcrumbLinks(): array
    {
        $breadcrumb = parent::getBreadcrumbLinks();

        $breadcrumb['links'][] = [
            'title' => $this->trans('Su cuenta', [], 'Shop.Theme.Customeraccount'),
            'url' => $this->context->link->getPageLink('my-account', true),
        ];

        $breadcrumb['links'][] = [
            'title' => $this->trans('Tarjetas Oneclick Inscritas', [], 'Modules.Webpay.Shop'),
            'url' => $this->context->link->getModuleLink('webpay', 'oneclickcards'),
        ];

        return $breadcrumb;
    }

    /**
     * Customizes page template variables, particularly the meta title.
     *
     * @return array Page template variables including meta information
     */
    public function getTemplateVarPage(): array
    {
        $page = parent::getTemplateVarPage();
        $page['meta']['title'] = $this->trans('Tarjetas guardadas', [], 'Modules.Webpay.Shop');
        return $page;
    }

    /**
     * Handles the complete card deletion workflow.
     * Validates card existence, deletes from Transbank, and removes from local database.
     *
     * @param string $cardId The unique identifier of the card to delete
     * @param string $customerId The customer's unique identifier
     * @return void
     * @throws EcommerceException When card is not found, Transbank deletion fails, or database deletion fails
     */
    private function handleDeleteCard(string $cardId, string $customerId): void
    {
        $this->log->logInfo("Iniciando eliminación de tarjeta => cardId: {$cardId}, customerId: {$customerId}");
        $inscription = $this->repository->getOneByUserIdAndInscriptionId($customerId, $cardId);

        if (!$inscription) {
            throw new EcommerceException('Inscripción no encontrada.');
        }

        $result = $this->oneclickService->delete($inscription['tbk_token'], $inscription['username']);

        if (!$result) {
            throw new EcommerceException('Error al eliminar la tarjeta en Transbank.');
        }

        $deleted = $this->repository->deleteInscriptionByUserAndId($customerId, $cardId);
        if (!$deleted) {
            throw new EcommerceException('No se pudo eliminar la inscripción de la base de datos.');
        }

        $this->success[] = $this->trans('Tarjeta eliminada.', [], 'Modules.WebPay.Shop');
        $this->log->logInfo("Tarjeta eliminada correctamente => cardId: {$cardId}, customerId: {$customerId}");
    }

    /**
     * Validates incoming POST request for security and required parameters.
     * Checks for required fields and CSRF token validity.
     *
     * @return void
     * @throws EcommerceException When request is invalid or CSRF token is missing/invalid
     */
    private function validatePostRequest(): void
    {
        if (!Tools::isSubmit('id_card')) {
            throw new EcommerceException('Petición inválida.');
        }

        $token = Tools::getValue('csrf_token');
        if (!$this->isValidCsrf($token)) {
            throw new EcommerceException('Token CSRF inválido.');
        }
    }

    /**
     * Formats raw card data for template display.
     * Masks card numbers, adds environment prefixes, and filters by current environment.
     *
     * @param array $cards Raw card data from database
     * @return array Formatted card data ready for template display with masked numbers and environment filtering
     */
    private function formatCardData(array $cards): array
    {
        $cardTypePrefix = $this->environment === Options::ENVIRONMENT_INTEGRATION ? '[TEST]' : '';
        return array_values(array_map(function ($card) use ($cardTypePrefix) {
            $cardNumber = $card['card_number'] ?? null;
            if ($cardNumber && strlen($cardNumber) > 4) {
                $cardNumber = substr($cardNumber, -4);
            }
            return [
                'id_card' => $card['id'] ?? null,
                'card_type' => "{$cardTypePrefix}{$card['card_type']}" ?? null,
                'card_number' => $cardNumber,
                'created_at' => $card['created_at'] ?? null,
            ];
        }, array_filter($cards, function ($card) {
            return $card['environment'] === $this->environment;
        })));
    }

    /**
     * Validates CSRF token against PrestaShop's built-in token system.
     *
     * @param mixed $token The CSRF token to validate (expected to be string)
     * @return bool True if token is valid and matches expected value, false otherwise
     */
    private function isValidCsrf($token)
    {
        return is_string($token) && hash_equals(Tools::getToken(true), $token);
    }
}
