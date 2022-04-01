<?php

namespace App\Form;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class UserType extends AbstractType
{
    /** @var UserRepository */
    private $userRepository;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'name=>Full name can not be null'
                    ]),
                    new Length([
                        'max' => 50,
                        'maxMessage' => 'name=>Full name cannot be longer than 50 characters',
                    ])
                ]
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'email=>Email can not be null',
                    ]),
                    new Length([
                        'max' => 150,
                        'maxMessage' => 'email=>Email cannot be longer than 150 characters',
                    ]),
                    new Regex([
                        'pattern' => '/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix',
                        'message' => "email=>Email is incorrect"
                    ])
                ]
            ])
            ->add('password', PasswordType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'password=>Password can not be null',
                    ]),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'password=>Password cannot be shorter than 8 characters',
                        'max' => 20,
                        'maxMessage' => 'password=>Password cannot be longer than 20 characters',
                    ])
                ]
            ])
            ->add('phone', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'phone=>Phone number can not be null',
                    ]),
                    new Regex([
                        'pattern' => '/^[0-9]{10,20}$/',
                        'message' => "phone=>Phone number is incorrect"
                    ])
                ]
            ])
            ->add('address', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'address=>Address can not be null',
                    ])
                ]
            ])
            ->add('image', FileType::class)
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $data = $event->getData();
                if (isset($data['email']) && !empty($data['email'])) {
                    $user = $this->userRepository->findOneBy([
                        'email' => $data['email'],
                    ]);
                    if ($user) {
                        $form = $event->getForm();
                        $form->addError(new FormError('email=>Email is already existed'));
                    }
                }
            })
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => false,
        ]);
    }

    /**
     * @return UserRepository
     */
    public function getUserRepository(): UserRepository
    {
        return $this->userRepository;
    }

    /**
     * @param UserRepository $userRepository
     * @return void
     */
    public function setUserRepository(UserRepository $userRepository): void
    {
        $this->userRepository = $userRepository;
    }
}
