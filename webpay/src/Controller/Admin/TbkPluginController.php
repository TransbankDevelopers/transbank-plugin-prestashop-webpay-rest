<?php

namespace PrestaShop\Module\WebpayPlus\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;
use Symfony\Component\HttpFoundation\Request;

class TbkPluginController extends AbstractController
{
    protected $logger;
    protected $tbkAdminService;

    public function __construct() {
        $this->logger = TbkFactory::createLogger();
        $this->tbkAdminService = TbkFactory::createTbkAdminService();
    }

    /**
	 * Expone un conjunto de métodos para administrar el Plugin de Transbank.
	 *
	 * @param Request $request
	 *
	 * @return mixed|JsonResponse
	 */
    public function execute(Request $request){
        $params = json_decode($request->getContent(), true);
        try {
            return new JsonResponse($this->tbkAdminService->execute($params));
        } catch (\Exception $e) {
            return new JsonResponse([
                "message" => $e->getMessage()
            ], 400);
        }
    }

}
