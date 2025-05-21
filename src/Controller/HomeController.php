<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        $search = $request->query->get('search', '');
        $minPrice = $request->query->get('min_price') ? (float) $request->query->get('min_price') : null;
        $maxPrice = $request->query->get('max_price') ? (float) $request->query->get('max_price') : null;

        $products = $productRepository->findByFilters($search, $minPrice, $maxPrice);
        
        // Debug output
        dump($products);

        return $this->render('home/index.html.twig', [
            'products' => $products,
            'search' => $search,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
        ]);
    }
} 