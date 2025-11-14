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
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class CarType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('brand', null, [
                'label' => 'Marque',
            ])
            ->add('model', null, [
                'label' => 'Modèle',
            ])
            ->add('color', null, [
                'label' => 'Couleur',
            ])
            ->add('fuelType', ChoiceType::class, [
                'choices' => [
                    'Électrique' => Car::FUEL_ELECTRIC,
                    'Thermique'  => Car::FUEL_THERMIC,
                    'Hybride'    => Car::FUEL_HYBRID,
                ],
                'label' => 'Type d\'énergie',
                'placeholder' => 'Choisissez un type d\'énergie',
                'required' => true,
            ])
            ->add('registration', null, [
                'label' => 'Immatriculation',
            ])
            ->add('seats', null, [
                'label' => 'Nombre de places',
            ])
            ->add('driverPreferences', ChoiceType::class, [
                'choices' => [
                    'Fumeur' => 'Fumeur',
                    'Animal' => 'Animal',
                    'Musique' => 'Musique',
                ],
                'expanded' => true,
                'multiple' => true,
                'label' => 'Préférences du chauffeur',
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
        ;

        // Ajouter le champ owner uniquement si is_admin = true
        if ($options['is_admin']) {
            $builder->add('owner', EntityType::class, [
                'class' => User::class,
                'choice_label' => fn(User $user) => $user->getFirstName() . ' ' . $user->getLastName(),
                'placeholder' => 'Choisissez un propriétaire',
                'label' => 'Propriétaire',
                'required' => true,
            ]);
        }

        // Event listener pour désactiver le champ owner si la voiture existe déjà (édition)
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $car = $event->getData();
            $form = $event->getForm();

            if ($car && $car->getId() && $options['is_admin']) {
                // voiture existante => désactiver le champ owner
                $form->add('owner', EntityType::class, [
                    'class' => User::class,
                    'choice_label' => fn(User $user) => $user->getFirstName() . ' ' . $user->getLastName(),
                    'label' => 'Propriétaire',
                    'disabled' => true,
                    'required' => true,
                ]);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Car::class,
            'is_admin' => false, // option par défaut
        ]);
    }
}