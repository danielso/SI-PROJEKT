<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for user registration.
 */
class RegistrationFormType extends AbstractType
{
    /**
     * Builds the registration form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options for the form
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your email']),
                    new Length(['min' => 6, 'max' => 180]),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => ['label' => 'Password'],
                'second_options' => ['label' => 'Confirm Password'],
                'invalid_message' => 'Passwords don\'t match',
                'mapped' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your password']),
                    new Length(['min' => 6, 'minMessage' => 'Your password must be at least 6 characters long']),
                ],
                'required' => true,
            ]);
    }

    /**
     * Configures the options for the form.
     *
     * @param OptionsResolver $resolver The resolver for options
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
