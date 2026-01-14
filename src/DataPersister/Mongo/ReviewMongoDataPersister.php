<?php

namespace App\DataPersister\Mongo;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use App\Document\ReviewMongo;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ReviewMongoDataPersister implements ContextAwareDataPersisterInterface
{
    private DocumentManager $dm;
    private Security $security;

    public function __construct(DocumentManager $dm, Security $security)
    {
        $this->dm = $dm;
        $this->security = $security;
    }

    public function supports($data, array $context = []): bool
    {
        return $data instanceof ReviewMongo;
    }

    public function persist($data, array $context = [])
    {
        /** @var ReviewMongo $data */
        $user = $this->security->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('Utilisateur non authentifié.');
        }

        if (null === $data->getUserId()) {
            $data->setUserId((int) $user->getId());
        }
        if (null === $data->getCreatedAt()) {
            $data->setCreatedAt(new \DateTime());
        }

        $this->dm->persist($data);
        $this->dm->flush();

        return $data;
    }

    public function remove($data, array $context = [])
    {
        $this->dm->remove($data);
        $this->dm->flush();
    }
}