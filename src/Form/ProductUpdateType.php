<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\ProductItem;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class ProductUpdateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Product name can not be null',
                    ]),
                    new NotNull([
                        'message' => 'Product name can not be null',
                    ]),
                    new Length([
                        'max' => 50,
                        'maxMessage' => 'Product name cannot be longer than 50 characters',
                    ])
                ]
            ])
            ->add('price', NumberType::class)
            ->add('images', FileType::class, [
                'multiple' => true,
            ])
            ->add('description', TextareaType::class)
            ->add('material', TextareaType::class)
            ->add('color', TextType::class)
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Category can not be null',
                    ]),
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
            'csrf_protection' => false,
        ]);
    }
}
