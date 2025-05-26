<?php

namespace App\Controller;

use App\Entity\Deal;
use App\Form\DealType;
use App\Repository\DealRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/deal')]
class DealController extends AbstractController
{
    #[Route('/', name: 'app_deal_index', methods: ['GET'])]
    #[IsGranted('ROLE_AGENT')]
    public function index(Request $request, DealRepository $dealRepository): Response
    {
        $filters = $request->query->all();
        $deals = $dealRepository->findByFilters($filters);

        return $this->render('deal/index.html.twig', [
            'deals' => $deals,
            'filters' => $filters,
        ]);
    }

    #[Route('/new', name: 'app_deal_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $deal = new Deal();
        $deal->setAgent($this->getUser()->getAgent());
        
        $form = $this->createForm(DealType::class, $deal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($deal);
            $entityManager->flush();

            $this->addFlash('success', 'Deal created successfully.');
            return $this->redirectToRoute('app_deal_show', ['id' => $deal->getId()]);
        }

        return $this->render('deal/new.html.twig', [
            'deal' => $deal,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_deal_show', methods: ['GET'])]
    #[IsGranted('ROLE_AGENT')]
    public function show(Deal $deal): Response
    {
        $this->denyAccessUnlessGranted('view', $deal);

        return $this->render('deal/show.html.twig', [
            'deal' => $deal,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_deal_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function edit(Request $request, Deal $deal, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('edit', $deal);

        $form = $this->createForm(DealType::class, $deal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Deal updated successfully.');
            return $this->redirectToRoute('app_deal_show', ['id' => $deal->getId()]);
        }

        return $this->render('deal/edit.html.twig', [
            'deal' => $deal,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_deal_delete', methods: ['POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function delete(Request $request, Deal $deal, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('delete', $deal);

        if ($this->isCsrfTokenValid('delete'.$deal->getId(), $request->request->get('_token'))) {
            $entityManager->remove($deal);
            $entityManager->flush();
            $this->addFlash('success', 'Deal deleted successfully.');
        }

        return $this->redirectToRoute('app_deal_index');
    }

    #[Route('/{id}/status/{status}', name: 'app_deal_status', methods: ['POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function updateStatus(Request $request, Deal $deal, string $status, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('edit', $deal);

        if ($this->isCsrfTokenValid('status'.$deal->getId(), $request->request->get('_token'))) {
            $deal->setStatus($status);
            if ($status === 'completed') {
                $deal->setCompletedAt(new \DateTime());
            }
            $entityManager->flush();
            $this->addFlash('success', 'Deal status updated successfully.');
        }

        return $this->redirectToRoute('app_deal_show', ['id' => $deal->getId()]);
    }

    #[Route('/{id}/documents', name: 'app_deal_documents', methods: ['POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function uploadDocuments(Request $request, Deal $deal, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('edit', $deal);

        $files = $request->files->get('documents');
        if ($files) {
            $documents = $deal->getDocuments();
            foreach ($files as $file) {
                $documents[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $this->uploadFile($file),
                    'uploadedAt' => (new \DateTime())->format('Y-m-d H:i:s'),
                ];
            }
            $deal->setDocuments($documents);
            $entityManager->flush();
            $this->addFlash('success', 'Documents uploaded successfully.');
        }

        return $this->redirectToRoute('app_deal_edit', ['id' => $deal->getId()]);
    }

    #[Route('/stats', name: 'app_deal_stats', methods: ['GET'])]
    #[IsGranted('ROLE_AGENT')]
    public function stats(DealRepository $dealRepository): Response
    {
        $agent = $this->getUser()->getAgent();
        $stats = $dealRepository->getAgentStats($agent);

        return $this->render('deal/stats.html.twig', [
            'stats' => $stats,
        ]);
    }

    private function uploadFile($file): string
    {
        $fileName = uniqid().'.'.$file->guessExtension();
        $file->move(
            $this->getParameter('documents_directory'),
            $fileName
        );
        return $fileName;
    }
} 