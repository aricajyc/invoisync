<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    /**
     * Determine if the user can view any invoices
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can view the invoice
     */
    public function view(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->user_id;
    }

    /**
     * Determine if the user can create invoices
     */
    public function create(User $user): bool
    {
        return $user->status === 'active';
    }

    /**
     * Determine if the user can update the invoice
     */
    public function update(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->user_id;
    }

    /**
     * Determine if the user can delete the invoice
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->user_id;
    }

    /**
     * Determine if the user can validate the invoice
     */
    public function validate(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->user_id 
            && $invoice->status === 'draft';
    }

    /**
     * Determine if the user can submit the invoice
     */
    public function submit(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->user_id 
            && $invoice->status === 'validated'
            && empty($invoice->myinvois_uid);
    }

    /**
     * Determine if the user can cancel the invoice
     */
    public function cancel(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->user_id 
            && $invoice->canCancel();
    }
}