<?php

namespace App\Tests\Integration\User\Command;

use Faker\Factory;
use Faker\Generator;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use App\Booking\Infrastructure\DataFixtures\GroupFixture;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use App\Shared\Application\Command\CommandBusInterface;
use App\User\Infrastructure\DataFixtures\InvitationFixtures;
use App\User\Infrastructure\DataFixtures\UserFixtures;
use App\User\Application\Command\SetStatusForInvitation\SetStatusForInvitationCommand;
use App\User\Domain\Entity\Invitation;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\InvitationStatus;
use App\User\Domain\Repository\InvitationRepositoryInterface;
use App\User\Infrastructure\Repository\InvitationRepository;

class SetStatusForInvitationTest extends WebTestCase
{
    private Generator $faker;
    private CommandBusInterface $commandBus;
    private InvitationRepositoryInterface $invitationRepository;
    private ORMExecutor $executor;

    public function setUp(): void
    {
        parent::setUp();
        $container = self::getContainer();

        $this->faker = Factory::create();
        $this->commandBus = $container->get(CommandBusInterface::class);
        $this->invitationRepository = $container->get(InvitationRepositoryInterface::class);

        $entityManager = $container->get(EntityManagerInterface::class);
        $loader = new Loader();
        $loader->addFixture($container->get(UserFixtures::class));
        $loader->addFixture($container->get(GroupFixture::class));
        $loader->addFixture($container->get(InvitationFixtures::class));
        $this->executor = new ORMExecutor($entityManager, new ORMPurger());
        $this->executor->execute($loader->getFixtures());
    }

    public function test_accept_invitation(): void
    {
        $invitation = $this->executor->getReferenceRepository()->getReference('invitation', Invitation::class);
        $user = $this->executor->getReferenceRepository()->getReference('user', User::class);
        $command = new SetStatusForInvitationCommand(
            $invitation->getId(),
            $user->getId(),
            true,
        );

        $this->commandBus->execute($command);

        $invitation = $this->invitationRepository->getById($invitation->getId());
        $this->assertEquals(InvitationStatus::ACCEPTED, $invitation->getStatus());
        // $this->assertNotEmpty($user);
    }
}
