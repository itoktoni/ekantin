<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class BasePolicy
{
    protected $module;

    protected $restrict;

    public function __construct()
    {
        // ponytail: getAction('name') is null on auto-routes; getName() holds
        // 'kartu.getTable' etc. Fallback keeps old behavior where set.
        $this->module = request()->route()->getName() ?? request()->route()->getAction('name');
        $this->restrict = config('permision');
    }

    // ponytail: custom controller methods (getPos, getTunai, ...) map to
    // abilities without explicit methods. __call keeps deny-list semantics:
    // listed = deny, unlisted = allow (same as save/create/update/table/...).
    public function __call($method, $args): Response
    {
        $user = $args[0] ?? null;
        if (! $user instanceof User) {
            return Response::deny('Unauthorized.');
        }

        return $this->accessProtected($user, $method) ? Response::deny() : Response::allow();
    }

    private function accessProtected($user, $permision)
    {
        $role = $user->role ?? 'guest';

        if (isset($this->restrict[$role][$this->module])) {

            if (in_array($permision, $this->restrict[$role][$this->module])) {
                return true;
            }
        }

        return false;
    }

    public function save(User $user): Response
    {
        return $this->accessProtected($user, __FUNCTION__) ? Response::deny() : Response::allow();
    }

    public function create(User $user): Response
    {
        return $this->accessProtected($user, __FUNCTION__) ? Response::deny() : Response::allow();
    }

    public function update(User $user): Response
    {
        return $this->accessProtected($user, __FUNCTION__) ? Response::deny() : Response::allow();
    }

    public function table(User $user): Response
    {
        return $this->accessProtected($user, __FUNCTION__) ? Response::deny() : Response::allow();
    }

    public function delete(User $user): Response
    {
        return $this->accessProtected($user, __FUNCTION__) ? Response::deny() : Response::allow();
    }

    public function show(User $user): Response
    {
        return $this->accessProtected($user, __FUNCTION__) ? Response::deny() : Response::allow();
    }

    public function prepare(User $user): Response
    {
        return $this->accessProtected($user, __FUNCTION__) ? Response::deny() : Response::allow();
    }

    public function prepareSo(User $user): Response
    {
        return $this->accessProtected($user, __FUNCTION__) ? Response::deny() : Response::allow();
    }

    public function storeship(User $user): Response
    {
        return $this->accessProtected($user, __FUNCTION__) ? Response::deny() : Response::allow();
    }
}
