<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerExportController extends Controller
{
    public function exportCsv()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="clients_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            
            // UTF-8 BOM pour Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // En-têtes CSV
            fputcsv($file, ['Numero', 'Type', 'Prenom', 'Nom', 'Entreprise', 'Email', 'Telephone', 'Ville', 'Statut']);

            // Données (filtrées par HasCompany automatiquement)
            Customer::chunk(100, function ($customers) use ($file) {
                foreach ($customers as $customer) {
                    fputcsv($file, [
                        $customer->customer_number,
                        $customer->type,
                        $customer->first_name,
                        $customer->last_name,
                        $customer->company_name,
                        $customer->email,
                        $customer->phone,
                        $customer->city,
                        $customer->status,
                    ]);
                }
            });

            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
