<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\ProductItem;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class ProductType extends AbstractType
{
    /** @var ProductRepository */
    private $productRepository;

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
            ->add('images', FileType::class, [
                'multiple' => true,
                'allow_extra_fields' => true,
            ])
            ->add('price', NumberType::class)
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
            ->add('productItems', CollectionType::class, [
                'entry_type' => ProductItem::class,
                'allow_extra_fields' => true,
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                $data = $event->getData();
                $product = $this->productRepository->findOneBy([
                    'name' => $data->getName(),
                    'color' => $data->getColor()
                ]);
                if ($product) {
                    $form->get('name')->addError(new FormError('Product is already existed'));
                }
            })
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'allow_extra_fields' => true,
            'data_class' => Product::class,
            'csrf_protection' => false,
        ]);
    }


    /**
     * @return ProductRepository
     */
    public function getProductRepository(): ProductRepository
    {
        return $this->productRepository;
    }

    /**
     * @param ProductRepository $productRepository
     * @return void
     */
    public function setProductRepository(ProductRepository $productRepository): void
    {
        $this->productRepository = $productRepository;
    }
}
