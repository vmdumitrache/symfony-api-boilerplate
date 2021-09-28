<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends Fixture
{
    private const USERS = [
        [
            'email' => 'admin@vmdumitrache.dev',
            'role' => 'ROLE_ADMIN',
            'password' => '00000000',
            'firstName' => 'Admin',
            'lastName' => 'Administratorson',
        ],
        [
            'email' => 'user@vmdumitrache.dev',
            'password' => '00000000',
            'role' => 'ROLE_USER',
            'firstName' => 'User',
            'lastName' => 'Userson',
        ],
        [
            'email' => 'frankberggren@vmdumitrache.dev',
            'role' => 'ROLE_USER',
            'password' => '00000000',
            'firstName' => 'Frank',
            'lastName' => 'Berggren',
        ],
        [
            'email' => 'sonyathoffman@vmdumitrache.dev',
            'role' => 'ROLE_USER',
            'password' => '00000000',
            'firstName' => 'Sonya T.',
            'lastName' => 'Hoffman',
        ],
        [
            'email' => 'antonioferreirasilva@vmdumitrache.dev',
            'role' => 'ROLE_USER',
            'password' => '00000000',
            'firstName' => 'Antônio Ferreira',
            'lastName' => 'Silva',
        ],
        [
            'email' => 'simonadvorakova@vmdumitrache.dev',
            'role' => 'ROLE_USER',
            'password' => '00000000',
            'firstName' => 'Simona',
            'lastName' => 'Dvořáková',
        ],
        [
            'email' => 'robertpayne@vmdumitrache.dev',
            'role' => 'ROLE_USER',
            'password' => '00000000',
            'firstName' => 'Robert',
            'lastName' => 'Payne',
        ],
        [
            'email' => 'comfortelhiver@vmdumitrache.dev',
            'role' => 'ROLE_USER',
            'password' => '00000000',
            'firstName' => 'Comforte ',
            'lastName' => 'L\'Hiver',
        ],
        [
            'email' => 'nusratesaissa@vmdumitrache.dev',
            'role' => 'ROLE_USER',
            'password' => '00000000',
            'firstName' => 'Nusrat Eisa',
            'lastName' => 'Issa',
        ],
        [
            'email' => 'majaaeriksen@vmdumitrache.dev',
            'role' => 'ROLE_USER',
            'password' => '00000000',
            'firstName' => 'Maja A.',
            'lastName' => 'Eriksen',
        ],
        [
            'email' => 'stefansharif@vmdumitrache.dev',
            'role' => 'ROLE_USER',
            'password' => '00000000',
            'firstName' => 'Stefan',
            'lastName' => 'Sharif',
        ],
        [
            'email' => 'johannestobiassen@vmdumitrache.dev',
            'role' => 'ROLE_USER',
            'password' => '00000000',
            'firstName' => 'Johannes',
            'lastName' => 'Tobiassen',
        ],
    ];
    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager)
    {
        foreach (self::USERS as $userData) {
            $user = new User();
            $user
                ->setEmail($userData['email'])
                ->setPassword(
                    $this->encoder->encodePassword(
                        $user,
                        $userData['password']
                    )
                )
                ->setFirstName($userData['firstName'])
                ->setLastName($userData['lastName'])
                ->setEmailVerified(false)
                ->setStatus(User::STATUS_PENDING)
            ;

            $manager->persist($user);
        }
        $manager->flush();
    }
}
