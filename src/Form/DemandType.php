<?php

namespace App\Form;

use App\Entity\Demand;
use App\Entity\Client;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DemandType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => function(Client $client) {
                    return $client->getFirstName() . ' ' . $client->getLastName();
                },
                'label' => 'Client',
                'required' => true,
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'required' => true,
                'choices' => [
                    'Buy' => 'buy',
                    'Rent' => 'rent',
                    'Sell' => 'sell',
                ],
            ])
            ->add('propertyType', ChoiceType::class, [
                'label' => 'Property Type',
                'required' => true,
                'choices' => [
                    'House' => 'house',
                    'Apartment' => 'apartment',
                    'Condo' => 'condo',
                    'Townhouse' => 'townhouse',
                    'Land' => 'land',
                    'Commercial' => 'commercial',
                ],
            ])
            ->add('minPrice', MoneyType::class, [
                'label' => 'Minimum Price',
                'required' => true,
                'currency' => 'USD',
            ])
            ->add('maxPrice', MoneyType::class, [
                'label' => 'Maximum Price',
                'required' => true,
                'currency' => 'USD',
            ])
            ->add('location', TextType::class, [
                'label' => 'Location',
                'required' => true,
            ])
            ->add('minBedrooms', IntegerType::class, [
                'label' => 'Minimum Bedrooms',
                'required' => true,
                'attr' => ['min' => 0],
            ])
            ->add('minBathrooms', IntegerType::class, [
                'label' => 'Minimum Bathrooms',
                'required' => true,
                'attr' => ['min' => 0],
            ])
            ->add('minSquareFootage', IntegerType::class, [
                'label' => 'Minimum Square Footage',
                'required' => true,
                'attr' => ['min' => 0],
            ])
            ->add('features', CollectionType::class, [
                'label' => 'Features',
                'entry_type' => TextType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'required' => false,
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'required' => true,
                'choices' => [
                    'Active' => 'active',
                    'In Progress' => 'in_progress',
                    'Completed' => 'completed',
                    'Cancelled' => 'cancelled',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Demand::class,
        ]);
    }
} 