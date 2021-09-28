<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Rfc4122\UuidV1;

/**
 * @internal
 * @covers \App\Entity\User
 */
class UserTest extends TestCase
{
    public function testSetId()
    {
        $user = new User();
        $id = UuidV1::uuid1();
        $user->setId($id);

        $this->assertEquals($id, $user->getId());
    }

    public function testSetEmail()
    {
        $user = new User();
        $email = 'user@example.com';
        $user->setEmail($email);

        $this->assertEquals($email, $user->getEmail());
    }

    public function testSetFirstName()
    {
        $user = new User();
        $firstName = 'John';
        $user->setFirstName($firstName);

        $this->assertEquals($firstName, $user->getFirstName());
    }

    public function testSetLastName()
    {
        $user = new User();
        $lastName = 'Smith';
        $user->setLastName($lastName);

        $this->assertEquals($lastName, $user->getLastName());
    }

    public function testSetRoles()
    {
        $user = new User();
        $roles = 'ROLE_TEST_USER';
        $user->setRoles([$roles]);

        $this->assertContains($roles, $user->getRoles());
        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testSetPassword()
    {
        $user = new User();
        $password = 'not-so-very-secure-password';
        $user->setPassword($password);

        $this->assertEquals($password, $user->getPassword());
    }

    public function testSetPlainPassword()
    {
        $user = new User();
        $plainPassword = 'not-so-very-secure-password';
        $user->setPlainPassword($plainPassword);

        $this->assertEquals($plainPassword, $user->getPlainPassword());
    }

    public function testSetStatus()
    {
        $user = new User();
        $this->assertEquals(User::STATUS_PENDING, $user->getStatus());
        $user->setStatus(User::STATUS_BLOCKED);

        $this->assertEquals(User::STATUS_BLOCKED, $user->getStatus());
    }

    public function testSetEmailVerified()
    {
        $user = new User();
        $this->assertFalse($user->isEmailVerified());

        $user->setEmailVerified(true);
        $this->assertTrue($user->isEmailVerified());
    }
}
