<?php
/**
 * @license MIT
 */

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * FormType for managing the admin profile form, including email and password fields.
 */
class AdminProfileFullType extends AbstractType
{
    private EntityManagerInterface $em;

    /**
     * AdminProfileFullType constructor.
     *
     * @param EntityManagerInterface $em Entity manager, available for potential custom validators or lookups.
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Builds the form for editing the admin profile.
     *
     * @param FormBuilderInterface $builder The form builder.
     * @param array                $options Options for the form.
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var User $user */
        $user = $options['data'];

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
