<?php

namespace App\Message;

final class ConfirmationReservationMessage
{
    public function __construct(
        public readonly int $reservationId,
    ) {}
}
