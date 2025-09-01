<?php

/**
 * @license MIT
 */

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for managing user details.
 */
class UserFormType extends AbstractType
{
    /**
     * Builds the form for editing a user.
     *
     * @param FormBuilderInterface $builder the form builder
     * @param array                $options the options for the form
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', TextType::class, [
                'label' => 'label.email',
            ])

            ->add('roles', ChoiceType::class, [
                'label' => 'label.role',
                'choices' => [
                    'label.role_user' => 'ROLE_USER',
                    'label.role_admin' => 'ROLE_ADMIN',
                ],
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('isBlocked', CheckboxType::class, [
                'label' => 'label.is_blocked',
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'action.save',
            ]);
    }

    /**
     * Configures the options for the User form.
     *
     * @param OptionsResolver $resolver the resolver for form options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
