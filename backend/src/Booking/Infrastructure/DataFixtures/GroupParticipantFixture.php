<?php

namespace App\Booking\Infrastructure\DataFixtures;

use App\Booking\Domain\Entity\Group;
use App\Booking\Domain\Entity\GroupParticipant;
use App\Shared\Infrastructure\Tools\WithFaker;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\DataFixtures\UserFixtures;
use DateTimeImmutable;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class GroupParticipantFixture extends Fixture implements DependentFixtureInterface
{
    use WithFaker;

    public function load(ObjectManager $manager): void
    {
        $groupParticipant = new GroupParticipant();
        // $groupParticipant->setJoinedAt(new DateTimeImmutable());
        // $groupParticipant->setUser($this->getReference('user', User::class));
        // $groupParticipant->setGroup($this->getReference('group', Group::class));
        
        // $manager->persist($groupParticipant);
        // $manager->flush();

        $this->addReference('group-participant', $groupParticipant);
    }

    public function getDependencies(): array
    {
        return [
            GroupFixture::class,
        ];
    }
}