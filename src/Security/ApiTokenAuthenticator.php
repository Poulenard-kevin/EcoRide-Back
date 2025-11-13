<?php
// src/Security/ApiTokenAuthenticator.php
namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class ApiTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(private UserRepository $userRepository) {}

    public function supports(Request $request): ?bool
    {
        // On supporte si header Authorization présent et commence par "Bearer "
        $auth = $request->headers->get('Authorization');
        return $auth && 0 === stripos($auth, 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $auth = $request->headers->get('Authorization', '');
        if (!preg_match('/^Bearer\s+(.*)$/i', $auth, $m)) {
            throw new AuthenticationException('Token mal formé.');
        }
        $token = $m[1];

        return new Passport(
            new UserBadge($token, fn($tokenValue) => $this->userRepository->findOneBy(['apiToken' => $tokenValue])),
            new CustomCredentials(fn($credentials, $user) => true, $token)
        );
    }

    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): ?\Symfony\Component\HttpFoundation\Response
    {
        // laisser continuer la requête
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?\Symfony\Component\HttpFoundation\Response
    {
        return new JsonResponse(['message' => 'Authentification requise.'], 401);
    }
}