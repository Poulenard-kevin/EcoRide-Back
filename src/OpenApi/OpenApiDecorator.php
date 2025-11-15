<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\SecurityScheme;
use ApiPlatform\OpenApi\OpenApi;

final class OpenApiDecorator implements OpenApiFactoryInterface
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
        $schemes = $components->getSecuritySchemes() ?? [];

        // bearerAuth
        $schemes['bearerAuth'] = new SecurityScheme(
            type: 'http',
            scheme: 'bearer',
            bearerFormat: 'JWT' // label only; your tokens can be anything
        );

        // API key header
        $schemes['ApiKeyAuth'] = new SecurityScheme(
            type: 'apiKey',
            name: 'X-AUTH-TOKEN',
            in: 'header'  
        );

        $openApi = $openApi->withComponents($components->withSecuritySchemes($schemes));

        // Apply a global security requirement (optional)
        $security = $openApi->getSecurity() ?? [];
        $security[] = ['bearerAuth' => []];
        $openApi = $openApi->withSecurity($security);

        return $openApi;
    }
}