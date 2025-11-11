<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @Route("/admin/user")
 */
class AdminUserController extends AbstractController
{
    /**
     * @Route("/", name="app_admin_user_index", methods={"GET"})
     */
    public function index(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    /**
     * @Route("/employees", name="app_admin_user_employees", methods={"GET"})
     */
    public function employees(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $employees = $userRepository->findByRole('ROLE_EMPLOYEE');

        return $this->render('admin/user/employees.html.twig', [
            'employees' => $employees,
        ]);
    }

    /**
     * @Route("/new", name="app_admin_user_new", methods={"GET", "POST"})
     */
    public function new(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['is_admin' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plain = $form->get('password')->getData();
            if ($plain) {
                $hashedPassword = $hasher->hashPassword($user, $plain);
                $user->setPassword($hashedPassword);
            }
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Utilisateur créé avec succès.');
            return $this->redirectToRoute('app_admin_user_index');
        }

        // IMPORTANT : ->createView() ici
        return $this->render('admin/user/new.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    /**
     * @Route("/new-employee", name="app_admin_employee_new", methods={"GET", "POST"})
     */
    public function newEmployee(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = new User();
        $user->setRoles([User::ROLE_EMPLOYE]);
        $user->setIsActive(true);

        $form = $this->createForm(UserType::class, $user, ['is_admin' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupère le mot de passe en clair depuis le formulaire (mapped => false)
            $plain = $form->get('password')->getData();

            if (!$plain || !is_string($plain)) {
                $this->addFlash('danger', 'Veuillez renseigner un mot de passe valide.');
                return $this->render('admin/user/new_employee.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            $hashedPassword = $hasher->hashPassword($user, $plain);
            $user->setPassword($hashedPassword);

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Employé créé.');
            return $this->redirectToRoute('app_admin_user_index');;
        }

        return $this->render('admin/user/new_employee.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_admin_user_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, User $user, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(UserType::class, $user, ['is_edit' => true, 'is_admin' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('password')->getData();
            if ($plainPassword) {
                $hashedPassword = $hasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $em->flush();

            $this->addFlash('success', 'Utilisateur mis à jour.');
            return $this->redirectToRoute('app_admin_user_index');
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/toggle", name="app_admin_user_toggle", methods={"POST"})
     */
    public function toggle(Request $request, User $user, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('toggle' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_admin_user_index');
        }

        // Empêcher un administrateur de se désactiver lui-même
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas suspendre/activer votre propre compte.');
            return $this->redirectToRoute('app_admin_user_index');
        }

        // Si on tente de suspendre un admin, vérifier qu'il restera au moins un admin actif
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $adminCount = $userRepository->countAdmins();
            // Si on suspend, on doit s'assurer qu'il restera au moins 1 admin actif
            if ($adminCount <= 1 && $user->isActive()) {
                $this->addFlash('error', 'Impossible de suspendre le dernier administrateur actif.');
                return $this->redirectToRoute('app_admin_user_index');
            }
        }

        $user->setIsActive(!$user->isActive());
        $em->flush();

        $this->addFlash('success', $user->isActive() ? 'Utilisateur activé.' : 'Utilisateur suspendu.');
        return $this->redirectToRoute('app_admin_user_index');
    }

    /**
     * @Route("/{id}/show", name="app_admin_user_show", methods={"GET"})
     */
    public function show(User $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/{id}", name="app_admin_user_delete", methods={"POST"})
     */
    public function delete(Request $request, User $user, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {

            // Empêcher suppression de soi-même
            if ($user === $this->getUser()) {
                $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
                return $this->redirectToRoute('app_admin_user_index');
            }

            // Empêcher suppression du dernier admin
            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                $adminCount = $userRepository->countAdmins();
                if ($adminCount <= 1) {
                    $this->addFlash('error', 'Impossible de supprimer le dernier administrateur.');
                    return $this->redirectToRoute('app_admin_user_index');
                }
            }

            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur supprimé.');
        }

        return $this->redirectToRoute('app_admin_user_index');
    }
}