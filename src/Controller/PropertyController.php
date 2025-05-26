<?php

namespace App\Controller;

use App\Entity\Property;
use App\Entity\PropertyImage;
use App\Form\PropertyType;
use App\Repository\PropertyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/property')]
class PropertyController extends AbstractController
{
    #[Route('/', name: 'app_property_index', methods: ['GET'])]
    public function index(Request $request, PropertyRepository $propertyRepository, PaginatorInterface $paginator): Response
    {
        $filters = $request->query->all();
        $query = $propertyRepository->createQueryBuilder('p')
            ->leftJoin('p.agent', 'a')
            ->addSelect('a')
            ->orderBy('p.createdAt', 'DESC');

        // Apply filters if any
        if (!empty($filters)) {
            if (isset($filters['type'])) {
                $query->andWhere('p.type = :type')
                    ->setParameter('type', $filters['type']);
            }
            if (isset($filters['status'])) {
                $query->andWhere('p.status = :status')
                    ->setParameter('status', $filters['status']);
            }
            if (isset($filters['minPrice'])) {
                $query->andWhere('p.price >= :minPrice')
                    ->setParameter('minPrice', $filters['minPrice']);
            }
            if (isset($filters['maxPrice'])) {
                $query->andWhere('p.price <= :maxPrice')
                    ->setParameter('maxPrice', $filters['maxPrice']);
            }
        }

        $properties = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10 // items per page
        );

        return $this->render('property/index.html.twig', [
            'properties' => $properties,
            'filters' => $filters,
        ]);
    }

    #[Route('/new', name: 'app_property_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $property = new Property();
        $property->setAgent($this->getUser()->getAgent());
        
        $form = $this->createForm(PropertyType::class, $property);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($property);
            $entityManager->flush();

            $this->addFlash('success', 'Property created successfully.');
            return $this->redirectToRoute('app_property_show', ['id' => $property->getId()]);
        }

        return $this->render('property/new.html.twig', [
            'property' => $property,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_property_show', methods: ['GET'])]
    public function show(Property $property): Response
    {
        return $this->render('property/show.html.twig', [
            'property' => $property,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_property_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function edit(Request $request, Property $property, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('edit', $property);

        $form = $this->createForm(PropertyType::class, $property);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $property->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Property updated successfully.');
            return $this->redirectToRoute('app_property_show', ['id' => $property->getId()]);
        }

        return $this->render('property/edit.html.twig', [
            'property' => $property,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_property_delete', methods: ['POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function delete(Request $request, Property $property, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('delete', $property);

        if ($this->isCsrfTokenValid('delete'.$property->getId(), $request->request->get('_token'))) {
            $entityManager->remove($property);
            $entityManager->flush();
            $this->addFlash('success', 'Property deleted successfully.');
        }

        return $this->redirectToRoute('app_property_index');
    }

    #[Route('/{id}/images', name: 'app_property_images', methods: ['POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function uploadImages(Request $request, Property $property, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('edit', $property);

        $files = $request->files->get('images');
        if ($files) {
            foreach ($files as $file) {
                $image = new PropertyImage();
                $image->setImageFile($file);
                $image->setProperty($property);
                $entityManager->persist($image);
            }
            $entityManager->flush();
            $this->addFlash('success', 'Images uploaded successfully.');
        }

        return $this->redirectToRoute('app_property_edit', ['id' => $property->getId()]);
    }

    #[Route('/{id}/status/{status}', name: 'app_property_status', methods: ['POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function updateStatus(Request $request, Property $property, string $status, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('edit', $property);

        if ($this->isCsrfTokenValid('status'.$property->getId(), $request->request->get('_token'))) {
            $property->setStatus($status);
            $property->setUpdatedAt(new \DateTime());
            $entityManager->flush();
            $this->addFlash('success', 'Property status updated successfully.');
        }

        return $this->redirectToRoute('app_property_show', ['id' => $property->getId()]);
    }

    #[Route('/search', name: 'app_property_search', methods: ['GET'])]
    public function search(Request $request, PropertyRepository $propertyRepository): Response
    {
        $filters = $request->query->all();
        $properties = $propertyRepository->findByFilters($filters);

        if ($request->isXmlHttpRequest()) {
            return $this->render('property/_list.html.twig', [
                'properties' => $properties,
            ]);
        }

        return $this->render('property/search.html.twig', [
            'properties' => $properties,
            'filters' => $filters,
        ]);
    }
} 