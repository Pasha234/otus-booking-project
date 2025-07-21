<?php
namespace App\User\Domain\Enum;

enum InvitationStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case DECLINED = 'declined';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::ACCEPTED => 'Accepted',
            self::DECLINED => 'Declined',
            self::EXPIRED => 'Expired',
        };
    }
}