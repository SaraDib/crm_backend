<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks to truncate tables
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('role_permissions')->truncate();
        DB::table('permissions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $permissions = [
            // --- SYSTEM PERMISSIONS (Super Admin) ---
            
            // Module: Companies
            ['name' => 'Voir les entreprises', 'slug' => 'view-companies', 'module' => 'Companies', 'type' => 'system', 'description' => 'Liste et consultation des fiches entreprises'],
            ['name' => 'Gérer les entreprises', 'slug' => 'manage-companies', 'module' => 'Companies', 'type' => 'system', 'description' => 'Création, modification et suppression des entreprises'],

            // Module: Commercial (Leads/Sales)
            ['name' => 'Voir le dashboard commercial', 'slug' => 'view-sales-dashboard', 'module' => 'Sales', 'type' => 'system', 'description' => 'Accès aux statistiques et leads globaux'],

            // Module: Subscription Plans
            ['name' => 'Gérer les plans d\'abonnement', 'slug' => 'manage-plans', 'module' => 'Plans', 'type' => 'system', 'description' => 'Configuration des offres et tarifs SaaS'],

            // Module: System Users (Équipe Admin)
            ['name' => 'Gérer l\'équipe admin', 'slug' => 'manage-system-users', 'module' => 'System Users', 'type' => 'system', 'description' => 'Gestion des utilisateurs de la plateforme (Staff)'],

            // Module: Roles & Permissions (System)
            ['name' => 'Gérer les rôles et permissions', 'slug' => 'manage-roles-permissions', 'module' => 'Roles', 'type' => 'system', 'description' => 'Contrôle total sur la matrice de sécurité system/company'],

            // Module: Subscriptions (Gestion Abonnements)
            ['name' => 'Gérer les abonnements clients', 'slug' => 'manage-subscriptions', 'module' => 'Subscriptions', 'type' => 'system', 'description' => 'Validation des paiements et activation des licences'],

            // Module: Subscription Invoices (Factures Abonnements)
            ['name' => 'Voir les factures d\'abonnement', 'slug' => 'view-subscription-invoices', 'module' => 'Subscription Invoices', 'type' => 'system', 'description' => 'Consultation des factures émises aux entreprises'],

            // Module: Support (System side)
            ['name' => 'Gérer le support technique', 'slug' => 'manage-support', 'module' => 'Support', 'type' => 'system', 'description' => 'Répondre aux tickets des entreprises'],

            // Module: Settings (System side)
            ['name' => 'Gérer les paramètres système', 'slug' => 'manage-settings', 'module' => 'Settings', 'type' => 'system', 'description' => 'Configuration globale de la plateforme'],

            // Module: Chat
            ['name' => 'Accès au Chat Système', 'slug' => 'access-chat', 'module' => 'Chat', 'type' => 'system', 'description' => 'Utilisation du module de communication interne'],

            // --- COMPANY PERMISSIONS (For Companies) ---
            
            // Module: Customers
            ['name' => 'Gérer les clients', 'slug' => 'manage-customers', 'module' => 'Customers', 'type' => 'company', 'description' => 'Liste, ajout et modification des clients de l\'entreprise'],

            // Module: Suppliers
            ['name' => 'Gérer les fournisseurs', 'slug' => 'manage-suppliers', 'module' => 'Suppliers', 'type' => 'company', 'description' => 'Gestion de la base fournisseurs et achats'],

            // Module: Products
            ['name' => 'Gérer le catalogue produits', 'slug' => 'manage-products', 'module' => 'Products', 'type' => 'company', 'description' => 'Configuration des produits et services vendus'],

            // Module: Finance (Quotes/Invoices/Payments)
            ['name' => 'Gérer la facturation et devis', 'slug' => 'manage-finance', 'module' => 'Finance', 'type' => 'company', 'description' => 'Émission de devis, factures et suivi des paiements'],

            // Module: WhatsApp
            ['name' => 'Utiliser WhatsApp', 'slug' => 'use-whatsapp', 'module' => 'WhatsApp', 'type' => 'company', 'description' => 'Envoi de messages et automation via l\'instance WhatsApp'],
            
            // Module: Team
            ['name' => 'Gérer l\'équipe interne', 'slug' => 'manage-team', 'module' => 'Team', 'type' => 'company', 'description' => 'Gestion des collaborateurs de l\'entreprise'],
            
            // Module: Reports
            ['name' => 'Voir les rapports et stats', 'slug' => 'view-reports', 'module' => 'Reports', 'type' => 'company', 'description' => 'Accès aux tableaux de bord analytiques'],

            // Module: Settings (Company side)
            ['name' => 'Paramètres entreprise', 'slug' => 'manage-company-settings', 'module' => 'Settings', 'type' => 'company', 'description' => 'Configuration de la fiche et des préférences société'],

            // Module: Support (Company side)
            ['name' => 'Accès au support technique', 'slug' => 'access-support', 'module' => 'Support', 'type' => 'company', 'description' => 'Envoyer et suivre des tickets au support plateforme'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->insert(array_merge($permission, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Assign all 'system' permissions to Super Admin role
        $superAdminRole = DB::table('roles')->where('slug', 'super-admin')->first();
        if ($superAdminRole) {
            $systemPermissions = DB::table('permissions')->where('type', 'system')->get();
            foreach ($systemPermissions as $permission) {
                DB::table('role_permissions')->insert([
                    'role_id' => $superAdminRole->id,
                    'permission_id' => $permission->id,
                    'created_at' => now(),
                ]);
            }
        }
        // Assign all 'company' permissions to Company Admin role
        $companyAdminRole = DB::table('roles')->where('slug', 'company-admin')->first();
        if ($companyAdminRole) {
            $companyPermissions = DB::table('permissions')->where('type', 'company')->get();
            foreach ($companyPermissions as $permission) {
                DB::table('role_permissions')->insert([
                    'role_id' => $companyAdminRole->id,
                    'permission_id' => $permission->id,
                    'created_at' => now(),
                ]);
            }
        }
    }
}
