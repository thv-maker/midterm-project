<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\Stock;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => 'name', // 👈 show product name instead of ID
                'placeholder' => 'Select a product',
                'label' => 'Product',
            ])
            ->add('quantity', null, [
                'label' => 'Quantity (cups)',
            ])
            ->add('reorderLevel', null, [
                'label' => 'Reorder Level',
            ])
            ->add('lastUpdated', null, [
                'label' => 'Last Updated',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Stock::class,
        ]);
    }
}
