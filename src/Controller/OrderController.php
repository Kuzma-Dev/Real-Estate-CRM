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
use App\Entity\Cart;

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
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $cart = $this->getCart($entityManager);
        if (!$cart || $cart->getItems()->isEmpty()) {
            $this->addFlash('error', 'Your cart is empty');
            return $this->redirectToRoute('app_cart_index');
        }

        $order = new Order();
        $order->setCustomer($this->getUser());
        $order->setStatus('pending');
        $order->setTotal($cart->getTotal());

        if ($request->isMethod('POST')) {
            $order->setFirstName($request->request->get('firstName'));
            $order->setLastName($request->request->get('lastName'));
            $order->setEmail($request->request->get('email'));
            $order->setAddress($request->request->get('address'));
            $order->setPhone($request->request->get('phone'));

            foreach ($cart->getItems() as $cartItem) {
                $orderItem = new OrderItem();
                $orderItem->setOrder($order);
                $orderItem->setProduct($cartItem->getProduct());
                $orderItem->setQuantity($cartItem->getQuantity());
                $orderItem->setPrice($cartItem->getProduct()->getPrice());
                $entityManager->persist($orderItem);
            }

            $entityManager->persist($order);
            $entityManager->remove($cart);
            $entityManager->flush();

            $this->addFlash('success', 'Your order has been placed successfully');
            return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
        }

        return $this->render('order/new.html.twig', [
            'cart' => $cart,
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

    private function getCart(EntityManagerInterface $entityManager): ?Cart
    {
        $user = $this->getUser();
        if (!$user) {
            return null;
        }

        $cart = $entityManager->getRepository(Cart::class)->findOneBy(['customer' => $user]);
        return $cart;
    }
}
