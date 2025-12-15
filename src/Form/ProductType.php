<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Product Name',
                'attr' => [
                    'placeholder' => 'Enter product name'
                ]
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Category',
                'choices' => [
                    'Coffee' => 'Coffee',
                    'Tea' => 'Tea',
                    'Frappe' => 'Frappe',
                    'Smoothie' => 'Smoothie',
                    
                ],
                'placeholder' => '-- Select Category --',
            ])
            ->add('price', NumberType::class, [
                'label' => 'Price (₱)',
                'attr' => [
                    'step' => '0.01',
                    'placeholder' => '0.00'
                ]
            ])
            ->add('calories', NumberType::class, [
                'label' => 'Calories',
                'attr' => [
                    'placeholder' => 'Enter calories'
                ]
            ])
            ->add('sugarGrams', NumberType::class, [
                'label' => 'Sugar (grams)',
                'attr' => [
                    'step' => '0.1',
                    'placeholder' => 'Enter sugar in grams'
                ]
            ])
            ->add('caffeineMg', NumberType::class, [
                'label' => 'Caffeine (mg)',
                'attr' => [
                    'placeholder' => 'Enter caffeine in mg'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}