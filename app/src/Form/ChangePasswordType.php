<?php

/**
 * @license MIT
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Form type for changing a user's password.
 *
 * Provides a repeated password field with basic validation.
 */
class ChangePasswordType extends AbstractType
{
    /**
     * Builds the change-password form fields.
     *
     * @param FormBuilderInterface $builder form builder
     * @param array<string,mixed>  $options options array
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('newPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'mapped' => false,
            'first_options'  => [
                'label' => 'label.new_password',
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(min: 6, max: 4096),
                ],
            ],
            'second_options' => [
                'label' => 'label.confirm_password',
                'attr' => ['autocomplete' => 'new-password'],
            ],
            'invalid_message' => 'message.passwords_must_match',
        ]);
    }
}
