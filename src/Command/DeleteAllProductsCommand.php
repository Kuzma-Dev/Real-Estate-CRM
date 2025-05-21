<?php

namespace App\Command;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:delete-all-products',
    description: 'Deletes all products from the database',
)]
class DeleteAllProductsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $products = $this->entityManager->getRepository(Product::class)->findAll();
        
        foreach ($products as $product) {
            $this->entityManager->remove($product);
        }
        
        $this->entityManager->flush();
        
        $output->writeln('All products have been deleted successfully!');
        
        return Command::SUCCESS;
    }
} 