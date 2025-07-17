<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
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
     * @param FormBuilderInterface $builder The form builder.
     * @param array                $options The options for the form.
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', TextType::class, [
                'label' => 'Email',
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Hasło',
                'required' => false, // Hasło jest opcjonalne przy edytowaniu konta
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Rola',
                'choices' => [
                    'Użytkownik' => 'ROLE_USER',
                    'Administrator' => 'ROLE_ADMIN',
                ],
                'multiple' => true,
                'expanded' => true,  // Użycie checkboxów
            ])
            ->add('isBlocked', CheckboxType::class, [
                'label' => 'Zablokowany',
                'required' => false,
                'mapped' => false, // To pole nie będzie mapowane bezpośrednio do encji (kontroler będzie zarządzać tym polem)
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Zapisz zmiany',
            ]);
    }

    /**
     * Configures the options for the User form.
     *
     * @param OptionsResolver $resolver The resolver for form options.
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
