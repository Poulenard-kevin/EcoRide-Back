<?php
namespace App\Security;

use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ApiTokenAuthenticator extends AbstractAuthenticator
{
    private UserRepository $userRepository;
    private LoggerInterface $logger;

    public function __construct(UserRepository $userRepository, LoggerInterface $logger)
    {
        $this->userRepository = $userRepository;
        $this->logger = $logger;
    }

    public function supports(Request $request): ?bool
    {
        // Ignore preflight
        if ($request->isMethod('OPTIONS')) {
            return false;
        }

        // Vérifier qu'un header X-API-TOKEN non vide est présent
        $xApiToken = $request->headers->get('X-API-TOKEN', '');
        $hasXApiToken = is_string($xApiToken) && trim($xApiToken) !== '';

        // Vérifier X-AUTH-TOKEN non vide
        $xAuthToken = $request->headers->get('X-AUTH-TOKEN', '');
        $hasXAuthToken = is_string($xAuthToken) && trim($xAuthToken) !== '';

        // Vérifier Authorization: Bearer <token>
        $auth = $request->headers->get('Authorization', '');
        $hasBearer = false;
        if (is_string($auth) && 0 === stripos($auth, 'Bearer ')) {
            $bearerToken = trim(substr($auth, 7));
            $hasBearer = $bearerToken !== '';
        }

        // Vérifier paramètre query api_token non vide
        $queryToken = (string) $request->query->get('api_token', '');
        $hasQueryToken = trim($queryToken) !== '';

        $hasToken = $hasXApiToken || $hasXAuthToken || $hasBearer || $hasQueryToken;

        $this->logger->debug('ApiTokenAuthenticator::supports', [
            'path' => $request->getPathInfo(),
            'has_x_api_token' => $hasXApiToken,
            'has_x_auth_token' => $hasXAuthToken,
            'has_bearer' => $hasBearer,
            'has_query_token' => $hasQueryToken,
        ]);

        return $hasToken;
    }

    public function authenticate(Request $request)
    {
        $this->logger->debug('ApiTokenAuthenticator::authenticate START', ['path' => $request->getPathInfo()]);

        $apiToken = $request->headers->get('X-API-TOKEN')
            ?? $request->headers->get('X-AUTH-TOKEN')
            ?? $request->query->get('api_token');

        if (!$apiToken) {
            $auth = $request->headers->get('Authorization', '');
            if (0 === stripos($auth, 'Bearer ')) {
                $apiToken = substr($auth, 7);
            }
        }

        $this->logger->debug('ApiTokenAuthenticator resolved token', [
            'has_token' => (bool) $apiToken,
        ]);

        if (empty($apiToken)) {
            // message destiné au client (sécurisé)
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }

        return new SelfValidatingPassport(new UserBadge($apiToken, function ($tokenValue) {
            $this->logger->debug('ApiTokenAuthenticator loading user by token');

            $user = $this->userRepository->findOneBy(['apiToken' => $tokenValue]);

            if ($user) {
                $this->logger->info('API token matched user', [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                ]);
            } else {
                $this->logger->notice('API token not matched');
            }

            return $user;
        }));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?JsonResponse
    {
        $this->logger->debug('ApiTokenAuthenticator::onAuthenticationSuccess', ['firewall' => $firewallName]);
        return null;
    }

    public function onAuthenticationFailure(Request $request, \Symfony\Component\Security\Core\Exception\AuthenticationException $exception): ?JsonResponse
    {
        $this->logger->warning('ApiTokenAuthenticator::onAuthenticationFailure', ['message' => $exception->getMessage()]);
        return new JsonResponse(['error' => $exception->getMessage()], 401);
    }
}