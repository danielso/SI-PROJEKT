<?php

/**
 * @license MIT
 */

namespace App\Form;

use App\Entity\Category;
use App\Entity\Note;
use App\Entity\User;
use App\Repository\CategoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

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
     * @param FormBuilderInterface $builder form builder.
     * @param array<string, mixed> $options opcje (oczekuje klucza 'user' => ?User).
     *
     * @return void.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User|null $user */
        $user = $options['user'] ?? null;

        $builder
            ->add('title', TextType::class, [
                'label' => 'label.title',
                'empty_data' => '',
                'constraints' => [
                    new NotBlank(),
                    new Length(max: 255),
                ],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'label.content',
                'attr' => ['rows' => 10],
                'empty_data' => '',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('image', FileType::class, [
                'label' => 'label.image',
                'required' => false,
                'mapped' => false,
                'attr' => ['accept' => 'image/*'],
                'constraints' => [
                    new File(maxSize: '5M'),
                ],
            ])
            ->add('category', EntityType::class, [
                'label' => 'label.category',
                'class' => Category::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'label.choose_category',
                'query_builder' => function (CategoryRepository $repo) use ($user) {
                    if (!$user) {
                        return $repo->createQueryBuilder('c')->where('1 = 0');
                    }

                    return $repo->createQueryBuilder('c')
                        ->andWhere('c.user = :u')
                        ->setParameter('u', $user)
                        ->orderBy('c.name', 'ASC');
                },
            ])
            ->add('newCategory', TextType::class, [
                'label' => 'label.new_category',
                'required' => false,
                'mapped' => false,
                'attr' => ['placeholder' => 'label.new_category_placeholder'],
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
     * @param OptionsResolver $resolver resolver opcji.
     *
     * @return void.
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
