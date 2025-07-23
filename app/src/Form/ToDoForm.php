<?php

namespace App\Form;

use App\Entity\ToDo;
use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Form type for creating and editing ToDo tasks.
 */
class ToDoForm extends AbstractType
{
    /**
     * Builds the form for creating or editing a ToDo task.
     *
     * @param FormBuilderInterface $builder The form builder.
     * @param array                $options The options for the form.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Tytuł',
            ])
            ->add('isDone')

            ->add('content', TextareaType::class, [
                'required' => false,
                'label' => 'Treść' ,
            ])

            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Wybierz kategorię',
            ])

            // Nowa kategoria
            ->add('newCategory', TextType::class, [
                'label' => 'Nowa Kategoria',
                'required' => false,
                'attr' => ['placeholder' => 'Wpisz nazwę nowej kategorii'],
                'mapped' => false,
            ])

            ->add('tags', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Tagi (oddzielone przecinkami)',
            ])

            ->add('save', SubmitType::class, [
                'label' => 'Zapisz',
            ]);
    }

    /**
     * Configures the options for the ToDo form.
     *
     * @param OptionsResolver $resolver The resolver for form options.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ToDo::class,
            'user' => null,
        ]);
    }
}
