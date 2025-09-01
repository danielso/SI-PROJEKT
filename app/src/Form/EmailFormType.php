<?php

/**
 * @license MIT
 */

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * FormType for managing the email field in user-related forms.
 */
class EmailFormType extends AbstractType
{
    /**
     * Builds the form to collect email information from the user.
     *
     * @param FormBuilderInterface $builder the form builder
     * @param array                $options options for the form
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', EmailType::class, [
            'label' => 'label.email',
            'required' => true,
            'constraints' => [
                new NotBlank(),
                new Email(),
            ],
        ]);
    }
}
