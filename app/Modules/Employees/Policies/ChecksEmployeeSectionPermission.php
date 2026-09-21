<?php

namespace App\Modules\Employees\Policies;

use App\Models\Employee;

trait ChecksEmployeeSectionPermission
{
    abstract protected function permissionSection(): string;

    protected function allows(mixed $user, string $action): bool
    {
        return $user instanceof Employee
            && $user->hasPermission($this->permissionSection().'.'.$action);
    }

    public function viewAny(mixed $user): bool
    {
        return $this->allows($user, 'view');
    }

    public function view(mixed $user, mixed $model): bool
    {
        return $this->allows($user, 'view');
    }

    public function create(mixed $user): bool
    {
        return $this->allows($user, 'create');
    }

    public function update(mixed $user, mixed $model): bool
    {
        return $this->allows($user, 'edit');
    }

    public function delete(mixed $user, mixed $model): bool
    {
        return $this->allows($user, 'delete');
    }
}
