<?php

namespace App\Policies;

use App\Models\BulkUploadBatch;
use App\Models\User;

class BulkUploadBatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->user_type === 'B2B';
    }

    public function view(User $user, BulkUploadBatch $batch): bool
    {
        return $user->id === $batch->user_id && $user->user_type === 'B2B';
    }

    public function create(User $user): bool
    {
        return $user->user_type === 'B2B' && $user->status === 'active';
    }

    public function update(User $user, BulkUploadBatch $batch): bool
    {
        return $user->id === $batch->user_id && $user->user_type === 'B2B';
    }

    public function delete(User $user, BulkUploadBatch $batch): bool
    {
        return $user->id === $batch->user_id 
            && $user->user_type === 'B2B'
            && in_array($batch->status, ['completed', 'failed']);
    }
}