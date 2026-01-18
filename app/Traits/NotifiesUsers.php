<?php

namespace App\Traits;

use App\Models\User;
use App\Notifications\CrmNotification;
use Illuminate\Support\Facades\Notification;

trait NotifiesUsers
{
    /**
     * Notify all admins and managers of a company.
     */
    protected function notifyCompanyStaff($companyId, $title, $message, $actionUrl = '', $type = 'info', $excludeUserId = null)
    {
        $users = User::whereHas('companies', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->get();

        if ($excludeUserId) {
            $users = $users->filter(fn($u) => $u->id !== $excludeUserId);
        }

        Notification::send($users, new CrmNotification($title, $message, $actionUrl, $type));
    }

    /**
     * Notify all super admins.
     */
    protected function notifySuperAdmins($title, $message, $actionUrl = '', $type = 'warning')
    {
        $superAdmins = User::where('user_type', 'system')
            ->whereHas('roles', function ($query) {
                $query->where('slug', 'super-admin');
            })->get();

        Notification::send($superAdmins, new CrmNotification($title, $message, $actionUrl, $type));
    }
}
