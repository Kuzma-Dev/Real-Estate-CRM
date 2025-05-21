<?php

$products = [
    'mens-tshirt' => 'https://images.pexels.com/photos/1004014/pexels-photo-1004014.jpeg',
    'womens-dress' => 'https://images.pexels.com/photos/1488463/pexels-photo-1488463.jpeg',
    'jeans' => 'https://images.pexels.com/photos/2983464/pexels-photo-2983464.jpeg',
    'sweater' => 'https://images.pexels.com/photos/6311387/pexels-photo-6311387.jpeg',
    'jacket' => 'https://images.pexels.com/photos/2294342/pexels-photo-2294342.jpeg',
    'shorts' => 'https://images.pexels.com/photos/1598507/pexels-photo-1598507.jpeg',
    'skirt' => 'https://images.pexels.com/photos/972995/pexels-photo-972995.jpeg',
    'blouse' => 'https://images.pexels.com/photos/5480693/pexels-photo-5480693.jpeg',
    'hoodie' => 'https://images.pexels.com/photos/6311387/pexels-photo-6311387.jpeg',
    'polo-shirt' => 'https://images.pexels.com/photos/5480693/pexels-photo-5480693.jpeg'
];

$outputDir = __DIR__ . '/public/images/products/';

if (!file_exists($outputDir)) {
    mkdir($outputDir, 0777, true);
}

$context = stream_context_create([
    'http' => [
        'header' => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    ]
]);

foreach ($products as $product => $url) {
    $outputFile = $outputDir . $product . '.jpg';
    
    try {
        $imageData = @file_get_contents($url, false, $context);
        
        if ($imageData === false) {
            echo "Error downloading {$product}\n";
            continue;
        }
        
        if (file_put_contents($outputFile, $imageData)) {
            echo "Successfully downloaded {$product}\n";
        } else {
            echo "Error saving {$product}\n";
        }
    } catch (Exception $e) {
        echo "Exception while downloading {$product}: " . $e->getMessage() . "\n";
    }
    
    // Add a small delay to be nice to the server
    usleep(500000); // 0.5 second delay
}

echo "Download complete!\n"; 