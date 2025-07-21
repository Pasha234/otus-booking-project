<?php

namespace App\User\Domain\Service;

use App\Booking\Domain\Entity\GroupParticipant;
use App\User\Domain\Entity\Invitation;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\InvitationStatus;
use DateTimeImmutable;

class GroupInvitationService
{
    public function acceptInvitation(Invitation $invitation, User $user)
    {
        $group = $invitation->getGroup();

        if(!$group->checkUserIsInGroup($user)) {
            $groupParticipant = new GroupParticipant();
            $groupParticipant->setGroup($group);
            $groupParticipant->setJoinedAt(new DateTimeImmutable());
            $groupParticipant->setUser($user);
            
            $group->addGroupParticipant($groupParticipant);
        }

        $invitation->setAcceptedAt(new DateTimeImmutable());
        $invitation->setStatus(InvitationStatus::ACCEPTED);
    }

    public function declineInvitation(Invitation $invitation)
    {
        $invitation->setDeclinedAt(new DateTimeImmutable());
        $invitation->setStatus(InvitationStatus::DECLINED);
    }
}