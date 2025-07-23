<?php

namespace App\Form;

use App\Entity\Note;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Category;
use Symfony\Component\Form\Extension\Core\Type\FileType;

/**
 * Form type for creating or editing a note.
 * Handles the fields for title, content, image, category, new category, and tags.
 */
class NoteType extends AbstractType
{
    /**
     * Builds the form for creating or editing a note.
     *
     * @param FormBuilderInterface $builder The form builder.
     * @param array                $options Additional options for the form.
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Tytuł',
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Treść',
                'attr' => ['rows' => 10],
            ])

            // Dodajemy pole do wyboru pliku
            ->add('image', FileType::class, [
                'label' => 'Dodaj obrazek',
                'required' => false,
                'mapped' => false,
                'attr' => ['accept' => 'image/*'],
            ])

            // Kategoria
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

            // Tagowanie
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
     * Configures the options for the form.
     *
     * @param OptionsResolver $resolver The options resolver.
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Note::class,
            'user' => null,
        ]);
    }
}
