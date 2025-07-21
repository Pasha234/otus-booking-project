<?php

namespace App\User\Application\Command\CreateUser;

use App\User\Domain\Entity\User;
use App\Shared\Application\Command\CommandHandlerInterface;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreateUserCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator
    ) {
    }

    /**
     * @return string ID пользователя
     */
    public function __invoke(CreateUserCommand $createUserCommand): string
    {
        if ($this->userRepository->findOneBy(['email' => $createUserCommand->email])) {
            throw new UserAlreadyExistsException('There is already an account with this email.');
        }

        $user = new User();

        $user->setEmail($createUserCommand->email);
        $user->setFullName($createUserCommand->full_name);
        $user->setPassword($this->passwordHasher->hashPassword($user, $createUserCommand->password));

        $this->userRepository->save($user);

        return $user->getId();
    }
}