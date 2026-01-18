<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Seeder;

class SupportTicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::first();
        $admin = User::where('user_type', 'system')->first();
        
        if ($company && $admin) {
            $companyUser = $company->users()->first();
            
            // If no user is linked to the company, link one
            if (!$companyUser) {
                $anyUser = User::where('user_type', '!=', 'system')->first() ?: User::first();
                $company->users()->attach($anyUser->id, ['role_id' => 1, 'is_owner' => true]);
                $companyUser = $anyUser;
            }

            $ticket = SupportTicket::create([
                'company_id' => $company->id,
                'user_id' => $companyUser->id,
                'assigned_to' => $admin->id,
                'subject' => 'Problème de connexion Dashboard',
                'description' => 'Je ne parviens pas à accéder aux statistiques de mon entreprise.',
                'priority' => 'high',
                'status' => 'open',
                'category' => 'Technical',
            ]);

            $ticket->messages()->create([
                'user_id' => $companyUser->id,
                'message' => 'Bonjour, j\'ai un problème de connexion sur mon tableau de bord.',
            ]);

            $ticket->messages()->create([
                'user_id' => $admin->id,
                'message' => 'Bonjour, nous regardons cela immédiatement.',
                'is_internal' => false,
            ]);
        }
    }
}
