<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/cart')]
class CartController extends AbstractController
{
    #[Route('/', name: 'app_cart_index', methods: ['GET'])]
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        $cart = $request->getSession()->get('cart', []);
        $products = [];
        $total = 0;

        foreach ($cart as $id => $quantity) {
            $product = $productRepository->find($id);
            if ($product) {
                $products[] = [
                    'product' => $product,
                    'quantity' => $quantity
                ];
                $total += $product->getPrice() * $quantity;
            }
        }

        return $this->render('cart/index.html.twig', [
            'products' => $products,
            'total' => $total
        ]);
    }

    #[Route('/add/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(Request $request, Product $product): Response
    {
        $cart = $request->getSession()->get('cart', []);
        $id = $product->getId();
        $quantity = (int) $request->request->get('quantity', 1);

        if ($quantity <= 0) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid quantity'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (isset($cart[$id])) {
            $cart[$id] += $quantity;
        } else {
            $cart[$id] = $quantity;
        }

        $request->getSession()->set('cart', $cart);

        return $this->json([
            'success' => true,
            'message' => 'Product added to cart'
        ]);
    }

    #[Route('/remove/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(Request $request, Product $product): Response
    {
        $cart = $request->getSession()->get('cart', []);
        $id = $product->getId();

        if (isset($cart[$id])) {
            if ($cart[$id] > 1) {
                $cart[$id]--;
            } else {
                unset($cart[$id]);
            }
        }

        $request->getSession()->set('cart', $cart);

        return $this->json([
            'success' => true,
            'message' => 'Product removed from cart'
        ]);
    }

    #[Route('/clear', name: 'app_cart_clear', methods: ['POST'])]
    public function clear(Request $request): Response
    {
        $request->getSession()->remove('cart');

        return $this->json([
            'success' => true,
            'message' => 'Cart cleared'
        ]);
    }
}
