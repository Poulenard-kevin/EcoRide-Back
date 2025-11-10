<?php

namespace App\Form;

use App\Entity\Booking;
use App\Entity\Carpool;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'] ?? false;

        $builder
            ->add('carpool', EntityType::class, [
                'class' => Carpool::class,
                'choice_label' => fn(Carpool $carpool) => sprintf(
                    '%s → %s (%s)',
                    $carpool->getDepartureLocation(),
                    $carpool->getArrivalLocation(),
                    $carpool->getDepartureDate() ? $carpool->getDepartureDate()->format('d/m/Y') : ''
                ),
                'label' => 'Covoiturage',
                'placeholder' => 'Choisissez un covoiturage',
            ])
            ->add('reservedSeats', IntegerType::class, [
                'label' => 'Nombre de places',
                'attr' => [
                    'min' => 1,
                    'placeholder' => 'Ex: 2',
                ],
            ]);

        // Afficher le statut uniquement en édition
        if ($isEdit) {
            $builder->add('status', ChoiceType::class, [
                'choices' => array_flip(Booking::getStatusLabels()),
                'label' => 'Statut',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Booking::class,
            'is_edit' => false, // Par défaut, on est en mode création
        ]);
    }
}