<?php

namespace App\Policies;

use App\Models\Site;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SitePolicy
{
    /**
     * Anonymous audits are public; a monitored site is visible only to its owner.
     * Others get a 404 so private sites cannot be discovered.
     */
    public function view(?User $user, Site $site): Response
    {
        return $site->isPublic() || $user?->id === $site->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Site $site): bool
    {
        return $user->id === $site->user_id;
    }

    public function delete(User $user, Site $site): bool
    {
        return $user->id === $site->user_id;
    }
}
