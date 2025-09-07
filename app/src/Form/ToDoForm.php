<?php

/**
 * @license MIT
 */

namespace App\Form;

use App\Entity\ToDo;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type tworzenia/edycji zadań ToDo.
 */
class ToDoForm extends AbstractType
{
    /**
     * Buduje formularz ToDo.
     *
     * @param FormBuilderInterface $builder form builder
     * @param array<string,mixed>  $options opcje formularza (oczekuje klucza 'user' => ?User)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User|null $user */
        $user = $options['user'] ?? null;

        $builder
            ->add('title', TextType::class, [
                'label' => 'label.title',
                'required' => true,
                'empty_data' => '',
                'trim' => true,
            ])
            ->add('isDone', CheckboxType::class, [
                'label' => 'label.is_done',
                'required' => false,
            ])
            ->add('content', TextareaType::class, [
                'label' => 'label.content',
                'required' => true,
                'empty_data' => '',
                'trim' => true,
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
                'trim' => true,
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
            'data_class' => ToDo::class,
            'user' => null,
        ]);
        $resolver->setAllowedTypes('user', ['null', User::class]);
    }
}
