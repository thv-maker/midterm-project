<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\Stock;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => 'name',
                'placeholder' => 'Select a product',
                'label' => 'Product',
                'required' => true,
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantity',
                'attr' => [
                    'min' => 0,
                    'placeholder' => '0'
                ],
                'required' => true,
            ])
            ->add('reorderLevel', IntegerType::class, [
                'label' => 'Reorder Level',
                'attr' => [
                    'min' => 0,
                    'placeholder' => '0'
                ],
                'required' => true,
            ])
            ->add('lastUpdated', DateTimeType::class, [
                'label' => 'Last Updated',
                'widget' => 'single_text',
                'required' => true,
                'data' => new \DateTime(), // Automatically set to current date/time
                'attr' => [
                    'readonly' => true // Make it readonly
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Stock::class,
        ]);
    }
}