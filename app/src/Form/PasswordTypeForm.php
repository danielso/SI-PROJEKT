<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * FormType for handling password and confirmation fields in user-related forms.
 */
class PasswordTypeForm extends AbstractType
{
    /**
     * Builds the form to collect and confirm the user's password.
     *
     * @param FormBuilderInterface $builder The form builder.
     * @param array                $options Options for the form.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plainPassword', PasswordType::class, [
                'label' => 'label.new_password',
                'required' => true,
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank(['message' => 'form.password.not_blank']),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'form.password.min_length',
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('confirmPassword', PasswordType::class, [
                'label' => 'label.confirm_password',
                'required' => true,
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
            ]);
    }

    /**
     * Configures the options for the form.
     *
     * @param OptionsResolver $resolver The resolver to configure the options.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }

    /**
     * Returns the block prefix for the form.
     *
     * @return string The form block prefix.
     */
    public function getBlockPrefix(): string
    {
        return 'edit_password';
    }
}
