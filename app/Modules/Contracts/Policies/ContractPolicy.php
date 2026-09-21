<?php

namespace App\Modules\Contracts\Policies;

use App\Models\Contract;
use App\Modules\Employees\Models\Employee;
use App\Modules\Users\Models\User;

class ContractPolicy
{
    public function viewAny(mixed $user): bool
    {
        if ($user instanceof User) {
            return true;
        }

        return $user instanceof Employee && $user->hasPermission('all_requests.view');
    }

    public function view(mixed $user, Contract $contract): bool
    {
        if ($user instanceof User) {
            return $this->owns($user, $contract);
        }

        return $user instanceof Employee && $user->hasPermission('all_requests.view');
    }

    public function create(mixed $user): bool
    {
        if ($user instanceof User) {
            return true;
        }

        return $user instanceof Employee && $user->hasPermission('all_requests.edit');
    }

    public function update(mixed $user, Contract $contract): bool
    {
        if ($user instanceof User) {
            return $this->owns($user, $contract);
        }

        return $user instanceof Employee && $user->hasPermission('all_requests.edit');
    }

    public function delete(mixed $user, Contract $contract): bool
    {
        if ($user instanceof User) {
            return $this->owns($user, $contract);
        }

        return $user instanceof Employee && $user->hasPermission('all_requests.edit');
    }

    private function owns(User $user, Contract $contract): bool
    {
        return (int) $user->id === (int) $contract->user_id;
    }
}
