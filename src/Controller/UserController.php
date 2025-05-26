<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Agent;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 10;
        $search = $request->query->get('search');
        $status = $request->query->get('status');

        $users = $userRepository->findByFilters($search, $status, $page, $limit);

        return $this->render('user/index.html.twig', [
            'users' => $users,
            'search' => $search,
            'status' => $status,
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Create associated Agent
            $agent = new Agent();
            $agent->setFirstName($form->get('firstName')->getData());
            $agent->setLastName($form->get('lastName')->getData());
            $agent->setCommission($form->get('commission')->getData());
            $agent->setStatus('active');
            $agent->setUser($user);

            // Set user data
            $user->setName($form->get('firstName')->getData() . ' ' . $form->get('lastName')->getData());
            $user->setRoles(['ROLE_AGENT']);

            // Hash password
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );
            $user->setPassword($hashedPassword);

            $entityManager->persist($user);
            $entityManager->persist($agent);
            $entityManager->flush();

            $this->addFlash('success', 'User created successfully.');

            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('user/form.html.twig', [
            'user' => $user,
            'form' => $form,
            'title' => 'New User',
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createForm(UserType::class, $user, [
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Update associated Agent
            $agent = $user->getAgent();
            if ($agent) {
                $agent->setFirstName($form->get('firstName')->getData());
                $agent->setLastName($form->get('lastName')->getData());
                $agent->setCommission($form->get('commission')->getData());
            }

            // Update password if provided
            if ($plainPassword = $form->get('plainPassword')->getData()) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $entityManager->flush();

            $this->addFlash('success', 'User updated successfully.');

            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('user/form.html.twig', [
            'user' => $user,
            'form' => $form,
            'title' => 'Edit User',
        ]);
    }

    #[Route('/{id}/delete', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'User deleted successfully.');
        }

        return $this->redirectToRoute('app_user_index');
    }

    #[Route('/{id}/toggle-status', name: 'app_user_toggle_status', methods: ['POST'])]
    public function toggleStatus(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('toggle-status'.$user->getId(), $request->request->get('_token'))) {
            $roles = $user->getRoles();
            if (in_array('ROLE_AGENT', $roles)) {
                $roles = array_diff($roles, ['ROLE_AGENT']);
            } else {
                $roles[] = 'ROLE_AGENT';
            }
            $user->setRoles($roles);
            $entityManager->flush();

            $this->addFlash('success', 'User status updated successfully.');
        }

        return $this->redirectToRoute('app_user_index');
    }

    #[Route('/export', name: 'app_user_export', methods: ['GET'])]
    public function export(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        $csv = "ID,Email,First Name,Last Name,Commission,Status\n";

        foreach ($users as $user) {
            $agent = $user->getAgent();
            $status = in_array('ROLE_AGENT', $user->getRoles()) ? 'Active' : 'Inactive';
            $csv .= sprintf(
                "%d,%s,%s,%s,%.2f,%s\n",
                $user->getId(),
                $user->getEmail(),
                $agent ? $agent->getFirstName() : '',
                $agent ? $agent->getLastName() : '',
                $agent ? $agent->getCommission() : 0,
                $status
            );
        }

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="users.csv"');

        return $response;
    }
} 