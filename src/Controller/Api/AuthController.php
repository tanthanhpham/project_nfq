<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\FileUploader;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use FOS\RestBundle\Controller\Annotations as Rest;

class AuthController extends AbstractFOSRestController
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @Rest\Post ("/admins")
     * @param Request $request
     * @param UserPasswordHasherInterface $encoder
     * @return Response
     */
    public function addAdmin(Request $request, UserPasswordHasherInterface $encoder, FileUploader $fileUploader): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $requestData = $request->request->all();

        $form->submit($requestData);
        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($encoder->hashPassword($user, $requestData['password']));
            $user->setCreatedAt(new \DateTime());
            $user->setUpdatedAt(new \DateTime());
            $user->setRoles(['ROLE_ADMIN']);

            $uploadFile = $request->files->get('image');
            if ($uploadFile) {
                $saveFile = $fileUploader->upload($uploadFile);
                $user->setImage($saveFile);
            }
            $this->userRepository->add($user);

            return $this->handleView($this->view(["message" => "Register successfully"], Response::HTTP_CREATED));
        }

        $errorsMessage = [];
        foreach ($form->getErrors(true, true) as $error) {
            $paramError = explode('=>', $error->getMessage());
            $errorsMessage[$paramError[0]] = $paramError[1];
        }

        return $this->handleView($this->view($errorsMessage, Response::HTTP_BAD_REQUEST));
    }
}
