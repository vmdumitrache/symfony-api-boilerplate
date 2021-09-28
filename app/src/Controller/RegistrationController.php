<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use App\Util\Handler\ErrorMessageHandler;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Ramsey\Uuid\Uuid;
use Rollerworks\Component\PasswordStrength\Validator\Constraints\PasswordRequirements;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

/**
 * Class RegistrationController.
 *
 * @author Vlad Dumitrache <vlad@vmdumitrache.dev>
 *
 * @OA\Response(
 *     response=500,
 *     description="Internal Server Error",
 *     @OA\Schema(
 *         type="object",
 *         @OA\Property(property="code", example="500"),
 *         @OA\Property(property="message", example="Internal Server Error")
 *     )
 * )
 * @OA\Tag(name="Registration")
 */
class RegistrationController extends AbstractFOSRestController
{
    private const VERIFICATION_INTERNAL_ERROR_MESSAGE = 'There was an error confirming your email address. Please try again at a later time or contact an administrator';
    private const VERIFICATION_SUCCESS = 'Your email address has been successfully verified. You may now log into the application';

    private UserRepository $userRepository;
    private UserPasswordEncoderInterface $passwordEncoder;
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;
    private VerifyEmailHelperInterface $emailHelper;
    private \Swift_Mailer $mailer;

    /**
     * RegistrationController constructor.
     */
    public function __construct(
        UserRepository $userRepository,
        UserPasswordEncoderInterface $passwordEncoder,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        VerifyEmailHelperInterface $emailHelper,
        \Swift_Mailer $mailer
    ) {
        $this->userRepository = $userRepository;
        $this->passwordEncoder = $passwordEncoder;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->emailHelper = $emailHelper;
        $this->mailer = $mailer;
    }

    /**
     * Register a new user.
     *
     * @Rest\Route(
     *     "/api/register",
     *     name="register",
     *     methods={"POST"}
     * )
     *
     * @OA\RequestBody(
     *     description="New user data",
     *     required=true,
     *     @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(
     *             type="object",
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 description="User's email address"
     *             ),
     *             @OA\Property(
     *                 property="password",
     *                 type="string",
     *                 description="User's password"
     *             ),
     *             @OA\Property(
     *                 property="firstName",
     *                 type="string",
     *                 description="User's first name"
     *             ),
     *             @OA\Property(
     *                 property="lastName",
     *                 type="string",
     *                 description="User's last name"
     *             )
     *         )
     *     )
     * )
     *
     * @OA\Response(
     *     response=201,
     *     description="User successfully created",
     *     @Model(type=User::class, groups={"non_sensitive_data"})
     * )
     *
     * @OA\Response(
     *     response=400,
     *     description="Bad Request",
     *     @OA\Schema(
     *         type="object",
     *         @OA\Property(property="message", example="Empty request received.")
     *     )
     * )
     * @OA\Response(
     *     response=409,
     *     description="Conflict",
     *     @OA\Schema(
     *         type="object",
     *         @OA\Property(property="message", example="User already exists.")
     *     )
     * )
     */
    public function register(Request $request, UserService $userService): View
    {
        if (empty($request->getContent())) {
            return $this->view([
                'message' => 'Empty request received.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $userData = json_decode($request->getContent(), true);

        $user = $this->userRepository->findOneBy([
            'email' => $userData['email'],
        ]);

        if (!empty($user)) {
            return $this->view([
                'message' => 'User already exists.',
            ], Response::HTTP_CONFLICT);
        }

        $constraints = new Collection([
            'email' => [
                new Email(),
                new NotBlank(),
            ],
            'firstName' => [
                new NotBlank(),
            ],
            'lastName' => [
                new NotBlank(),
            ],
            'password' => [
                new PasswordRequirements([
                    'minLength' => 8,
                    'requireCaseDiff' => true,
                    'requireNumbers' => true,
                    'requireSpecialCharacter' => true,
                ]),
            ],
        ]);

        $violations = $this->validator->validate($userData, $constraints);

        if ($violations->count() > 0) {
            $errors = (new ErrorMessageHandler())->getValidationErrors($violations);

            return $this->view($errors, Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->beginTransaction();
        try {
            $user = new User();
            $user
                ->setEmail($userData['email'])
                ->setFirstName($userData['firstName'])
                ->setLastName($userData['lastName'])
                ->setPlainPassword($userData['password'])
                ->setPassword(
                    $this
                        ->passwordEncoder
                        ->encodePassword($user, $user->getPlainPassword())
                )
            ;

            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $this->entityManager->commit();

            $userService->sendEmailVerification($user);

            return $this
                ->view($user, Response::HTTP_CREATED)
                ->setContext((new Context())->setGroups(['non_sensitive_data']))
            ;
        } catch (\Exception $e) {
            dump($e->getMessage());

            return $this->view([
                'message' => 'There was an error creating your account.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Rest\Route(
     *     "/verifications/email",
     *     name="verify_email",
     *     methods={"GET"}
     * )
     *
     * @OA\Response(
     *     response="200",
     *     description="Email address successfully verified.",
     *     @OA\MediaType(
     *         mediaType="text/html"
     *     )
     * )
     *
     * @OA\Response(
     *     response="400",
     *     description="Invalid user ID.",
     *     @OA\MediaType(
     *         mediaType="text/html"
     *     )
     * )
     *
     * @OA\Response(
     *     response="404",
     *     description="User not found.",
     *     @OA\MediaType(
     *         mediaType="text/html"
     *     )
     * )
     *
     * @OA\Response(
     *     response="409",
     *     description="Email address already verified",
     *     @OA\MediaType(
     *         mediaType="text/html"
     *     )
     * )
     */
    public function verifyEmailAddress(Request $request): Response
    {
        $id = $request->get('id');

        if (null === $id || !Uuid::isValid($id)) {
            return $this
                ->render('registration/verify-email-error.html.twig', [
                    'title' => 'Invalid User ID',
                    'message' => 'User ID not passed',
                ])
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
            ;
        }

        $user = $this->userRepository->findOneBy(['id' => $id]);

        if (null === $user) {
            return $this
                ->render('registration/verify-email-error.html.twig', [
                    'title' => 'User Not Found',
                    'message' => 'User not found',
                ])
                ->setStatusCode(Response::HTTP_NOT_FOUND)
            ;
        }

        if ($user->isEmailVerified()) {
            return $this
                ->render('registration/verify-email-error.html.twig', [
                    'title' => 'Email Address Already Verified',
                    'message' => 'This email address has already been verified',
                ])
                ->setStatusCode(Response::HTTP_CONFLICT)
            ;
        }

        try {
            $this->emailHelper->validateEmailConfirmation(
                $request->getUri(),
                $user->getId()->toString(),
                $user->getEmail()
            );
        } catch (VerifyEmailExceptionInterface $exception) {
            return $this
                ->render('registration/verify-email-error.html.twig', [
                    'title' => 'Something went wrong...',
                    'message' => self::VERIFICATION_INTERNAL_ERROR_MESSAGE,
                ])
                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
            ;
        }

        $this->entityManager->beginTransaction();
        try {
            $user
                ->setStatus(User::STATUS_VERIFIED)
                ->setEmailVerified(true)
            ;
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();

            return $this
                ->render('registration/verify-email-error.html.twig', [
                    'title' => 'Something went wrong...',
                    'message' => self::VERIFICATION_INTERNAL_ERROR_MESSAGE,
                ])
                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
            ;
        }

        return $this
            ->render('registration/verify-email-success.html.twig', [
                'message' => self::VERIFICATION_SUCCESS,
            ])
            ->setStatusCode(Response::HTTP_OK)
        ;
    }

    /**
     * @Rest\Route(
     *     "/verifications/email",
     *     name="verify_email_resend",
     *     methods={"POST"}
     * )
     *
     * @Rest\RequestParam(
     *     name="email",
     *     nullable=false
     * )
     *
     * @OA\Response(
     *     response="201",
     *     description="Email address verification sent.",
     *     @OA\MediaType(
     *         mediaType="text/html"
     *     )
     * )
     */
    public function resendVerificationEmail(ParamFetcher $paramFetcher, UserService $userService): Response
    {
        $defaultResponse = $this->render('registration/verify-email-error.html.twig', [
            'title' => 'Email Sent',
            'message' => 'Verification email successfully sent',
        ])
            ->setStatusCode(Response::HTTP_CREATED)
        ;

        $email = $paramFetcher->get('email');
        $constraints = new Collection([
            'email' => [
                new Email(),
                new NotBlank(),
            ],
        ]);

        $violations = $this->validator->validate([
            'email' => $email,
        ], $constraints);

        if ($violations->count() > 0) {
            return $defaultResponse;
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (empty($user)) {
            return $defaultResponse;
        }

        if ($user->isEmailVerified()) {
            return $defaultResponse;
        }

        $userService->sendEmailVerification($user);

        return $defaultResponse;
    }
}
