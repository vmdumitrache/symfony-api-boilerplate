<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use App\Util\Exception\ValidationHttpException;
use App\Util\Handler\ErrorMessageHandler;
use CoopTilleuls\ForgotPasswordBundle\Event\CreateTokenEvent;
use CoopTilleuls\ForgotPasswordBundle\Event\UpdatePasswordEvent;
use Doctrine\ORM\EntityManagerInterface;
use Rollerworks\Component\PasswordStrength\Validator\Constraints\PasswordRequirements;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Twig\Environment;

final class ForgotPasswordEventSubscriber implements EventSubscriberInterface
{
    private \Swift_Mailer $mailer;

    private Environment $twig;

    private ParameterBagInterface $parameterBag;

    private EntityManagerInterface $entityManager;

    private ValidatorInterface $validator;

    private UserPasswordEncoderInterface $passwordEncoder;

    /**
     * ResetPasswordEventSubscriber constructor.
     */
    public function __construct(
        \Swift_Mailer $mailer,
        Environment $twig,
        ParameterBagInterface $parameterBag,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        UserPasswordEncoderInterface $passwordEncoder
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->parameterBag = $parameterBag;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CreateTokenEvent::class => 'onCreateToken',
            UpdatePasswordEvent::class => 'onUpdatePassword',
        ];
    }

    public function onCreateToken(CreateTokenEvent $event)
    {
        $passwordToken = $event->getPasswordToken();
        $user = $passwordToken->getUser();

        $message = (new \Swift_Message('Password Reset Requested'))
            ->setTo($user->getEmail())
            ->setFrom([
                $this->parameterBag->get('email.address'),
                $this->parameterBag->get('email.sender'),
            ])
            ->setBody(
                $this->twig->render(
                    'forgot-password/emails/email.html.twig',
                    [
                        'reset_password_url' => sprintf(
                            'https://%s/reset-password/%s',
                            $this->parameterBag->get('front-end.domain'),
                            $passwordToken->getToken()
                        ),
                        'user' => $user,
                        'expiresAt' => $passwordToken->getExpiresAt(),
                    ]
                ),
                'text/html'
            )
            ->addPart(
                $this->twig->render(
                    'forgot-password/emails/email.txt.twig',
                    [
                        'reset_password_url' => sprintf(
                            'https://%s/reset-password/%s',
                            $this->parameterBag->get('front-end.domain'),
                            $passwordToken->getToken()
                        ),
                        'user' => $user,
                        'expiresAt' => $passwordToken->getExpiresAt(),
                    ]
                ),
                'text/plain'
            )
        ;

        if (0 === $this->mailer->send($message)) {
            throw new \RuntimeException('Unable to send email');
        }
    }

    public function onUpdatePassword(UpdatePasswordEvent $event)
    {
        $passwordToken = $event->getPasswordToken();
        $user = $passwordToken->getUser();
        $user->setPlainPassword($event->getPassword());

        $constraints = new Collection([
            'password' => [
                new PasswordRequirements([
                    'minLength' => 8,
                    'requireCaseDiff' => true,
                    'requireNumbers' => true,
                    'requireSpecialCharacter' => true,
                ]),
                new NotBlank(),
            ],
        ]);

        $violations = $this->validator->validate(
            ['password' => $user->getPlainPassword()],
            $constraints
        );

        if ($violations->count() > 0) {
            $errors = (new ErrorMessageHandler())->getValidationErrors($violations);
            throw new ValidationHttpException(json_encode(['errors' => $errors['password']]));
        }

        $user->setPassword(
            $this->passwordEncoder->encodePassword(
                $user,
                $user->getPlainPassword()
            )
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->sendPasswordUpdatedEmail($user);
    }

    public function sendPasswordUpdatedEmail(User $user)
    {
        $message = (new \Swift_Message('Password Updated Successfully'))
            ->setTo($user->getEmail())
            ->setFrom([
                $this->parameterBag->get('email.address'),
                $this->parameterBag->get('email.sender'),
            ])
            ->setBody(
                $this->twig->render(
                    'forgot-password/emails/success.html.twig',
                    [
                        'user' => $user,
                    ]
                ),
                'text/html'
            )
            ->addPart(
                $this->twig->render(
                    'forgot-password/emails/success.txt.twig',
                    [
                        'user' => $user,
                    ]
                ),
                'text/plain'
            )
        ;

        if (0 === $this->mailer->send($message)) {
            throw new \RuntimeException('Unable to send email');
        }
    }
}
