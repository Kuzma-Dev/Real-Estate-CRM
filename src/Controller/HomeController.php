<?php

namespace App\Controller;

use App\Repository\PropertyRepository;
use App\Repository\ClientRepository;
use App\Repository\DealRepository;
use App\Repository\DemandRepository;
use App\Repository\ActivityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    #[IsGranted('ROLE_AGENT')]
    public function index(
        PropertyRepository $propertyRepository,
        ClientRepository $clientRepository,
        DealRepository $dealRepository,
        DemandRepository $demandRepository,
        ActivityRepository $activityRepository
    ): Response {
        $agent = $this->getUser()->getAgent();

        return $this->render('home/index.html.twig', [
            'properties' => $propertyRepository->findBy(['agent' => $agent], ['createdAt' => 'DESC'], 5),
            'clients' => $clientRepository->findBy(['agent' => $agent], ['createdAt' => 'DESC'], 5),
            'deals' => $dealRepository->findBy(['agent' => $agent], ['createdAt' => 'DESC'], 5),
            'demands' => $demandRepository->findBy(['agent' => $agent], ['createdAt' => 'DESC'], 5),
            'upcomingActivities' => $activityRepository->findUpcomingActivities($agent),
            'stats' => [
                'properties' => $propertyRepository->count(['agent' => $agent]),
                'clients' => $clientRepository->count(['agent' => $agent]),
                'deals' => $dealRepository->count(['agent' => $agent]),
                'demands' => $demandRepository->count(['agent' => $agent]),
                'activeDeals' => $dealRepository->count(['agent' => $agent, 'status' => 'under_contract']),
                'completedDeals' => $dealRepository->count(['agent' => $agent, 'status' => 'completed']),
            ],
        ]);
    }
} 