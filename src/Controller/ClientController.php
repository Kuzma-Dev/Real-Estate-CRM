<?php

namespace App\Controller;

use App\Entity\Client;
use App\Form\ClientType;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client')]
class ClientController extends AbstractController
{
    #[Route('/', name: 'app_client_index', methods: ['GET'])]
    #[IsGranted('ROLE_AGENT')]
    public function index(Request $request, ClientRepository $clientRepository): Response
    {
        $filters = $request->query->all();
        $clients = $clientRepository->findByFilters($filters);

        return $this->render('client/index.html.twig', [
            'clients' => $clients,
            'filters' => $filters,
        ]);
    }

    #[Route('/new', name: 'app_client_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $client = new Client();
        $client->setAgent($this->getUser()->getAgent());
        
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($client);
            $entityManager->flush();

            $this->addFlash('success', 'Client created successfully.');
            return $this->redirectToRoute('app_client_show', ['id' => $client->getId()]);
        }

        return $this->render('client/new.html.twig', [
            'client' => $client,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_client_show', methods: ['GET'])]
    #[IsGranted('ROLE_AGENT')]
    public function show(Client $client): Response
    {
        $this->denyAccessUnlessGranted('view', $client);

        return $this->render('client/show.html.twig', [
            'client' => $client,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_client_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function edit(Request $request, Client $client, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('edit', $client);

        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Client updated successfully.');
            return $this->redirectToRoute('app_client_show', ['id' => $client->getId()]);
        }

        return $this->render('client/edit.html.twig', [
            'client' => $client,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_client_delete', methods: ['POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function delete(Request $request, Client $client, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('delete', $client);

        if ($this->isCsrfTokenValid('delete'.$client->getId(), $request->request->get('_token'))) {
            $entityManager->remove($client);
            $entityManager->flush();
            $this->addFlash('success', 'Client deleted successfully.');
        }

        return $this->redirectToRoute('app_client_index');
    }

    #[Route('/{id}/demands', name: 'app_client_demands', methods: ['GET'])]
    #[IsGranted('ROLE_AGENT')]
    public function demands(Client $client): Response
    {
        $this->denyAccessUnlessGranted('view', $client);

        return $this->render('client/demands.html.twig', [
            'client' => $client,
            'demands' => $client->getDemands(),
        ]);
    }

    #[Route('/{id}/activities', name: 'app_client_activities', methods: ['GET'])]
    #[IsGranted('ROLE_AGENT')]
    public function activities(Client $client): Response
    {
        $this->denyAccessUnlessGranted('view', $client);

        return $this->render('client/activities.html.twig', [
            'client' => $client,
            'activities' => $client->getActivities(),
        ]);
    }

    #[Route('/{id}/deals', name: 'app_client_deals', methods: ['GET'])]
    #[IsGranted('ROLE_AGENT')]
    public function deals(Client $client): Response
    {
        $this->denyAccessUnlessGranted('view', $client);

        return $this->render('client/deals.html.twig', [
            'client' => $client,
            'deals' => $client->getDeals(),
        ]);
    }

    #[Route('/search', name: 'app_client_search', methods: ['GET'])]
    #[IsGranted('ROLE_AGENT')]
    public function search(Request $request, ClientRepository $clientRepository): Response
    {
        $filters = $request->query->all();
        $clients = $clientRepository->findByFilters($filters);

        if ($request->isXmlHttpRequest()) {
            return $this->render('client/_list.html.twig', [
                'clients' => $clients,
            ]);
        }

        return $this->render('client/search.html.twig', [
            'clients' => $clients,
            'filters' => $filters,
        ]);
    }
} 