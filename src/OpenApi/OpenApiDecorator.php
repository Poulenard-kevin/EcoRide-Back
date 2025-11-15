<?php

namespace App\OpenApi;

use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\Core\OpenApi\Model\Components;
use ApiPlatform\Core\OpenApi\Model\SecurityScheme;
use ApiPlatform\Core\OpenApi\OpenApi;

class OpenApiDecorator implements OpenApiFactoryInterface
{
    private OpenApiFactoryInterface $decorated;

    public function __construct(OpenApiFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        $components = $openApi->getComponents();

        // ➜ On ajoute un schéma "ApiToken"
        $securitySchemes = $components->getSecuritySchemes() ?? [];
        $securitySchemes['ApiToken'] = new SecurityScheme(
            'apiKey',      // type
            'header',      // in
            'X-AUTH-TOKEN' // name du header
        );

        $components = $components->withSecuritySchemes($securitySchemes);

        // ➜ On applique la sécurité par défaut (bouton authorize)
        $openApi = $openApi->withSecurity([['ApiToken' => []]]);
        $openApi = $openApi->withComponents($components);

        return $openApi;
    }
}