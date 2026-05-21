<?php

namespace App\Message;

final class RappelEvenementMessage
{
    public function __construct(
        public readonly int $evenementId,
    ) {}
}
