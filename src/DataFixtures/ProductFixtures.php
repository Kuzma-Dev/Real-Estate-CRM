<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $products = [
            [
                'name' => 'Men\'s T-Shirt',
                'description' => 'Comfortable cotton t-shirt for everyday wear',
                'price' => 24.99,
                'imageName' => 'mens-tshirt.jpg',
                'stock' => 100
            ],
            [
                'name' => 'Women\'s Dress',
                'description' => 'Elegant summer dress with floral pattern',
                'price' => 49.99,
                'imageName' => 'womens-dress.jpg',
                'stock' => 50
            ],
            [
                'name' => 'Classic Jeans',
                'description' => 'High-quality denim jeans with perfect fit',
                'price' => 59.99,
                'imageName' => 'jeans.jpg',
                'stock' => 75
            ],
            [
                'name' => 'Wool Sweater',
                'description' => 'Warm and cozy wool sweater for cold weather',
                'price' => 45.99,
                'imageName' => 'sweater.jpg',
                'stock' => 60
            ],
            [
                'name' => 'Leather Jacket',
                'description' => 'Stylish leather jacket with modern design',
                'price' => 129.99,
                'imageName' => 'jacket.jpg',
                'stock' => 30
            ],
            [
                'name' => 'Summer Shorts',
                'description' => 'Light and comfortable shorts for hot days',
                'price' => 29.99,
                'imageName' => 'shorts.jpg',
                'stock' => 80
            ],
            [
                'name' => 'Silk Blouse',
                'description' => 'Luxurious silk blouse with delicate details',
                'price' => 69.99,
                'imageName' => 'blouse.jpg',
                'stock' => 40
            ],
            [
                'name' => 'Hoodie',
                'description' => 'Comfortable hoodie for casual everyday wear',
                'price' => 34.99,
                'imageName' => 'hoodie.jpg',
                'stock' => 90
            ],
            [
                'name' => 'Polo Shirt',
                'description' => 'Classic polo shirt for smart casual look',
                'price' => 29.99,
                'imageName' => 'polo-shirt.jpg',
                'stock' => 70
            ],
            [
                'name' => 'Skirt',
                'description' => 'Elegant skirt for formal occasions',
                'price' => 39.99,
                'imageName' => 'skirt.jpg',
                'stock' => 55
            ]
        ];

        foreach ($products as $productData) {
            $product = new Product();
            $product->setName($productData['name']);
            $product->setDescription($productData['description']);
            $product->setPrice($productData['price']);
            $product->setImageName($productData['imageName']);
            $product->setStock($productData['stock']);
            $manager->persist($product);
        }

        $manager->flush();
    }
} 