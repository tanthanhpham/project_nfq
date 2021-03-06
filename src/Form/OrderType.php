<?php

namespace App\Form;

use App\Entity\Order;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('recipientName', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'The recipient name can not be null',
                    ]),
                    new Length([
                        'max' => 50,
                        'maxMessage' => 'The recipient name cannot be longer than 50 characters',
                    ])
                ]
            ])
            ->add('recipientEmail', EmailType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'The recipient email can not be null',
                    ]),
                    new Email([
                        'message' => 'The recipient email is not a valid email.',
                    ])
                ]
            ])
            ->add('recipientPhone', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'The recipient phone can not be null',
                    ]),
                    new Length([
                        'max' => 11,
                        'maxMessage' => 'The recipient phone cannot be longer than 11 characters',
                        'min' => 10,
                        'minMessage' => 'The recipient phone cannot be short than 10 characters',
                    ])
                ]
            ])
            ->add('paymentMethod', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'The recipient phone can not be null',
                    ]),
                    new Length([
                        'max' => 11,
                        'maxMessage' => 'The recipient phone cannot be longer than 11 characters',
                        'min' => 10,
                        'minMessage' => 'The recipient phone cannot be short than 10 characters',
                    ])
                ]
            ])
            ->add('addressDelivery', TextareaType::class)
            ->add('shippingCost', NumberType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'The shipping free can not be null',
                    ]),
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
