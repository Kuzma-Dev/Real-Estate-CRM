<?php

namespace App\Command;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:create-clothes-products',
    description: 'Creates sample clothes products',
)]
class CreateClothesProductsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $products = [
                [
                    'name' => 'Classic White T-Shirt',
                    'description' => 'Premium cotton t-shirt with a comfortable fit. Perfect for everyday wear.',
                    'price' => '19.99',
                    'image' => 'mens-tshirt.jpg'
                ],
                [
                    'name' => 'Slim Fit Jeans',
                    'description' => 'Modern slim fit jeans with stretch comfort. Versatile for any occasion.',
                    'price' => '49.99',
                    'image' => 'jeans.jpg'
                ],
                [
                    'name' => 'Casual Hoodie',
                    'description' => 'Warm and cozy hoodie for everyday wear. Features a kangaroo pocket and adjustable hood.',
                    'price' => '39.99',
                    'image' => 'hoodie.jpg'
                ],
                [
                    'name' => 'Summer Dress',
                    'description' => 'Light and flowy summer dress with floral pattern. Perfect for warm weather.',
                    'price' => '45.99',
                    'image' => 'womens-dress.jpg'
                ],
                [
                    'name' => 'Leather Jacket',
                    'description' => 'Classic leather jacket with modern styling. Features multiple pockets and a comfortable fit.',
                    'price' => '129.99',
                    'image' => 'jacket.jpg'
                ],
                [
                    'name' => 'Polo Shirt',
                    'description' => 'Classic polo shirt for a smart casual look. Made from breathable cotton.',
                    'price' => '29.99',
                    'image' => 'polo-shirt.jpg'
                ],
                [
                    'name' => 'Wool Sweater',
                    'description' => 'Warm wool sweater for cold weather. Features a classic design and comfortable fit.',
                    'price' => '59.99',
                    'image' => 'sweater.jpg'
                ],
                [
                    'name' => 'Pleated Skirt',
                    'description' => 'Elegant pleated skirt for formal occasions. Features a comfortable elastic waistband.',
                    'price' => '34.99',
                    'image' => 'skirt.jpg'
                ],
                [
                    'name' => 'Silk Blouse',
                    'description' => 'Luxurious silk blouse with delicate details. Perfect for professional settings.',
                    'price' => '69.99',
                    'image' => 'blouse.jpg'
                ],
                [
                    'name' => 'Casual Shorts',
                    'description' => 'Comfortable shorts for warm weather. Features a relaxed fit and multiple pockets.',
                    'price' => '24.99',
                    'image' => 'shorts.jpg'
                ],
            ];

            $output->writeln('Starting to create products...');

            foreach ($products as $index => $productData) {
                try {
                    $product = new Product();
                    $product->setName($productData['name']);
                    $product->setDescription($productData['description']);
                    $product->setPrice($productData['price']);
                    $product->setImage($productData['image']);
                    $product->setCreatedAt(new \DateTimeImmutable());

                    $this->entityManager->persist($product);
                    $output->writeln(sprintf('Created product %d: %s', $index + 1, $productData['name']));
                } catch (\Exception $e) {
                    $output->writeln(sprintf('<error>Error creating product %d: %s</error>', $index + 1, $e->getMessage()));
                    throw $e;
                }
            }

            $this->entityManager->flush();
            $output->writeln('<info>Successfully created 10 clothes products!</info>');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));
            return Command::FAILURE;
        }
    }
} 