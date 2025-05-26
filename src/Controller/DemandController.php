<?php

namespace App\Controller;

use App\Entity\Demand;
use App\Form\DemandType;
use App\Repository\DemandRepository;
use App\Repository\PropertyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/demand')]
class DemandController extends AbstractController
{
    #[Route('/', name: 'app_demand_index', methods: ['GET'])]
    #[IsGranted('ROLE_AGENT')]
    public function index(Request $request, DemandRepository $demandRepository): Response
    {
        $filters = $request->query->all();
        $demands = $demandRepository->findByFilters($filters);

        return $this->render('demand/index.html.twig', [
            'demands' => $demands,
            'filters' => $filters,
        ]);
    }

    #[Route('/new', name: 'app_demand_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $demand = new Demand();
        $clientId = $request->query->get('client');
        if ($clientId) {
            $client = $entityManager->getRepository('App\Entity\Client')->find($clientId);
            if ($client) {
                $demand->setClient($client);
            }
        }
        
        $form = $this->createForm(DemandType::class, $demand);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($demand);
            $entityManager->flush();

            $this->addFlash('success', 'Demand created successfully.');
            return $this->redirectToRoute('app_demand_show', ['id' => $demand->getId()]);
        }

        return $this->render('demand/new.html.twig', [
            'demand' => $demand,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_demand_show', methods: ['GET'])]
    #[IsGranted('ROLE_AGENT')]
    public function show(Demand $demand, PropertyRepository $propertyRepository): Response
    {
        $this->denyAccessUnlessGranted('view', $demand);

        $matchingProperties = $propertyRepository->findByFilters([
            'type' => $demand->getPropertyType(),
            'minPrice' => $demand->getMinPrice(),
            'maxPrice' => $demand->getMaxPrice(),
            'location' => $demand->getLocation(),
            'bedrooms' => $demand->getMinBedrooms(),
            'bathrooms' => $demand->getMinBathrooms(),
            'features' => $demand->getFeatures(),
        ]);

        return $this->render('demand/show.html.twig', [
            'demand' => $demand,
            'matchingProperties' => $matchingProperties,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_demand_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function edit(Request $request, Demand $demand, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('edit', $demand);

        $form = $this->createForm(DemandType::class, $demand);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $demand->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Demand updated successfully.');
            return $this->redirectToRoute('app_demand_show', ['id' => $demand->getId()]);
        }

        return $this->render('demand/edit.html.twig', [
            'demand' => $demand,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_demand_delete', methods: ['POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function delete(Request $request, Demand $demand, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('delete', $demand);

        if ($this->isCsrfTokenValid('delete'.$demand->getId(), $request->request->get('_token'))) {
            $entityManager->remove($demand);
            $entityManager->flush();
            $this->addFlash('success', 'Demand deleted successfully.');
        }

        return $this->redirectToRoute('app_demand_index');
    }

    #[Route('/{id}/status/{status}', name: 'app_demand_status', methods: ['POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function updateStatus(Request $request, Demand $demand, string $status, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('edit', $demand);

        if ($this->isCsrfTokenValid('status'.$demand->getId(), $request->request->get('_token'))) {
            $demand->setStatus($status);
            $demand->setUpdatedAt(new \DateTime());
            $entityManager->flush();
            $this->addFlash('success', 'Demand status updated successfully.');
        }

        return $this->redirectToRoute('app_demand_show', ['id' => $demand->getId()]);
    }

    #[Route('/search', name: 'app_demand_search', methods: ['GET'])]
    #[IsGranted('ROLE_AGENT')]
    public function search(Request $request, DemandRepository $demandRepository): Response
    {
        $filters = $request->query->all();
        $demands = $demandRepository->findByFilters($filters);

        if ($request->isXmlHttpRequest()) {
            return $this->render('demand/_list.html.twig', [
                'demands' => $demands,
            ]);
        }

        return $this->render('demand/search.html.twig', [
            'demands' => $demands,
            'filters' => $filters,
        ]);
    }
} 