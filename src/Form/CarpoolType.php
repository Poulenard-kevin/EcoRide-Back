<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\User;
use App\Entity\Car;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Carpool;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CarpoolType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('driver', EntityType::class, [
                'class' => User::class,
                'choice_label' => fn(User $user) => $user->getFirstName() . ' ' . $user->getLastName(),
                'placeholder' => 'Choisissez un chauffeur',
                'label' => 'Chauffeur',
            ])
            ->add('car', EntityType::class, [
                'class' => Car::class,
                'choice_label' => fn(Car $car) => $car->getBrand() . ' ' . $car->getModel() . ' (' . $car->getRegistration() . ')',
                'placeholder' => 'Choisissez une voiture',
                'label' => 'Voiture',
            ])
            ->add('departureDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de départ',
            ])
            ->add('departureLocation', TextType::class, [
                'label' => 'Lieu de départ',
            ])
            ->add('departureTime', TimeType::class, [
                'widget' => 'single_text',
                'label' => 'Heure de départ',
            ])
            ->add('arrivalDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date d\'arrivée',
            ])
            ->add('arrivalLocation', TextType::class, [
                'label' => 'Lieu d\'arrivée',
            ])
            ->add('arrivalTime', TimeType::class, [
                'widget' => 'single_text',
                'label' => 'Heure d\'arrivée',
            ])
            ->add('pricePerSeat', IntegerType::class, [
                'label' => 'Prix par place (en crédits)',
                'attr' => [
                    'step' => 5,
                    'min' => 5,
                ],
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 5,
                        'message' => 'Le prix doit être au minimum de 5 crédits.',
                    ]),
                    new Assert\Callback(function ($value, ExecutionContextInterface $context) {
                        if ($value % 5 !== 0) {
                            $context->buildViolation('Le prix doit être un multiple de 5 crédits.')
                                ->addViolation();
                        }
                    }),
                ],
            ])
            ->add('totalSeats', NumberType::class, [
                'label' => 'Nombre total de places',
            ])
            ->add('availableSeats', NumberType::class, [
                'label' => 'Nombre de places disponibles',
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Actif' => Carpool::STATUS_ACTIVE,
                    'Terminé' => Carpool::STATUS_COMPLETED,
                    'Annulé' => Carpool::STATUS_CANCELLED,
                    'Archivé' => Carpool::STATUS_ARCHIVED,
                ],
                'label' => 'Statut',
                'placeholder' => 'Choisissez un statut',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Carpool::class,
        ]);
    }
}