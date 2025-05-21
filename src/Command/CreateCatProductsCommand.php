<?php

namespace App\Command;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:create-cat-products',
    description: 'Creates sample cat products',
)]
class CreateCatProductsCommand extends Command
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
                'name' => 'Premium Cat Food',
                'description' => 'High-quality dry cat food with balanced nutrition for adult cats. Contains essential vitamins and minerals.',
                'price' => '24.99',
                'image' => 'https://images.unsplash.com/photo-1601758124510-52d02ddb7cbd?w=500&auto=format&fit=crop&q=60'
            ],
            [
                'name' => 'Cat Tree Tower',
                'description' => 'Multi-level cat tree with scratching posts, platforms, and a cozy hideaway. Perfect for climbing and scratching.',
                'price' => '89.99',
                'image' => 'https://images.unsplash.com/photo-1615789591457-74a63395c990?w=500&auto=format&fit=crop&q=60'
            ],
            [
                'name' => 'Interactive Cat Toy Set',
                'description' => 'Set of 5 interactive toys including feather wands, balls, and mice. Keeps your cat entertained and active.',
                'price' => '19.99',
                'image' => 'https://images.unsplash.com/photo-1592194996308-7b43878e84a6?w=500&auto=format&fit=crop&q=60'
            ],
            [
                'name' => 'Luxury Cat Bed',
                'description' => 'Soft and comfortable cat bed with plush material. Perfect for napping and relaxation.',
                'price' => '39.99',
                'image' => 'https://images.unsplash.com/photo-1592194996308-7b43878e84a6?w=500&auto=format&fit=crop&q=60'
            ],
            [
                'name' => 'Cat Grooming Kit',
                'description' => 'Complete grooming kit including brush, comb, nail clippers, and shampoo. Essential for cat care.',
                'price' => '29.99',
                'image' => 'https://images.unsplash.com/photo-1592194996308-7b43878e84a6?w=500&auto=format&fit=crop&q=60'
            ],
            [
                'name' => 'Automatic Cat Feeder',
                'description' => 'Programmable automatic cat feeder with 6 meal compartments. Perfect for busy pet owners.',
                'price' => '49.99',
                'image' => 'https://images.unsplash.com/photo-1592194996308-7b43878e84a6?w=500&auto=format&fit=crop&q=60'
            ],
            [
                'name' => 'Cat Carrier Backpack',
                'description' => 'Comfortable and stylish cat carrier backpack with ventilation and viewing window.',
                'price' => '59.99',
                'image' => 'https://images.unsplash.com/photo-1592194996308-7b43878e84a6?w=500&auto=format&fit=crop&q=60'
            ],
            [
                'name' => 'Cat Dental Care Kit',
                'description' => 'Complete dental care kit with toothbrush, toothpaste, and dental treats for cats.',
                'price' => '34.99',
                'image' => 'https://images.unsplash.com/photo-1592194996308-7b43878e84a6?w=500&auto=format&fit=crop&q=60'
            ],
            [
                'name' => 'Smart Cat Water Fountain',
                'description' => 'Automatic water fountain with filter and LED light. Encourages healthy water consumption.',
                'price' => '44.99',
                'image' => 'https://images.unsplash.com/photo-1592194996308-7b43878e84a6?w=500&auto=format&fit=crop&q=60'
            ],
            [
                'name' => 'Cat Window Perch',
                'description' => 'Comfortable window perch with suction cups. Perfect for cats to watch birds and enjoy sunlight.',
                'price' => '29.99',
                'image' => 'https://images.unsplash.com/photo-1592194996308-7b43878e84a6?w=500&auto=format&fit=crop&q=60'
            ],
        ];

        foreach ($products as $productData) {
            $product = new Product();
            $product->setName($productData['name']);
            $product->setDescription($productData['description']);
            $product->setPrice($productData['price']);
            $product->setImage($productData['image']);
            $product->setCreatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($product);
        }

        $this->entityManager->flush();

        $output->writeln('Successfully created 10 cat products!');

        return Command::SUCCESS;
    }
} 