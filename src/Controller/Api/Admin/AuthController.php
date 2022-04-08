<?php

namespace App\Controller\Api\Admin;

use App\Entity\User;
use App\Form\UserType;
use App\Form\UserUpdateType;
use App\Repository\UserRepository;
use App\Service\FileUploader;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_ADMIN")
 */
class AuthController extends AbstractFOSRestController
{
    public const USER_PAGE_LIMIT = 10;
    public const USER_PAGE_PAGE = 1;
    public const PATH = 'http://127.0.0.1/uploads/images/';

    private $userRepository;
    private $logger;
    public function __construct(UserRepository $userRepository, LoggerInterface $logger)
    {
        $this->userRepository = $userRepository;
        $this->logger = $logger;
    }

    /**
     * @Rest\Get ("/admin/users")
     * @return Response
     */
    public function getAllUser(Request $request): Response
    {
        $limit = $request->get('limit', self::USER_PAGE_LIMIT);
        $page = $request->get('page', self::USER_PAGE_PAGE);

        $offset = $limit * ($page - 1);
        $users = $this->userRepository->findByConditions(['deletedAt' => null], ['createdAt' => 'DESC'], $limit, $offset);

        $serializer = SerializerBuilder::create()->build();
        $convertToJson = $serializer->serialize($users, 'json', SerializationContext::create()->setGroups(array('showUser')));
        $users = $serializer->deserialize($convertToJson, 'array', 'json');

        return $this->handleView($this->view($users, Response::HTTP_OK));
    }

    /**
     * @Rest\Post ("/admin/users")
     * @param Request $request
     * @param UserPasswordHasherInterface $encoder
     * @return Response
     */
    public function addAdmin(Request $request, UserPasswordHasherInterface $encoder, FileUploader $fileUploader): Response
    {
        try {
            $user = new User();
            $form = $this->createForm(UserType::class, $user);
            $requestData = $request->request->all();

            $form->submit($requestData);
            if ($form->isSubmitted() && $form->isValid()) {
                $user->setPassword($encoder->hashPassword($user, $requestData['password']));
                $user->setUpdatedAt(new \DateTime());
                $user->setRoles(['ROLE_ADMIN']);

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
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this->handleView($this->view([
            'error' => 'Something went wrong! Please contact support.'
        ], Response::HTTP_INTERNAL_SERVER_ERROR));
    }

    /**
     * @Rest\Delete("/admin/users/{id}")
     * @param integer $id
     * @return Response
     */
    public function deleteAdmin(int $id): Response
    {
        try {
            $user = $this->userRepository->find($id);
            if (!$user) {
                return $this->handleView($this->view(
                    ['error' => 'No user was found with this id.'],
                    Response::HTTP_NOT_FOUND
                ));
            }

            $user->setDeletedAt(new \DateTime());
            $this->userRepository->add($user);

            return $this->handleView($this->view([], Response::HTTP_NO_CONTENT));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this->handleView($this->view(
            ['error' => 'Something went wrong! Please contact support.'],
            Response::HTTP_INTERNAL_SERVER_ERROR
        ));
    }
}
