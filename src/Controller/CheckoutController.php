<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/checkout')]
class CheckoutController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CartRepository $cartRepository
    ) {}

    #[Route('', name: 'app_checkout')]
    public function index(): Response
    {
        $cart = $this->cartRepository->findOneBy([]);
        
        if (!$cart || $cart->getItems()->isEmpty()) {
            $this->addFlash('error', 'Your cart is empty.');
            return $this->redirectToRoute('app_cart');
        }

        return $this->render('checkout/index.html.twig', [
            'cart' => $cart
        ]);
    }

    #[Route('/process', name: 'app_checkout_process', methods: ['POST'])]
    public function process(Request $request): Response
    {
        $cart = $this->cartRepository->findOneBy([]);
        
        if (!$cart || $cart->getItems()->isEmpty()) {
            return $this->json([
                'success' => false,
                'message' => 'Cart is empty'
            ], 400);
        }

        // Get form data
        $data = json_decode($request->getContent(), true);
        
        if (!$this->validateCheckoutData($data)) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid checkout data'
            ], 400);
        }

        try {
            // Create new order
            $order = new Order();
            $order->setCustomerName($data['name']);
            $order->setCustomerEmail($data['email']);
            $order->setCustomerAddress($data['address']);
            $order->setCreatedAt(new \DateTimeImmutable());
            $order->setStatus('pending');
            $order->setTotal($cart->getItems()->reduce(function($total, $item) {
                return $total + ($item->getProduct()->getPrice() * $item->getQuantity());
            }, 0));

            // Convert cart items to order items
            foreach ($cart->getItems() as $cartItem) {
                $orderItem = new OrderItem();
                $orderItem->setOrder($order);
                $orderItem->setProduct($cartItem->getProduct());
                $orderItem->setQuantity($cartItem->getQuantity());
                $orderItem->setPrice($cartItem->getProduct()->getPrice());
                
                $this->entityManager->persist($orderItem);
            }

            // Save order
            $this->entityManager->persist($order);
            
            // Clear cart
            foreach ($cart->getItems() as $item) {
                $this->entityManager->remove($item);
            }
            $this->entityManager->remove($cart);
            
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Order placed successfully',
                'orderId' => $order->getId()
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error processing order: ' . $e->getMessage()
            ], 500);
        }
    }

    private function validateCheckoutData(array $data): bool
    {
        return isset($data['name']) && !empty($data['name']) &&
               isset($data['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL) &&
               isset($data['address']) && !empty($data['address']);
    }
} 