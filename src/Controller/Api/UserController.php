<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\FileUploader;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class UserController extends AbstractFOSRestController
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @Rest\Post ("/register")
     * @param Request $request
     * @param UserPasswordHasherInterface $encoder
     * @return Response
     */
    public function register(Request $request, UserPasswordHasherInterface $encoder, FileUploader $fileUploader): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $requestData = $request->request->all();
        $form->submit($requestData);
        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($encoder->hashPassword($user, $requestData['password']));
            $user->setCreatedAt(new \DateTime());
            $user->setUpdatedAt(new \DateTime());
            $user->setRoles(['ROLE_USER']);

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

    /**
     * @Rest\Post ("/login")
     * @param UserInterface $user
     * @param JWTTokenManagerInterface $JWTManager
     * @return Response
     */
    public function getTokenUser(UserInterface $user, JWTTokenManagerInterface $JWTManager): Response
    {

        return $this->handleView($this->view(['token' => $JWTManager->create($user)], Response::HTTP_OK));
    }

    /**
     * @Rest\Get ("/users/{id}")
     * @param $id
     * @IsGranted("ROLE_USER")
     * @return Response
     */
    public function getOneUser($id): Response
    {
        $user = $this->userRepository->findOneBy(['id' => $id]);

        $serializer = SerializerBuilder::create()->build();
        $convertToJson = $serializer->serialize($user, 'json', SerializationContext::create()->setGroups(array('showUser')));
        $user = $serializer->deserialize($convertToJson, 'array', 'json');

        return $this->handleView($this->view($user, Response::HTTP_OK));
    }

    /**
     * @Rest\Get ("/users")
     * @IsGranted("ROLE_USER")
     * @return Response
     */
    public function getAllUser(): Response
    {
        $users = $this->userRepository->findBy(['deletedAt' => null]);

        $serializer = SerializerBuilder::create()->build();
        $convertToJson = $serializer->serialize($users, 'json', SerializationContext::create()->setGroups(array('showUser')));
        $users = $serializer->deserialize($convertToJson, 'array', 'json');

        return $this->handleView($this->view($users, Response::HTTP_OK));
    }

    /**
     * @Rest\Post ("/users/email")
     * @IsGranted("ROLE_USER")
     * @param Request $request
     * @return Response
     */
    public function getUserByEmail(Request $request): Response
    {
        $requestData = json_decode($request->getContent(), true);
        $email = $requestData['email'];
        $user = $this->userRepository->findOneBy(['email' => $email, 'deletedAt' => null]);

        $serializer = SerializerBuilder::create()->build();
        $convertToJson = $serializer->serialize($user, 'json', SerializationContext::create()->setGroups(array('showUser')));
        $user = $serializer->deserialize($convertToJson, 'array', 'json');

        return $this->handleView($this->view($user, Response::HTTP_OK));
    }
}
