<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Maps every policy ability to a "{subject}.{action}" permission.
 * Super Admin bypasses policies through Gate::before (see AppServiceProvider).
 */
abstract class PermissionPolicy
{
    protected string $subject;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'view_any');
    }

    public function view(User $user, Model $model): bool
    {
        return $this->allows($user, 'view');
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'create');
    }

    /**
     * Duplicating a record creates a new one, so it takes the create permission.
     */
    public function replicate(User $user, Model $model): bool
    {
        return $this->allows($user, 'create');
    }

    public function update(User $user, Model $model): bool
    {
        return $this->allows($user, 'update');
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->allows($user, 'delete');
    }

    public function deleteAny(User $user): bool
    {
        return $this->allows($user, 'delete');
    }

    public function restore(User $user, Model $model): bool
    {
        return $this->allows($user, 'restore');
    }

    public function restoreAny(User $user): bool
    {
        return $this->allows($user, 'restore');
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return $this->allows($user, 'force_delete');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->allows($user, 'force_delete');
    }

    public function publish(User $user, ?Model $model = null): bool
    {
        return $this->allows($user, 'publish');
    }

    /**
     * Approve, request changes, and assign owners or reviewers.
     */
    public function review(User $user, ?Model $model = null): bool
    {
        return $this->allows($user, 'review');
    }

    public function preview(User $user, ?Model $model = null): bool
    {
        return $this->allows($user, 'preview');
    }

    public function reorder(User $user): bool
    {
        return $this->allows($user, 'reorder');
    }

    public function export(User $user): bool
    {
        return $this->allows($user, 'export');
    }

    protected function allows(User $user, string $action): bool
    {
        return $user->hasPermissionTo("{$this->subject}.{$action}");
    }
}
