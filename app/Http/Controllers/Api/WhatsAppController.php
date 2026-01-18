<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsappConnection;
use App\Services\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{
    private $managerUrl;

    public function __construct()
    {
        $this->managerUrl = env('WHATSAPP_MANAGER_URL', 'http://127.0.0.1:5001');
    }

    /**
     * Get WhatsApp status for current company
     */
    public function status()
    {
        $companyId = app(CompanyContext::class)->getCompanyId();
        $connection = WhatsappConnection::firstOrCreate(
            ['company_id' => $companyId],
            ['status' => 'disconnected']
        );

        // Check with manager
        try {
            $response = Http::get("{$this->managerUrl}/companies/{$companyId}/whatsapp/status");
            $managerStatus = $response->json();
            
            if ($managerStatus['running']) {
                return response()->json([
                    'status' => $connection->status,
                    'port' => $connection->getOrAssignPort(),
                    'phone_number' => $connection->phone_number,
                    'last_connected_at' => $connection->last_connected_at,
                    'running' => true
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to connect to WhatsApp manager: " . $e->getMessage());
        }

        return response()->json([
            'status' => 'disconnected',
            'port' => $connection->getOrAssignPort(),
            'running' => false
        ]);
    }

    /**
     * Start WhatsApp instance for current company
     */
    public function start()
    {
        $companyId = app(CompanyContext::class)->getCompanyId();
        $connection = WhatsappConnection::firstOrCreate(
            ['company_id' => $companyId],
            ['status' => 'disconnected']
        );

        try {
            $port = $connection->getOrAssignPort();
            
            // Request manager to start instance
            $response = Http::post("{$this->managerUrl}/companies/{$companyId}/whatsapp/start");
            
            if ($response->successful()) {
                $connection->update([
                    'status' => 'connecting',
                    'instance_port' => $port
                ]);

                return response()->json([
                    'success' => true,
                    'port' => $port,
                    'message' => 'WhatsApp instance starting'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to start WhatsApp instance'
            ], 500);

        } catch (\Exception $e) {
            Log::error("Failed to start WhatsApp: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stop WhatsApp instance
     */
    public function stop()
    {
        $companyId = app(CompanyContext::class)->getCompanyId();
        
        try {
            Http::post("{$this->managerUrl}/companies/{$companyId}/whatsapp/stop");
            
            WhatsappConnection::where('company_id', $companyId)->update([
                'status' => 'disconnected',
                'last_disconnected_at' => now()
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Restart WhatsApp instance
     */
    public function restart()
    {
        $companyId = app(CompanyContext::class)->getCompanyId();
        
        try {
            Http::post("{$this->managerUrl}/companies/{$companyId}/whatsapp/restart");
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Receive status updates from WhatsApp instance (webhook)
     */
    public function statusUpdate(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'status' => 'required|in:connecting,connected,disconnected,error',
            'port' => 'required|integer',
            'qr_code' => 'nullable|string',
            'phone_number' => 'nullable|string',
        ]);

        $connection = WhatsappConnection::where('company_id', $validated['company_id'])->first();
        
        if (!$connection) {
            return response()->json(['message' => 'Connection not found'], 404);
        }

        $updateData = [
            'status' => $validated['status'],
            'instance_port' => $validated['port']
        ];

        if (isset($validated['qr_code'])) {
            $updateData['qr_code'] = $validated['qr_code'];
        }

        if (isset($validated['phone_number'])) {
            $updateData['phone_number'] = $validated['phone_number'];
        }

        if ($validated['status'] === 'connected') {
            $updateData['last_connected_at'] = now();
            $updateData['qr_code'] = null;
        } elseif ($validated['status'] === 'disconnected') {
            $updateData['last_disconnected_at'] = now();
        }

        $connection->update($updateData);

        return response()->json(['success' => true]);
    }

    /**
     * Send a document (Invoice, Quote, etc) via WhatsApp
     */
    public function sendDocument(Request $request)
    {
        $request->validate([
            'type' => 'required|in:invoice,quote,credit_note',
            'id' => 'required|integer',
            'phone' => 'nullable|string' // Optional override
        ]);

        try {
            // Find the document
            $modelClass = match($request->type) {
                'invoice', 'credit_note' => \App\Models\CustomerInvoice::class,
                'quote' => \App\Models\Quote::class,
            };

            $document = $modelClass::findOrFail($request->id);
            
            $service = new \App\Services\WhatsAppDocumentService();
            $success = $service->send($document, $request->type);

            if ($success) {
                return response()->json(['success' => true, 'message' => 'Document envoyé avec succès']);
            } else {
                return response()->json(['success' => false, 'message' => 'L\'envoi a échoué (vérifiez si WhatsApp est connecté)'], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Erreur lors de l\'envoi : ' . $e->getMessage()
            ], 500);
        }
    }
}
