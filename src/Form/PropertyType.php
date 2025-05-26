<?php

namespace App\Form;

use App\Entity\Property;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PropertyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Title',
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Price',
                'required' => true,
                'currency' => 'USD',
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'required' => true,
                'choices' => [
                    'Available' => 'available',
                    'Under Contract' => 'under_contract',
                    'Sold' => 'sold',
                    'Off Market' => 'off_market',
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
            ->add('bedrooms', IntegerType::class, [
                'label' => 'Bedrooms',
                'required' => true,
                'attr' => ['min' => 0],
            ])
            ->add('bathrooms', IntegerType::class, [
                'label' => 'Bathrooms',
                'required' => true,
                'attr' => ['min' => 0],
            ])
            ->add('squareFootage', IntegerType::class, [
                'label' => 'Square Footage',
                'required' => true,
                'attr' => ['min' => 0],
            ])
            ->add('yearBuilt', IntegerType::class, [
                'label' => 'Year Built',
                'required' => false,
                'attr' => ['min' => 1800, 'max' => date('Y')],
            ])
            ->add('address', TextType::class, [
                'label' => 'Address',
                'required' => true,
            ])
            ->add('city', TextType::class, [
                'label' => 'City',
                'required' => true,
            ])
            ->add('state', TextType::class, [
                'label' => 'State',
                'required' => true,
            ])
            ->add('zipCode', TextType::class, [
                'label' => 'ZIP Code',
                'required' => true,
            ])
            ->add('features', CollectionType::class, [
                'label' => 'Features',
                'entry_type' => TextType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Property::class,
        ]);
    }
} 