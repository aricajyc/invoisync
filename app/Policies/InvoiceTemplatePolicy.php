<?php

namespace App\Policies;

use App\Models\InvoiceTemplate;
use App\Models\User;

class InvoiceTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, InvoiceTemplate $template): bool
    {
        return $user->id === $template->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, InvoiceTemplate $template): bool
    {
        return $user->id === $template->user_id;
    }

    public function delete(User $user, InvoiceTemplate $template): bool
    {
        return $user->id === $template->user_id;
    }
}