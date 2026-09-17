<?php

namespace App\Policies;

use App\Models\Couple;
use App\Models\User;

class CouplePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdministrator() ? true : null;
    }

    public function update(User $user, Couple $couple): bool
    {
        return ($couple->husband && $this->personPolicy()->inScope($user, $couple->husband))
            || ($couple->wife && $this->personPolicy()->inScope($user, $couple->wife));
    }

    public function delete(User $user, Couple $couple): bool
    {
        return $this->update($user, $couple);
    }

    public function addChild(User $user, Couple $couple): bool
    {
        return $this->update($user, $couple);
    }

    private function personPolicy(): PersonPolicy
    {
        return app(PersonPolicy::class);
    }
}
