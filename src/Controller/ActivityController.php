<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Form\ActivityType;
use App\Repository\ActivityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/activity')]
class ActivityController extends AbstractController
{
    #[Route('/', name: 'app_activity_index', methods: ['GET'])]
    #[IsGranted('ROLE_AGENT')]
    public function index(Request $request, ActivityRepository $activityRepository): Response
    {
        $filters = $request->query->all();
        $activities = $activityRepository->findByFilters($filters);

        return $this->render('activity/index.html.twig', [
            'activities' => $activities,
            'filters' => $filters,
        ]);
    }

    #[Route('/new', name: 'app_activity_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $activity = new Activity();
        $activity->setAgent($this->getUser()->getAgent());
        
        $clientId = $request->query->get('client');
        if ($clientId) {
            $client = $entityManager->getRepository('App\Entity\Client')->find($clientId);
            if ($client) {
                $activity->setClient($client);
            }
        }

        $propertyId = $request->query->get('property');
        if ($propertyId) {
            $property = $entityManager->getRepository('App\Entity\Property')->find($propertyId);
            if ($property) {
                $activity->setProperty($property);
            }
        }
        
        $form = $this->createForm(ActivityType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($activity);
            $entityManager->flush();

            $this->addFlash('success', 'Activity created successfully.');
            return $this->redirectToRoute('app_activity_show', ['id' => $activity->getId()]);
        }

        return $this->render('activity/new.html.twig', [
            'activity' => $activity,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_activity_show', methods: ['GET'])]
    #[IsGranted('ROLE_AGENT')]
    public function show(Activity $activity): Response
    {
        $this->denyAccessUnlessGranted('view', $activity);

        return $this->render('activity/show.html.twig', [
            'activity' => $activity,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_activity_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function edit(Request $request, Activity $activity, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('edit', $activity);

        $form = $this->createForm(ActivityType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Activity updated successfully.');
            return $this->redirectToRoute('app_activity_show', ['id' => $activity->getId()]);
        }

        return $this->render('activity/edit.html.twig', [
            'activity' => $activity,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_activity_delete', methods: ['POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function delete(Request $request, Activity $activity, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('delete', $activity);

        if ($this->isCsrfTokenValid('delete'.$activity->getId(), $request->request->get('_token'))) {
            $entityManager->remove($activity);
            $entityManager->flush();
            $this->addFlash('success', 'Activity deleted successfully.');
        }

        return $this->redirectToRoute('app_activity_index');
    }

    #[Route('/{id}/status/{status}', name: 'app_activity_status', methods: ['POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function updateStatus(Request $request, Activity $activity, string $status, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('edit', $activity);

        if ($this->isCsrfTokenValid('status'.$activity->getId(), $request->request->get('_token'))) {
            $activity->setStatus($status);
            if ($status === 'completed') {
                $activity->setCompletedAt(new \DateTime());
            }
            $entityManager->flush();
            $this->addFlash('success', 'Activity status updated successfully.');
        }

        return $this->redirectToRoute('app_activity_show', ['id' => $activity->getId()]);
    }

    #[Route('/calendar', name: 'app_activity_calendar', methods: ['GET'])]
    #[IsGranted('ROLE_AGENT')]
    public function calendar(ActivityRepository $activityRepository): Response
    {
        $agent = $this->getUser()->getAgent();
        $upcomingActivities = $activityRepository->findUpcomingActivities($agent);
        $pastActivities = $activityRepository->findPastActivities($agent);

        return $this->render('activity/calendar.html.twig', [
            'upcomingActivities' => $upcomingActivities,
            'pastActivities' => $pastActivities,
        ]);
    }

    #[Route('/search', name: 'app_activity_search', methods: ['GET'])]
    #[IsGranted('ROLE_AGENT')]
    public function search(Request $request, ActivityRepository $activityRepository): Response
    {
        $filters = $request->query->all();
        $activities = $activityRepository->findByFilters($filters);

        if ($request->isXmlHttpRequest()) {
            return $this->render('activity/_list.html.twig', [
                'activities' => $activities,
            ]);
        }

        return $this->render('activity/search.html.twig', [
            'activities' => $activities,
            'filters' => $filters,
        ]);
    }
} 