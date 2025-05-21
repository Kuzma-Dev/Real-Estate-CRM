<?php

namespace App\Command;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:create-sample-products',
    description: 'Creates sample products for the e-shop',
)]
class CreateSampleProductsCommand extends Command
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
                'name' => 'Smartphone X',
                'description' => 'Latest smartphone with amazing features',
                'price' => 999.99,
                'stock' => 50,
            ],
            [
                'name' => 'Laptop Pro',
                'description' => 'Powerful laptop for professionals',
                'price' => 1499.99,
                'stock' => 30,
            ],
            [
                'name' => 'Wireless Headphones',
                'description' => 'High-quality wireless headphones with noise cancellation',
                'price' => 199.99,
                'stock' => 100,
            ],
            [
                'name' => 'Smart Watch',
                'description' => 'Feature-rich smartwatch with health monitoring',
                'price' => 299.99,
                'stock' => 75,
            ],
            [
                'name' => 'Tablet Ultra',
                'description' => 'Slim and powerful tablet for work and entertainment',
                'price' => 799.99,
                'stock' => 40,
            ],
        ];

        foreach ($products as $productData) {
            $product = new Product();
            $product->setName($productData['name']);
            $product->setDescription($productData['description']);
            $product->setPrice($productData['price']);
            $product->setStock($productData['stock']);
            $product->setUpdatedAt(new \DateTime());

            $this->entityManager->persist($product);
        }

        $this->entityManager->flush();

        $output->writeln('Sample products have been created successfully!');

        return Command::SUCCESS;
    }
} 