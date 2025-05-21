<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AdminSecurityController extends AbstractController
{
    #[Route('/admin/login', name: 'app_admin_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_admin_index');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/admin_login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }
    #[Route('/admin/logout', name: 'app_admin_logout', methods: ['POST'])]
    public function logout(): void
    {
        // Tato metoda může být prázdná – bude zachycena klíčem 'logout' ve vašem firewallu.
        // Není potřeba zde psát žádnou logiku pro odhlášení.
        // Můžete zde nechat výjimku jako "pojistku", která se spustí, pokud by se logout nezachytil firewallem.
        throw new \LogicException('Tato metoda by NIKDY neměla být volána přímo! Odhlášení je zpracováno vaším firewallem.');
    }
} 