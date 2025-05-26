<?php

namespace App\Form;

use App\Entity\Activity;
use App\Entity\Client;
use App\Entity\Property;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'required' => true,
                'choices' => [
                    'Viewing' => 'viewing',
                    'Meeting' => 'meeting',
                    'Phone Call' => 'phone_call',
                    'Email' => 'email',
                    'Document Signing' => 'document_signing',
                    'Other' => 'other',
                ],
            ])
            ->add('title', TextType::class, [
                'label' => 'Title',
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
            ])
            ->add('startTime', DateTimeType::class, [
                'label' => 'Start Time',
                'required' => true,
                'widget' => 'single_text',
            ])
            ->add('endTime', DateTimeType::class, [
                'label' => 'End Time',
                'required' => true,
                'widget' => 'single_text',
            ])
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => function(Client $client) {
                    return $client->getFirstName() . ' ' . $client->getLastName();
                },
                'label' => 'Client',
                'required' => true,
            ])
            ->add('property', EntityType::class, [
                'class' => Property::class,
                'choice_label' => 'title',
                'label' => 'Property',
                'required' => false,
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'required' => true,
                'choices' => [
                    'Scheduled' => 'scheduled',
                    'In Progress' => 'in_progress',
                    'Completed' => 'completed',
                    'Cancelled' => 'cancelled',
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Activity::class,
        ]);
    }
} 