<?php

namespace App\Form;

use App\Entity\Booking;
use App\Entity\Carpool;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'] ?? false;

        $builder
            ->add('reservedSeats', IntegerType::class, [
                'label' => 'Nombre de places',
                'attr' => [
                    'min' => 1,
                    'placeholder' => 'Ex: 1',
                ],
                'empty_data' => 1,
            ]);

        // N'ajouter le champ passenger que si l'option allow_passenger est vraie (admin)
        if (!empty($options['allow_passenger'])) {
            $builder->add('passenger', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $u) {
                    // adapte selon ta entité User (getFullName ou getLastName/getFirstName)
                    return method_exists($u, 'getFullName') ? $u->getFullName() : ($u->getFirstName() . ' ' . $u->getLastName() ?? $u->getEmail());
                },
                'label' => 'Passager (admin)',
                'required' => true,
                'placeholder' => 'Choisir un passager',
            ]);
        }

        // Si besoin afficher le statut en édition (optionnel)
        if ($isEdit) {
            // ->add('status', ChoiceType::class, [...])
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Booking::class,
            'is_edit' => false,
            'allow_passenger' => false, // par défaut : champ passenger non affiché
        ]);
    }
}