<?php

namespace App\Policies;

use App\Models\Audit;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AuditPolicy
{
    /**
     * An audit is visible to whoever can see its site.
     */
    public function view(?User $user, Audit $audit): Response
    {
        return $audit->site->isPublic() || $user?->id === $audit->site->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
