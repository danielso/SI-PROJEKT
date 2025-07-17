<?php

namespace App\Form;

use App\Entity\Tag;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for creating and editing tags.
 */
class TagFormType extends AbstractType
{
    /**
     * Builds the form for creating or editing a tag.
     *
     * @param FormBuilderInterface $builder The form builder.
     * @param array                $options The options for the form.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, [
            'label' => 'Nazwa tagu',
        ]);
    }

    /**
     * Configures the options for the form.
     *
     * @param OptionsResolver $resolver The resolver for form options.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tag::class,
        ]);
    }
}
