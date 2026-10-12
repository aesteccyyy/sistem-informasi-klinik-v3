<?php

namespace App\Enums;

enum QueueStatusEnum: string
{
    case WAITING = 'waiting';
    case CALLED = 'called';
    case SERVED = 'served';
    case SKIPPED = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::WAITING => 'Menunggu',
            self::CALLED => 'Dipanggil',
            self::SERVED => 'Selesai',
            self::SKIPPED => 'Dilewati',
        };
    }
}
