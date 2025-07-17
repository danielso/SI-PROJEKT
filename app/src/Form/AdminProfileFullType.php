<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\User;

/**
 * FormType for managing the admin profile form, including email and password fields.
 */
class AdminProfileFullType extends AbstractType
{
    /**
     * Builds the form for editing the admin profile.
     *
     * @param FormBuilderInterface $builder The form builder.
     * @param array                $options Options for the form.
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adres email',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Email(),
                ],
            ])
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => false,
                'first_options'  => ['label' => 'Nowe hasło'],
                'second_options' => ['label' => 'Powtórz hasło'],
                'invalid_message' => 'Hasła muszą się zgadzać.',
            ]);
    }

    /**
     * Configures the options for the form.
     *
     * @param OptionsResolver $resolver The options resolver.
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
