<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/')]
final class ProductListingController extends AbstractController
{
    #[Route('/products', name: 'app_product_listing', methods: ['GET'])]
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        $search = $request->query->get('search');
        $minPrice = $request->query->get('minPrice');
        $maxPrice = $request->query->get('maxPrice');

        $products = $productRepository->findByFilters($search, $minPrice, $maxPrice);

        return $this->render('product_listing/index.html.twig', [
            'products' => $products,
        ]);
    }
} 