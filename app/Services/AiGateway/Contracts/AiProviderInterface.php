<?php

namespace App\Services\AiGateway\Contracts;

interface AiProviderInterface
{
    /**
     * Send a standardized request envelope to the AI provider.
     */
    public function generate(AiRequestEnvelope $envelope): AiProviderResponse;
}
