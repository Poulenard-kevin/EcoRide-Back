<?php

namespace App\Form;

use App\Entity\Booking;
use App\Entity\User;
use App\Entity\Carpool;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('bookingDate', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date de réservation',
            ])
            ->add('reservedSeats', IntegerType::class, [
                'label' => 'Nombre de places réservées',
                'attr' => ['min' => 1],
            ])
            ->add('status', ChoiceType::class, [
                'choices' => array_flip(Booking::getStatusLabels()),
                'label' => 'Statut',
            ])
            /*->add('passenger', EntityType::class, [
                'class' => User::class,
                'choice_label' => fn(User $user) => $user->getFirstName() . ' ' . $user->getLastName(),
                'label' => 'Passager',
                'placeholder' => 'Choisissez un passager',
            ])*/
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Booking::class,
        ]);
    }
}