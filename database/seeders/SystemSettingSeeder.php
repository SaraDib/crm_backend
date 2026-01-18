<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            ['key' => 'site_name', 'value' => 'Antigravity CRM', 'type' => 'string', 'group' => 'general', 'description' => 'Le nom de votre plateforme'],
            ['key' => 'site_description', 'value' => 'Solution CRM SaaS pour entreprises marocaines', 'type' => 'string', 'group' => 'general', 'description' => 'Description pour le SEO'],
            ['key' => 'currency', 'value' => 'MAD', 'type' => 'string', 'group' => 'general', 'description' => 'Devise principale du système'],
            
            // SMTP / Email
            ['key' => 'mail_host', 'value' => 'smtp.mailtrap.io', 'type' => 'string', 'group' => 'email', 'description' => 'Hôte SMTP'],
            ['key' => 'mail_port', 'value' => '2525', 'type' => 'integer', 'group' => 'email', 'description' => 'Port SMTP'],
            ['key' => 'mail_username', 'value' => '', 'type' => 'string', 'group' => 'email', 'description' => 'Utilisateur SMTP'],
            ['key' => 'mail_password', 'value' => '', 'type' => 'string', 'group' => 'email', 'description' => 'Mot de passe SMTP'],
            ['key' => 'mail_from_address', 'value' => 'noreply@crm.ma', 'type' => 'string', 'group' => 'email', 'description' => 'Adresse expéditeur'],
            
            // Payments
            ['key' => 'stripe_public_key', 'value' => '', 'type' => 'string', 'group' => 'payment', 'description' => 'Clé publique Stripe'],
            ['key' => 'stripe_secret_key', 'value' => '', 'type' => 'string', 'group' => 'payment', 'description' => 'Clé secrète Stripe'],
            ['key' => 'tax_rate', 'value' => '20', 'type' => 'integer', 'group' => 'payment', 'description' => 'Taux de TVA par défaut (%)'],
        ];

        foreach ($settings as $setting) {
            DB::table('system_settings')->updateOrInsert(['key' => $setting['key']], $setting + ['created_at' => now(), 'updated_at' => now()]);
        }
    }
}
