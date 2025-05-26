<?php

namespace App\Form;

use App\Entity\Deal;
use App\Entity\Client;
use App\Entity\Property;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DealType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('property', EntityType::class, [
                'class' => Property::class,
                'choice_label' => 'title',
                'label' => 'Property',
                'required' => true,
            ])
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => function(Client $client) {
                    return $client->getFirstName() . ' ' . $client->getLastName();
                },
                'label' => 'Client',
                'required' => true,
            ])
            ->add('dealType', ChoiceType::class, [
                'label' => 'Deal Type',
                'required' => true,
                'choices' => [
                    'Sale' => 'sale',
                    'Rental' => 'rental',
                    'Lease' => 'lease',
                ],
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Price',
                'required' => true,
                'currency' => 'USD',
            ])
            ->add('commission', MoneyType::class, [
                'label' => 'Commission',
                'required' => true,
                'currency' => 'USD',
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'required' => true,
                'choices' => [
                    'Pending' => 'pending',
                    'Under Contract' => 'under_contract',
                    'Completed' => 'completed',
                    'Cancelled' => 'cancelled',
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
            ])
            ->add('documents', CollectionType::class, [
                'label' => 'Documents',
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
            'data_class' => Deal::class,
        ]);
    }
} 