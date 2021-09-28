<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Twig\Environment;

class UserService
{
    private EntityManagerInterface $entityManager;
    private VerifyEmailHelperInterface $emailHelper;
    private \Swift_Mailer $mailer;
    private Environment $environment;
    private ParameterBagInterface $parameterBag;

    public function __construct(
        EntityManagerInterface $entityManager,
        VerifyEmailHelperInterface $emailHelper,
        \Swift_Mailer $mailer,
        Environment $environment,
        ParameterBagInterface $parameterBag
    ) {
        $this->entityManager = $entityManager;
        $this->emailHelper = $emailHelper;
        $this->mailer = $mailer;
        $this->environment = $environment;
        $this->parameterBag = $parameterBag;
    }

    public function sendEmailVerification(User $user)
    {
        $this->entityManager->beginTransaction();
        try {
            $user->setEmailVerified(false);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
        }

        $signatureComponents = $this->emailHelper->generateSignature(
            'verify_email',
            $user->getId()->toString(),
            $user->getEmail(),
            ['id' => $user->getId()->toString()]
        );

        $verificationEmail = new \Swift_Message();
        $verificationEmail->setSubject('Email Address Verification');
        $verificationEmail->setTo($user->getEmail());
        $verificationEmail->setFrom([
            $this->parameterBag->get('email.address'),
            $this->parameterBag->get('email.sender'),
        ]);

        $verificationEmail->setBody(
            $this->environment->render(
                'registration/emails/confirmation.html.twig',
                [
                    'signedUrl' => $signatureComponents->getSignedUrl(),
                    'expiresAtMessageKey' => $signatureComponents->getExpirationMessageKey(),
                    'expiresAtMessageData' => $signatureComponents->getExpirationMessageData(),
                    'user' => $user,
                ]
            ),
            'text/html'
        );
        $this->mailer->send($verificationEmail);
    }
}
