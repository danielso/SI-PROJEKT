<?php

/**
 * @license MIT
 */

namespace App\Form;

use App\Entity\Category;
use App\Entity\ToDo;
use App\Entity\User;
use App\Repository\CategoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Form type tworzenia/edycji zadań ToDo.
 *
 * Uwaga:
 *  - Tagi są CSV (mapped=false) i przetwarzane w serwisie.
 *  - Pole 'category' pokazuje tylko kategorie właściciela (opcja 'user').
 */
class ToDoForm extends AbstractType
{
    /**
     * Buduje formularz ToDo.
     *
     * @param FormBuilderInterface $builder form builder.
     * @param array<string,mixed>  $options opcje formularza (oczekuje klucza 'user' => ?User).
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User|null $user */
        $user = $options['user'] ?? null;

        $builder
            ->add('title', TextType::class, [
                'label' => 'label.title',
                'empty_data' => '',
                'trim' => true,
                'constraints' => [
                    new NotBlank(),
                    new Length(max: 255),
                ],
            ])
            ->add('isDone', CheckboxType::class, [
                'label' => 'label.is_done',
                'required' => false,
            ])
            ->add('content', TextareaType::class, [
                'label' => 'label.content',
                'required' => false,
                'empty_data' => '',
                'trim' => true,
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
                'trim' => true,
                'attr' => ['placeholder' => 'label.new_category_placeholder'],
            ])
            ->add('tags', TextType::class, [
                'label' => 'label.tags',
                'mapped' => false,
                'required' => false,
                'trim' => true,
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
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ToDo::class,
            'user' => null,
        ]);
        $resolver->setAllowedTypes('user', ['null', User::class]);
    }
}
