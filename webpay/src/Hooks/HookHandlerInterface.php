<?php

namespace PrestaShop\Module\WebpayPlus\Hooks;

/**
 * Interface for module hook handlers.
 */
interface HookHandlerInterface
{
    /**
     * Executes the logic for the hook.
     *
     * @param array $params Parameters passed to the hook.
     * @return string|null Rendered content or null.
     */
    public function execute(array $params): ?string;
}
