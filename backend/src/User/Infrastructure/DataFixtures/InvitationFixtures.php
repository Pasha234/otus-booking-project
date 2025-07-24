<?php

namespace App\User\Infrastructure\DataFixtures;

use DateTimeImmutable;
use App\User\Domain\Entity\User;
use App\Booking\Domain\Entity\Group;
use App\User\Domain\Entity\Invitation;
use Doctrine\Persistence\ObjectManager;
use App\User\Domain\Enum\InvitationStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use App\Shared\Infrastructure\Tools\WithFaker;

class InvitationFixtures extends Fixture
{
    use WithFaker;

    public function load(ObjectManager $manager): void
    {
        $invitation = new Invitation();
        $invitation->setExpiresAt(new DateTimeImmutable('+7 days'));
        $invitation->setStatus(InvitationStatus::PENDING);
        $invitation->setInvitee($this->getReference('user', User::class));
        $invitation->setInvitedEmail($this->getReference('user', User::class)->getEmail());
        $invitation->setGroup($this->getReference('group', Group::class));

        $manager->persist($invitation);
        $manager->flush();

        $this->addReference('invitation', $invitation);
    }
}