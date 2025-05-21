<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/order')]
class OrderController extends AbstractController
{
    #[Route('/', name: 'app_order_index', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): Response
    {
        $orders = $orderRepository->findBy(['customer' => $this->getUser()], ['createdAt' => 'DESC']);

        return $this->render('order/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/new', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ProductRepository $productRepository, EntityManagerInterface $entityManager): Response
    {
        $cart = $request->getSession()->get('cart', []);
        
        if (empty($cart)) {
            $this->addFlash('error', 'Your cart is empty');
            return $this->redirectToRoute('app_cart_index');
        }

        if ($request->isMethod('POST')) {
            $order = new Order();
            $order->setCustomer($this->getUser());
            $order->setStatus('pending');
            $order->setTotal(0);

            $total = 0;
            foreach ($cart as $id => $quantity) {
                $product = $productRepository->find($id);
                if ($product) {
                    $orderItem = new OrderItem();
                    $orderItem->setOrder($order);
                    $orderItem->setProduct($product);
                    $orderItem->setQuantity($quantity);
                    $orderItem->setPrice($product->getPrice());
                    
                    $entityManager->persist($orderItem);
                    $total += $product->getPrice() * $quantity;
                }
            }

            $order->setTotal($total);
            $entityManager->persist($order);
            $entityManager->flush();

            // Clear the cart
            $request->getSession()->remove('cart');

            $this->addFlash('success', 'Your order has been placed successfully');
            return $this->redirectToRoute('app_order_index');
        }

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

        return $this->render('order/new.html.twig', [
            'products' => $products,
            'total' => $total
        ]);
    }

    #[Route('/{id}', name: 'app_order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        if ($order->getCustomer() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('You cannot view this order');
        }

        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }
}
