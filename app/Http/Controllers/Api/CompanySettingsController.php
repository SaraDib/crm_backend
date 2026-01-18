<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CompanyContext;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CompanySettingsController extends Controller
{
    protected $companyContext;

    public function __construct(CompanyContext $companyContext)
    {
        $this->companyContext = $companyContext;
    }

    /**
     * Get current company settings
     */
    public function show()
    {
        $company = $this->companyContext->getCompany();
        if (!$company) {
            return response()->json(['message' => 'Aucune entreprise sélectionnée ou associée.'], 404);
        }
        
        $data = $company->toArray();
        $data['logo_url'] = $company->logo ? asset('storage/' . $company->logo) : null;
        
        return response()->json($data);
    }

    /**
     * Update company settings
     */
    public function update(Request $request)
    {
        $company = $this->companyContext->getCompany();
        if (!$company) {
            return response()->json(['message' => 'Aucune entreprise sélectionnée ou associée.'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'secondary_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'tax_id' => 'nullable|string|max:50',
            'vat_number' => 'nullable|string|max:50',
            'business_registration_number' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'timezone' => 'nullable|string|max:100',
            'currency' => 'nullable|string|size:3',
        ]);

        if ($request->hasFile('logo')) {
            $request->validate(['logo' => 'image|mimes:jpeg,png,jpg,gif|max:2048']);
            
            // Delete old logo if exists
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }

            $path = $request->file('logo')->store('logos', 'public');
            $validated['logo'] = $path;
        }

        $company->update($validated);

        $data = $company->toArray();
        $data['logo_url'] = $company->logo ? asset('storage/' . $company->logo) : null;

        return response()->json([
            'message' => 'Paramètres de l\'entreprise mis à jour avec succès.',
            'company' => $data
        ]);
    }
}
