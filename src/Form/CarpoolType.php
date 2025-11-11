<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Car;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Carpool;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class CarpoolType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('car', EntityType::class, [
                'class' => Car::class,
                'choices' => $options['user_cars'], // voitures de l'utilisateur uniquement
                'choice_label' => function (Car $car) {
                    return sprintf('%s %s (%s) - %d places', $car->getBrand(), $car->getModel(), $car->getRegistration(), $car->getSeats());
                },
                'placeholder' => 'Choisissez une voiture',
                'label' => 'Voiture',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez choisir une voiture.']),
                ],
                'required' => true,
            ])
            ->add('departureDate', DateType::class, [
                'label' => 'Date de départ',
                'widget' => 'single_text',
                'html5' => true,
                'input' => 'datetime', 
                'required' => true,
            ])
            ->add('departureLocation', TextType::class, [
                'label' => 'Lieu de départ',
                'required' => true,
            ])
            ->add('departureTime', TimeType::class, [
                'label' => 'Heure de départ',
                'widget' => 'single_text',
                'html5' => true,
                'input' => 'datetime',
                'required' => true,
            ])
            ->add('arrivalDate', DateType::class, [
                'label' => 'Date d\'arrivée',
                'widget' => 'single_text',
                'html5' => true,
                'input' => 'datetime',
                'required' => true,
            ])
            ->add('arrivalLocation', TextType::class, [
                'label' => 'Lieu d\'arrivée',
                'required' => true,
            ])
            ->add('arrivalTime', TimeType::class, [
                'label' => 'Heure d\'arrivée',
                'widget' => 'single_text',
                'html5' => true,
                'input' => 'datetime',
                'required' => true,
            ])
            ->add('pricePerSeat', IntegerType::class, [
                'label' => 'Prix par place (en crédits)',
                'attr' => [
                    'step' => 5,
                    'min' => 5,
                ],
                'required' => true,
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
            ]);
        }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Carpool::class,
            'user_cars' => [], // option personnalisée pour passer les voitures
        ]);
    }
}