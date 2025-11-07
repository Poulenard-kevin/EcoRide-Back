<?php

namespace App\Form;

use App\Entity\Car;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class CarType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('brand')
            ->add('model')
            ->add('color')
            ->add('fuelType', ChoiceType::class, [
                'choices' => [
                    'Électrique' => 'Electrique',
                    'Thermique' => 'Thermique',
                    'Hybride' => 'Hybride',
                ],
                'label' => 'Type d\'énergie',
                'placeholder' => 'Choisissez un type d\'énergie',
                'required' => true,
            ])
            ->add('registration')
            ->add('seats')
            ->add('driverPreferences', ChoiceType::class, [
                'choices' => [
                    'Fumeur' => 'Fumeur',
                    'Animal' => 'Animal',
                    'Musique' => 'Musique',
                ],
                'expanded' => true,   // cases à cocher
                'multiple' => true,   // plusieurs choix possibles
                'label' => 'Préférences chauffeur',
                'required' => false,
            ])
            ->add('otherPreferences', TextType::class, [
                'required' => false,
                'label' => 'Autres préférences',
                'attr' => [
                    'maxlength' => 255,
                    'placeholder' => 'Ex : Parler, sport mécanique...',
                ],
            ])
            ->add('owner', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return $user->getLastName() . ' ' . $user->getFirstName();
                },
                'placeholder' => 'Choisissez un propriétaire',
                'required' => false, // selon si tu veux rendre ce champ obligatoire
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Car::class,
        ]);
    }
}