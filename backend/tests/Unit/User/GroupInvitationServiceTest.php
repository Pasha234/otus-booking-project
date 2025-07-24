<?php

namespace App\Tests\Unit\User;

use App\Booking\Domain\Entity\Group;
use App\Booking\Domain\Entity\GroupParticipant;
use App\User\Domain\Entity\Invitation;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\InvitationStatus;
use App\User\Domain\Service\GroupInvitationService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class GroupInvitationServiceTest extends TestCase
{
    private GroupInvitationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GroupInvitationService();
    }

    public function test_accept_invitation_should_add_user_to_group_if_not_present(): void
    {
        // Arrange
        $user = $this->createMock(User::class);
        $group = $this->createMock(Group::class);
        $invitation = $this->createMock(Invitation::class);

        $invitation->expects($this->once())
            ->method('getGroup')
            ->willReturn($group);

        $group->expects($this->once())
            ->method('checkUserIsInGroup')
            ->with($user)
            ->willReturn(false);

        // Expect that a new participant is created and added to the group
        $group->expects($this->once())
            ->method('addGroupParticipant')
            ->with($this->callback(function (GroupParticipant $participant) use ($group, $user) {
                $this->assertSame($group, $participant->getGroup());
                $this->assertSame($user, $participant->getUser());
                $this->assertInstanceOf(DateTimeImmutable::class, $participant->getJoinedAt());
                return true;
            }));

        // Expect the invitation status to be updated
        $invitation->expects($this->once())
            ->method('setAcceptedAt')
            ->with($this->isInstanceOf(DateTimeImmutable::class));

        $invitation->expects($this->once())
            ->method('setStatus')
            ->with(InvitationStatus::ACCEPTED);

        // Act
        $this->service->acceptInvitation($invitation, $user);
    }

    public function test_accept_invitation_should_not_add_user_to_group_if_already_present(): void
    {
        // Arrange
        $user = $this->createMock(User::class);
        $group = $this->createMock(Group::class);
        $invitation = $this->createMock(Invitation::class);

        $invitation->expects($this->once())->method('getGroup')->willReturn($group);
        $group->expects($this->once())->method('checkUserIsInGroup')->with($user)->willReturn(true);

        // Assert that user is NOT added again
        $group->expects($this->never())->method('addGroupParticipant');

        // Assert the invitation is still updated
        $invitation->expects($this->once())->method('setAcceptedAt')->with($this->isInstanceOf(DateTimeImmutable::class));
        $invitation->expects($this->once())->method('setStatus')->with(InvitationStatus::ACCEPTED);

        // Act
        $this->service->acceptInvitation($invitation, $user);
    }

    public function test_decline_invitation_should_update_invitation_status(): void
    {
        // Arrange
        $invitation = $this->createMock(Invitation::class);

        $invitation->expects($this->once())->method('setDeclinedAt')->with($this->isInstanceOf(DateTimeImmutable::class));
        $invitation->expects($this->once())->method('setStatus')->with(InvitationStatus::DECLINED);

        // Act
        $this->service->declineInvitation($invitation);
    }
}