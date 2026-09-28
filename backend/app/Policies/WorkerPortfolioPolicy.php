<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkerPortfolio;

class WorkerPortfolioPolicy
{
    public function update(User $user, WorkerPortfolio $portfolio): bool
    {
        return $user->workerProfile && $portfolio->worker_profile_id === $user->workerProfile->id;
    }

    public function delete(User $user, WorkerPortfolio $portfolio): bool
    {
        return $user->workerProfile && $portfolio->worker_profile_id === $user->workerProfile->id;
    }
}
