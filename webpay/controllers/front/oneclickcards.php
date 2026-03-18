<?php

use Transbank\Webpay\Options;
use PrestaShop\Module\WebpayPlus\Utils\Utils;
use Transbank\Plugin\Exceptions\EcommerceException;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;
use PrestaShop\Module\WebpayPlus\Helpers\OneclickFactory;
use PrestaShop\Module\WebpayPlus\Model\TransbankInscriptions;
use PrestaShop\Module\WebpayPlus\Repository\InscriptionRepository;

class WebPayOneclickCardsModuleFrontController extends ModuleFrontController
{
    const CARD_INSCRIPTION_FLOW = 'start_inscription';
    const CARD_DELETION_FLOW = 'delete_inscription';
    const CARD_INSCRIPTION_RETURN_FLOW = 'inscription_return';
    const CARD_INSCRIPTION_RETURN_NORMAL_FLOW = 'normal';
    const CARD_INSCRIPTION_RETURN_ABORTED_FLOW = 'aborted';
    const CARD_INSCRIPTION_RETURN_INVALID_FLOW = 'invalid';
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

    /** @var PrestaShop\Module\WebpayPlus\Utils\Utils */
    private $moduleUtils;

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
        $this->moduleUtils = new Utils();
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
            $requestMethod = $_SERVER['REQUEST_METHOD'];
            $requestPayload = json_encode(Tools::getAllValues());
            $this->log->logInfo('Procesando petición en OneclickCardsModuleFrontController');
            $this->log->logInfo("Request method: {$requestMethod}");
            $this->log->logInfo("Request payload: {$requestPayload}");
            $this->validateRequest();

            if ($requestMethod === 'GET' && !Tools::getValue('action')) {
                return;
            }

            $this->handleCardRequest();
        } catch (Exception $e) {
            $this->errors[] = "La operación no se pudo completar, por favor reintente. En caso de persistir el problema, contacte al comercio.";
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

        $action = Tools::getValue('action');
        if ($action !== self::CARD_INSCRIPTION_FLOW) {

            $this->context->smarty->assign([
                'cards' => $cards,
                'csrf_token' => Tools::getToken(true),
                'cards_controller_url' => $this->context->link->getModuleLink($this->module->name, 'oneclickcards'),
                'back_to_account_url' => $this->context->link->getPageLink('my-account', true),
                'oneclick_image_url' => $this->context->link->getMediaLink(
                    $this->module->getPathUri() . 'views/img/oneclick.png'
                ),
                'strings' => [
                    'title' => $this->trans('Tarjetas Oneclick Inscritas', [], 'Modules.WebPay.Shop'),
                    'delete' => $this->trans('Eliminar', [], 'Modules.WebPay.Shop'),
                    'enroll' => $this->trans('Inscribir tarjeta', [], 'Modules.WebPay.Shop'),
                    'no_cards' => $this->trans('Aún no tienes tarjetas inscritas.', [], 'Modules.WebPay.Shop'),
                    'back' => $this->trans('Volver a mi cuenta', [], 'Modules.WebPay.Shop'),
                ],
            ]);

            $this->setTemplate("module:{$this->module->name}/views/templates/front/oneclick_cards.tpl");
        }
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

    private function handleCardRequest(): void
    {
        $action = Tools::getValue('action');
        $this->log->logInfo("Acción recibida: {$action}");

        switch ($action) {
            case self::CARD_INSCRIPTION_FLOW:
                $this->handleInscriptionFlow();
                break;
            case self::CARD_INSCRIPTION_RETURN_FLOW:
                $this->handleInscriptionReturnFlow();
                break;
            case self::CARD_DELETION_FLOW:
                $this->handleDeletionFlow();
                break;
            default:
                throw new EcommerceException('Acción no reconocida.');
        }
    }

    private function handleInscriptionFlow(): void
    {
        $this->log->logInfo('Iniciando flujo de inscripción de tarjeta');

        $userId = $this->context->customer->id;
        $username = $this->generateOneclickUsername($userId);
        $email = $this->context->customer->email;
        $returnUrl = $this->context->link->getModuleLink(
            $this->module->name,
            'oneclickcards',
            ['action' => self::CARD_INSCRIPTION_RETURN_FLOW],
            true
        );

        $this->log->logInfo("Datos para inscripción => username: {$username}, email: {$email}, returnUrl: {$returnUrl}");
        $inscriptionResponse = $this->oneclickService->startInscription($username, $email, $returnUrl);

        $this->repository->createInscription([
            'token' => $inscriptionResponse['token'],
            'username' => $username,
            'email' => $email,
            'user_id' => (int) $userId,
            'pay_after_inscription' => false,
            'from' => 'account',
            'status' => TransbankInscriptions::STATUS_INITIALIZED,
            'environment' => $this->environment,
            'commerce_code' => $this->oneclickService->getCommerceCode(),
        ]);

        $this->log->logInfo('Redireccionando a formulario de Webpay');
        $this->redirectToWebpayForm($inscriptionResponse);
    }

    private function handleInscriptionReturnFlow(): void
    {
        $this->log->logInfo('Iniciando flujo de retorno de inscripción de tarjeta');

        $redirectionFlow = $this->getOneclickReturnFlow();

        if ($redirectionFlow === self::CARD_INSCRIPTION_RETURN_INVALID_FLOW) {
            throw new EcommerceException('Flujo de retorno inválido.');
        }

        if ($redirectionFlow === self::CARD_INSCRIPTION_RETURN_ABORTED_FLOW) {
            $this->handleInscriptionReturnAbortedFlow();
        }

        if ($redirectionFlow === self::CARD_INSCRIPTION_RETURN_NORMAL_FLOW) {
            $this->handleInscriptionReturnNormalFlow();
        }
    }

    private function getOneclickReturnFlow(): string
    {
        $tbkToken = Tools::getValue('TBK_TOKEN') ?? null;
        $tbkOrdenCompra = Tools::getValue('TBK_ORDEN_COMPRA') ?? null;
        $returnFlow = self::CARD_INSCRIPTION_RETURN_INVALID_FLOW;

        if ($tbkToken && $tbkOrdenCompra) {
            $returnFlow = self::CARD_INSCRIPTION_RETURN_ABORTED_FLOW;
        }

        if ($tbkToken && !$tbkOrdenCompra) {
            $returnFlow = self::CARD_INSCRIPTION_RETURN_NORMAL_FLOW;
        }

        return $returnFlow;
    }

    private function handleInscriptionReturnNormalFlow(): void
    {
        $tbkToken = Tools::getValue('TBK_TOKEN');
        $this->log->logInfo("Procesando retorno normal de inscripción => TBK_TOKEN: {$tbkToken}");

        $inscription = $this->repository->getInscriptionByToken($tbkToken);
        if (!$inscription) {
            throw new EcommerceException('Inscripción no encontrada para el token proporcionado.');
        }

        $response = $this->oneclickService->finish(
            $tbkToken,
            $inscription['username'],
            $inscription['email']
        );

        $inscriptionApproved = $response->isApproved();

        $this->repository->updateById((int) $inscription['id'], [
            'finished' => true,
            'authorization_code' => $response->authorizationCode,
            'tbk_token' => $response->tbkUser,
            'card_type' => $response->cardType,
            'card_number' => $response->cardNumber,
            'transbank_response' => json_encode($response),
            'status' => $inscriptionApproved
                ? TransbankInscriptions::STATUS_COMPLETED : TransbankInscriptions::STATUS_FAILED,
        ]);

        if (!$inscriptionApproved) {
            $this->log->logInfo("Inscripción de tarjeta rechazada => token: {$tbkToken}.");
            $this->errors[] = $this->trans(
                'La inscripción de tarjeta ha sido rechazada, por favor intenta con otro medio de pago.',
                [],
                'Modules.WebPay.Shop'
            );
        } else {
            $this->log->logInfo("Tarjeta inscrita correctamente => token: {$tbkToken}.");
            $this->success[] = $this->trans('Tarjeta inscrita correctamente.', [], 'Modules.WebPay.Shop');
        }
    }

    private function handleInscriptionReturnAbortedFlow(): void
    {
        $tbkToken = Tools::getValue('TBK_TOKEN');
        $this->log->logInfo("Procesando retorno normal de inscripción => TBK_TOKEN: {$tbkToken}");

        $inscription = $this->repository->getInscriptionByToken($tbkToken);

        if (!$inscription) {
            throw new EcommerceException('Inscripción no encontrada para el token proporcionado.');
        }

        $this->repository->updateById((int) $inscription['id'], [
            'status' => TransbankInscriptions::STATUS_FAILED,
        ]);

        $this->errors[] = $this->trans('Operación cancelada por el usuario.', [], 'Modules.WebPay.Shop');
    }


    /**
     * Handles the complete card deletion workflow.
     * Validates card existence, deletes from Transbank, and removes from local database.
     *
     * @return void
     * @throws EcommerceException When card is not found, Transbank deletion fails, or database deletion fails
     */
    private function handleDeletionFlow(): void
    {
        $cardId = Tools::getValue('id_card');
        $customerId = $this->context->customer->id;

        $this->log->logInfo("Iniciando eliminación de tarjeta => cardId: {$cardId}, customerId: {$customerId}");
        $inscription = $this->repository->getOneByUserIdAndInscriptionId($customerId, $cardId);

        if (!$inscription) {
            throw new EcommerceException('Inscripción no encontrada.');
        }

        $this->oneclickService->delete($inscription['tbk_token'], $inscription['username']);

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
    private function validateRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return;
        }

        if (!Tools::isSubmit('action')) {
            throw new EcommerceException('Petición inválida.');
        }

        if (Tools::getValue('action') !== self::CARD_INSCRIPTION_RETURN_FLOW) {
            $token = Tools::getValue(key: 'csrf_token');
            if (!$this->isValidCsrf($token)) {
                throw new EcommerceException(message: 'Token CSRF inválido.');
            }
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
            $lastDigitsLength = 4;
            $cardNumber = $card['card_number'] ?? null;
            if ($cardNumber && strlen($cardNumber) > $lastDigitsLength) {
                $cardNumber = substr($cardNumber, -$lastDigitsLength);
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

    private function redirectToWebpayForm(array $inscriptionResponse): void
    {
        $this->context->smarty->assign([
            'url' => $inscriptionResponse['url'] ?? '',
            'token_ws' => $inscriptionResponse['token'] ?? '',
            'redirectType' => 'oneclick-cards',
        ]);
        $this->setTemplate('module:webpay/views/templates/front/redirect_to_payment_form.tpl');
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

    public function generateOneclickUsername($userId)
    {
        $idLength = 10;
        return 'ps:' . $this->moduleUtils->generateSecureId($idLength) . ':' . $userId;
    }
}
