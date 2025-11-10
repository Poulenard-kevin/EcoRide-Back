<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType; 
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isEdit = $options['is_edit'] ?? false;
        $isAdmin = $options['is_admin'] ?? false;

        $builder
            ->add('lastName', null, [
                'label' => 'Nom',
            ])
            ->add('firstName', null, [
                'label' => 'Prénom',
            ])
            ->add('email', null, [
                'label' => 'Adresse e-mail',
            ])
            ->add('password', PasswordType::class, [
                'mapped' => false,
                'required' => !$isEdit,
                'empty_data' => '',
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => $isEdit ? 'Laissez vide pour conserver le mot de passe actuel' : '',
                ],
                'label' => 'Mot de passe',
            ]);

        if ($isAdmin) {
            $builder->add('role', ChoiceType::class, [
                'label' => 'Rôle',
                'choices' => [
                    'Utilisateur' => 'ROLE_USER',
                    'Employé' => 'ROLE_EMPLOYE',
                ],
                'expanded' => false,
                'multiple' => false,
            ]);
        }

        $builder->add('averageRating', null, [
            'label' => 'Note moyenne',
        ])
        ->add('about', TextareaType::class, [
            'required' => false,
            'label' => 'À propos de vous',
            'attr' => [
                'rows' => 5,
                'placeholder' => 'Parlez un peu de vous...',
                'maxlength' => 500,
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
            'is_admin' => false,
        ]);
    }
}