<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'] ?? false;
        $isAdmin = $options['is_admin'] ?? false;

        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
            ])
            ->add('password', PasswordType::class, [
                'label' => $isEdit ? 'Nouveau mot de passe (laisser vide pour ne pas changer)' : 'Mot de passe',
                'required' => !$isEdit,
                'mapped' => false,
            ]);

        // Champ "about" affiché uniquement pour le profil utilisateur (non-admin)
        if (!$isAdmin) {
            $builder->add('about', TextareaType::class, [
                'label' => 'À propos de moi',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Parlez un peu de vous, vos habitudes de trajet, vos préférences...',
                    'maxlength' => 500,
                ],
                'help' => 'Maximum 500 caractères',
            ]);
        }

        // Champs réservés à l'admin
        if ($isAdmin) {
            $builder
                ->add('roles', ChoiceType::class, [
                    'label' => 'Rôles',
                    'choices' => [
                        'Utilisateur' => User::ROLE_USER,
                        'Employé' => User::ROLE_EMPLOYE,
                        'Administrateur' => User::ROLE_ADMIN,
                    ],
                    'multiple' => true,
                    'expanded' => true,
                ])
                ->add('isActive', CheckboxType::class, [
                    'label' => 'Actif',
                    'required' => false,
                ]);
        }
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