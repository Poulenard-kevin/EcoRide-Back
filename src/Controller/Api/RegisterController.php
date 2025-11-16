<?php
// src/Controller/Api/RegisterController.php
namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Response;

class RegisterController extends AbstractController
{
    /**
     * @Route("/api/register", name="api_register", methods={"POST"})
     */
    public function __invoke(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['message' => 'Payload invalide'], Response::HTTP_BAD_REQUEST);
        }

        // récupération flexible des champs (supporte firstName/lastName ou Prenom/Nom)
        $firstName = trim((string) ($data['firstName'] ?? $data['Prenom'] ?? ''));
        $lastName  = trim((string) ($data['lastName']  ?? $data['Nom'] ?? ''));
        $email     = trim((string) ($data['email'] ?? ''));
        $plainPwd  = $data['password'] ?? null;

        if (empty($email) || empty($plainPwd)) {
            return $this->json(['message' => 'Email et mot de passe requis'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier doublon
        $existing = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing) {
            return $this->json(['message' => 'Utilisateur déjà existant'], Response::HTTP_CONFLICT);
        }

        $user = new User();
        // setters disponibles dans ton entity
        if ($firstName !== '') $user->setFirstName($firstName);
        if ($lastName !== '')  $user->setLastName($lastName);
        $user->setEmail($email);

        // Assigner rôle par défaut si none
        $user->setRoles([User::ROLE_USER]);

        // Hasher le mot de passe et définir la valeur encodée
        $hashed = $passwordHasher->hashPassword($user, $plainPwd);
        $user->setPassword($hashed);

        // Si tu as un champ isActive, on active le compte par défaut
        if (method_exists($user, 'setIsActive')) {
            $user->setIsActive(true);
        }

        // Validation (optionnelle mais utile)
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errList = [];
            foreach ($errors as $e) {
                $errList[] = $e->getPropertyPath() . ': ' . $e->getMessage();
            }
            return $this->json(['message' => 'Validation échouée', 'errors' => $errList], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $token = bin2hex(random_bytes(30));
        $user->setApiToken($token);

        // debug
        error_log('DEBUG register token: '.$user->getApiToken());

        $em->persist($user);
        $em->flush();

        // Effacer plainPassword si présent (sûreté)
        $user->eraseCredentials();

        return $this->json([
            'id' => $user->getId(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'apiToken' => $user->getApiToken(),
        ], Response::HTTP_CREATED);
    }
}