<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security as OASecurity;
use OpenApi\Annotations as OA;
use OpenApi\Annotations\Items;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class UserController.
 *
 * @Route("/api", name="api_")
 *
 * @OA\Response(
 *     response=500,
 *     description="Internal server error",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="code", example="500"),
 *         @OA\Property(property="message", example="Internal Server Error")
 *     )
 * )
 *
 * @OA\Response(
 *     response=401,
 *     description="Unauthorised",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="code", example="401"),
 *         @OA\Property(property="message", example="Access denied")
 *     )
 * )
 */
class UserController extends AbstractFOSRestController
{
    /**
     * @var UserRepository
     */
    private $userRepository;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * UserController constructor.
     */
    public function __construct(
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ) {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    /**
     * @Route(
     *     "/users",
     *     name="users",
     *     methods={"GET"}
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Returns all users",
     *     @OA\JsonContent(
     *         type="array",
     *         @Items(ref=@Model(type=User::class, groups={"non_sensitive_data"}))
     *     )
     * )
     *
     * @OA\Response(
     *     response=404,
     *     description="Not Found",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="message", example="No users found.")
     *     )
     * )
     *
     * @OA\Tag(name="Users")
     * @OASecurity(name="Bearer")
     */
    public function showUsers(): View
    {
        $users = $this->userRepository->findAll();
        if (empty($users)) {
            return $this->view([
                'message' => 'No users found',
                Response::HTTP_NOT_FOUND,
            ]);
        }

        return $this
            ->view($users, Response::HTTP_OK)
            ->setContext((new Context())->setGroups(['public']))
        ;
    }

    /**
     * @param User $subjectUser
     *
     * @Route(
     *     "/users/{userId}",
     *     name="get_user",
     *     methods={"GET"}
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Returns user",
     *     @Model(type=User::class, groups={"non_sensitive_data"})
     * )
     *
     * @OA\Response(
     *     response=404,
     *     description="Not Found",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="message", example="User not found")
     *     )
     * )
     *
     * @OA\Tag(name="Users")
     * @OASecurity(name="Bearer")
     */
    public function showUser(User $userId): View
    {
        return $this->view($userId, Response::HTTP_OK);
    }

    /**
     * @Route(
     *     "/users/{userId}/email",
     *     name="patch_user_email",
     *     methods={"PATCH"}
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Returns user",
     *     @Model(type=User::class, groups={"non_sensitive_data"})
     * )
     *
     * @OA\Response(
     *     response=404,
     *     description="Not Found",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="message", example="User not found")
     *     )
     * )
     *
     * @OA\Response(
     *     response=400,
     *     description="Bad Request",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="message", example="Invalid email address")
     *     )
     * )
     *
     * @OA\Response(
     *     response=409,
     *     description="Conflict",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="message", example="Email address already registered.")
     *     )
     * )
     *
     * @Rest\RequestParam(name="email", nullable=false)
     *
     * @OA\Tag(name="Users")
     * @OASecurity(name="Bearer")
     */
    public function patchUserEmail(ParamFetcherInterface $paramFetcher, User $userId): View
    {
        $email = $paramFetcher->get('email');
        $emailConstraint = new Email();
        $errors = $this->validator->validate($email, $emailConstraint);

        if (count($errors) > 0) {
            return $this->view([
                'message' => 'Invalid email address',
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($email === $userId->getEmail()) {
            return $this
                ->view($userId, Response::HTTP_OK)
                ->setContext((new Context())->setGroups(['public']))
            ;
        }

        if ($this->userRepository->findOneBy(['email' => $email])) {
            return $this->view([
                'message' => 'Email address already registered.',
            ], Response::HTTP_CONFLICT);
        }

        $userId->setEmail($email);
        $this->entityManager->persist($userId);
        $this->entityManager->flush();

        return $this
            ->view($userId, Response::HTTP_OK)
            ->setContext((new Context())->setGroups(['public']))
        ;
    }
}
