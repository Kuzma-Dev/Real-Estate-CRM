<?php

$products = [
    'mens-tshirt',
    'womens-dress',
    'jeans',
    'sweater',
    'jacket',
    'shorts',
    'skirt',
    'blouse',
    'hoodie',
    'polo-shirt'
];

$outputDir = __DIR__ . '/public/images/products/';

if (!file_exists($outputDir)) {
    mkdir($outputDir, 0777, true);
}

echo "Images are already downloaded in {$outputDir}\n";
echo "Available products:\n";
foreach ($products as $product) {
    echo "- {$product}\n";
} 