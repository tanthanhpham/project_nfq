<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Form\UserType;
use App\Form\UserUpdateType;
use App\Repository\UserRepository;
use App\Service\FileUploader;
use App\Service\GetUserInfo;
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
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractFOSRestController
{
    public const PATH = 'http://127.0.0.1/uploads/images/';
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
            $user->setUpdatedAt(new \DateTime());
            $user->setRoles(['ROLE_USER']);

            $uploadFile = $request->files->get('image');
            if ($uploadFile) {
                $saveFile = $fileUploader->upload($uploadFile);
                $saveFile = self::PATH . $saveFile;
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
     * @Rest\post ("/users/check_email")
     * @param Request $request
     * @return Response
     */
    public function getCheckEmailExist(Request $request): Response
    {
        $requestData = json_decode($request->getContent(), true);
        $user = $this->userRepository->findOneBy(['email' => $requestData]);
        if ($user) {

            return $this->handleView($this->view(true, Response::HTTP_OK));
        }

        return $this->handleView($this->view(false, Response::HTTP_OK));
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
     * @Rest\Post ("/users/email")
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

    /**
     * @Rest\Put ("/users/{id}")
     * @IsGranted("ROLE_USER")
     * @param Request $request
     * @return Response
     */
    public function updateUser(User $user, Request $request): Response
    {
        $form = $this->createForm(UserUpdateType::class, $user);
        $requestData = json_decode($request->getContent(), true);
        $form->submit($requestData);
        if ($form->isSubmitted() && $form->isValid()) {

            $user->setUpdatedAt(new \DateTime());
            $this->userRepository->add($user);

            return $this->handleView($this->view([], Response::HTTP_NO_CONTENT));
        }

        $errorsMessage = [];
        foreach ($form->getErrors(true, true) as $error) {
            $paramError = explode('=>', $error->getMessage());
            $errorsMessage[$paramError[0]] = $paramError[1];
        }

        return $this->handleView($this->view($errorsMessage, Response::HTTP_BAD_REQUEST));
    }

    /**
     * @Rest\Post("/users/{id}/image")
     * @IsGranted("ROLE_USER")
     * @param Request $request
     * @param FileUploader $fileUploader
     * @param ValidatorInterface $validator
     * @return Response
     */
    public function updateAvatar(Request $request, User $user, FileUploader $fileUploader, ValidatorInterface $validator): Response
    {
        $uploadFile = $request->files->get('image');

        if (!$uploadFile) {
            return $this->handleView($this->view(['error' => 'Please choose image to upload.'], Response::HTTP_BAD_REQUEST));
        }

        $errors = $validator->validate($uploadFile, new Image([
            'maxSize' => '5M',
            'mimeTypes' => [
                "image/jpeg",
                "image/jpg",
                "image/png",
            ],
            'maxSizeMessage' => 'File is too large.',
            'mimeTypesMessage' => 'Please upload a valid Image file.',
        ]));

        if (count($errors)) {
            return $this->handleView($this->view(['error' => $errors], Response::HTTP_BAD_REQUEST));
        }
        $saveFile = $fileUploader->upload($uploadFile);
        $saveFile = self::PATH . $saveFile;
        $user->setImage($saveFile);
        $this->userRepository->add($user);

        return $this->handleView($this->view([], Response::HTTP_NO_CONTENT));
    }
}
