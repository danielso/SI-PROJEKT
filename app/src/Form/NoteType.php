<?php

/**
 * @license MIT
 */

namespace App\Form;

use App\Entity\Note;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

/**
 * Form type tworzenia/edycji notatki.
 *
 * Obrazek i tagi są mapped=false i obsługiwane w serwisie.
 * Lista kategorii filtrowana po właścicielu (opcja 'user').
 */
class NoteType extends AbstractType
{
    /**
     * Buduje formularz notatki.
     *
     * @param FormBuilderInterface $builder form builder
     * @param array<string, mixed> $options opcje (oczekuje klucza 'user' => ?User)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Opcja 'user' może zostać, choć nie jest już używana wewnątrz formularza
        $builder
            ->add('title', TextType::class, [
                'label' => 'label.title',
                'required' => true,
                'empty_data' => '',
            ])
            ->add('content', TextareaType::class, [
                'label' => 'label.content',
                'attr' => ['rows' => 10],
                'empty_data' => '',
            ])
            ->add('image', FileType::class, [
                'label' => 'label.image',
                'required' => false,
                'mapped' => false,
                'attr' => ['accept' => 'image/*'],
                'constraints' => [
                    new Image([
                        'maxSize' => '4M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'image/gif',
                        ],
                        'detectCorrupted' => true,
                    ]),
                ],
            ])
            ->add('categoryName', TextType::class, [
                'label' => 'label.category',
                'mapped' => false,
                'required' => false,
            ])
            ->add('tags', TextType::class, [
                'label' => 'label.tags',
                'mapped' => false,
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'action.save',
            ]);
    }

    /**
     * Konfiguracja opcji formularza.
     *
     * @param OptionsResolver $resolver resolver opcji
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Note::class,
            'user' => null, // \App\Entity\User
        ]);
        $resolver->setAllowedTypes('user', ['null', User::class]);
    }
}
