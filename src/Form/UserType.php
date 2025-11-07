<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType; 
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('lastName')
            ->add('firstName')
            ->add('email')
            ->add('password', PasswordType::class, [
                'mapped' => false,
                'required' => false,
                'empty_data' => '',
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => 'Laissez vide pour conserver le mot de passe actuel',
                ],
                'label' => 'Mot de passe',
            ])
            ->add('role')
            ->add('averageRating')
            ->add('about', TextareaType::class, [
                'required' => false,
                'label' => 'À propos de vous',
                'attr' => ['rows' => 5, 'placeholder' => 'Parlez un peu de vous...', 'maxlength' => 500,],
            ])
            ->add('lastPasswordResetRequestAt')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}