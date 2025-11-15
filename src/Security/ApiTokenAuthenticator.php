<?php
namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class ApiTokenAuthenticator extends AbstractAuthenticator
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function supports(Request $request): ?bool
    {
        // supporte X-API-TOKEN, X-AUTH-TOKEN ou ?api_token=
        return $request->headers->has('X-API-TOKEN')
            || $request->headers->has('X-AUTH-TOKEN')
            || $request->query->has('api_token');
    }

    public function authenticate(Request $request)
    {
        $apiToken = $request->headers->get('X-API-TOKEN')
            ?? $request->headers->get('X-AUTH-TOKEN')
            ?? $request->query->get('api_token');

        if (!$apiToken) {
            throw new AuthenticationException('No API token provided');
        }

        return new SelfValidatingPassport(new UserBadge($apiToken, function ($tokenValue) {
            // retourne l'entité User ou null
            return $this->userRepository->findOneBy(['apiToken' => $tokenValue]);
        }));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?JsonResponse
    {
        // laisser continuer la requête normalement
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?JsonResponse
    {
        return new JsonResponse(['error' => $exception->getMessage()], 401);
    }
}