<?php

namespace App\Policies;

use App\User;
use App\local_catalog;
use Illuminate\Auth\Access\HandlesAuthorization;

class CatalogPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the localCatalog.
     *
     * @param  \App\User  $user
     * @param  \App\local_catalog  $localCatalog
     * @return mixed
     */
    public function view(User $user, local_catalog $localCatalog)
    {
        //
    }

    /**
     * Determine whether the user can create localCatalogs.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the localCatalog.
     *
     * @param  \App\User  $user
     * @param  \App\local_catalog  $localCatalog
     * @return mixed
     */
    public function update(User $user, local_catalog $localCatalog)
    {
        //
    }

    /**
     * Determine whether the user can delete the localCatalog.
     *
     * @param  \App\User  $user
     * @param  \App\local_catalog  $localCatalog
     * @return mixed
     */
    public function delete(User $user, local_catalog $localCatalog)
    {
        //
    }
}
