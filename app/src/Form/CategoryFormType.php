<?php

/**
 * @license MIT
 */

namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * FormType dla kategorii (nazwa).
 */
class CategoryFormType extends AbstractType
{
    /**
     * Buduje formularz Category.
     *
     * @param FormBuilderInterface $builder form builder
     * @param array<string,mixed>  $options options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, [
            'label' => 'label.category_name',
            'required' => true,
            'trim' => true,
            'empty_data' => '',
            'constraints' => [
                new NotBlank(),
                new Length(max: 255),
            ],
        ]);
    }

    /**
     * Konfiguracja opcji formularza.
     *
     * @param OptionsResolver $resolver options resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}
