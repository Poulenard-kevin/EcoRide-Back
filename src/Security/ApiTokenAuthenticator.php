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
        $this->logger->debug('ApiTokenAuthenticator::supports', [
            'path' => $request->getPathInfo(),
            'headers' => array_keys($request->headers->all()),
        ]);

        return $request->headers->has('X-API-TOKEN')
            || $request->headers->has('X-AUTH-TOKEN')
            || $request->headers->has('Authorization')
            || $request->query->has('api_token');
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