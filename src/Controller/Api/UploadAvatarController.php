<?php
// src/Controller/Api/UploadAvatarController.php
namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

class UploadAvatarController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SluggerInterface $slugger,
    ) {}

    #[Route('/api/users/{id}/avatar', name: 'api_user_avatar_upload', methods: ['POST'])]
    public function __invoke(int $id, Request $request, KernelInterface $kernel): JsonResponse
    {
        /** @var User $user */
        $user = $this->em->getRepository(User::class)->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        // Sécurité
        $currentUser = $this->getUser();
        if (!$currentUser || ($currentUser->getId() !== $user->getId() && !$this->isGranted('ROLE_ADMIN'))) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $uploadedFile = $request->files->get('avatar');
        if (!$uploadedFile) {
            return new JsonResponse(['error' => 'No file provided'], 400);
        }

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($uploadedFile->getMimeType(), $allowedMimeTypes)) {
            return new JsonResponse(['error' => 'Invalid file type. Only images are allowed.'], 400);
        }

        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$uploadedFile->guessExtension();

        $avatarUploadDir = $this->getParameter('kernel.project_dir').'/public/uploads/avatars';
        if (!is_dir($avatarUploadDir)) {
            mkdir($avatarUploadDir, 0755, true);
        }

        $uploadedFile->move($avatarUploadDir, $newFilename);

        $publicPath = '/uploads/avatars/'.$newFilename;
        $user->setAvatar($publicPath);

        $this->em->flush();

        return new JsonResponse([
            'id'        => $user->getId(),
            'avatar'    => $publicPath,
            'firstName' => $user->getFirstName(),
            'lastName'  => $user->getLastName(),
            'email'     => $user->getEmail(),
        ], 200);
    }

    #[Route('/api/users/{id}/avatar', name: 'api_user_avatar_delete', methods: ['DELETE'])]
    public function delete(int $id, KernelInterface $kernel): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->em->getRepository(User::class)->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $currentUser = $this->getUser();
        if (!$currentUser || ($currentUser->getId() !== $user->getId() && !$this->isGranted('ROLE_ADMIN'))) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $old = $user->getAvatar();
        if ($old) {
            $oldPath = $this->getParameter('kernel.project_dir') . '/public' . $old;
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $user->setAvatar(null);
        $this->em->flush();

        return new JsonResponse(['ok' => true], Response::HTTP_OK);
    }
}