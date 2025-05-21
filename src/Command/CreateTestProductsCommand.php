<?php

namespace App\Command;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:create-test-products',
    description: 'Creates test products in the database',
)]
class CreateTestProductsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $products = [
            [
                'name' => 'Men\'s T-Shirt',
                'description' => 'A comfortable cotton t-shirt for men',
                'price' => '24.99',
                'image' => 'tshirt.jpg'
            ],
            [
                'name' => 'Women\'s Dress',
                'description' => 'An elegant summer dress for women',
                'price' => '49.99',
                'image' => 'dress.jpg'
            ],
            [
                'name' => 'Running Shoes',
                'description' => 'Lightweight running shoes for athletes',
                'price' => '89.99',
                'image' => 'shoes.jpg'
            ]
        ];

        foreach ($products as $productData) {
            $product = new Product();
            $product->setName($productData['name']);
            $product->setDescription($productData['description']);
            $product->setPrice($productData['price']);
            $product->setImage($productData['image']);
            $product->setCreatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($product);
            $output->writeln('Created product: ' . $productData['name']);
        }

        $this->entityManager->flush();
        $output->writeln('All products created successfully!');

        return Command::SUCCESS;
    }
} 