<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use Auditable, SoftDeletes;

    /**
     * Prevent deletion while the role is assigned to at least one user.
     */
    public function delete()
    {
        $hasAssignedUsers = DB::table(config('permission.table_names.model_has_roles'))
            ->where('role_id', $this->id)
            ->where('model_type', User::class)
            ->exists();

        if ($hasAssignedUsers) {
            throw ValidationException::withMessages([
                'role' => 'Rola se ne može obrisati dok ima korisnike.',
            ]);
        }

        return parent::delete();
    }
}
