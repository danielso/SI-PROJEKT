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

class ToDoForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Tytuł',  // Tytuł zadania
            ])
            ->add('isDone')
            ->add('content', TextareaType::class, [
                'required' => false,
                'label' => 'Treść',
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'query_builder' => function ($categoryRepository) use ($options) {
                    // Uzyskujemy repozytorium z opcji formularza
                    $user = $options['user'];  // Pobieramy użytkownika z opcji formularza
                    return $categoryRepository->createQueryBuilder('c')
                        ->where('c.user = :user')  // Filtrowanie kategorii dla użytkownika
                        ->setParameter('user', $user);
                },
                'choice_label' => 'name', // Ustalamy, co będzie widoczne na liście
                'required' => false,
            ])
            ->add('createdAt', null, [
                'widget' => 'single_text',
            ])
            ->add('updatedAt', null, [
                'widget' => 'single_text',
            ])
            ->add('tags', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Tagi (oddzielone przecinkami)',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ToDo::class,
            'user' => null, // Domyślnie ustawiamy user na null
        ]);
    }
}
