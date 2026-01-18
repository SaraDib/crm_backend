<?php

namespace App\Services;

use App\Models\WhatsappConnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class WhatsAppDocumentService
{
    /**
     * Send a document via WhatsApp automatically
     * 
     * @param mixed $document (CustomerInvoice or Quote)
     * @param string $type (invoice, quote, credit_note)
     * @return bool
     */
    public function send($document, string $type)
    {
        try {
            $connection = WhatsappConnection::where('company_id', $document->company_id)->first();

            if (!$connection || $connection->status !== 'connected') {
                return false;
            }

            $customer = $document->customer;
            if (!$customer || !$customer->phone) {
                return false;
            }

            // Generate PDF
            $view = match($type) {
                'invoice', 'credit_note' => 'pdf.invoice',
                'quote' => 'pdf.quote',
            };

            $document->load(['customer', 'items', 'company']);
            $pdf = Pdf::loadView($view, [$type => $document]);
            $pdfContent = base64_encode($pdf->output());

            $ref = match($type) {
                'invoice', 'credit_note' => $document->invoice_number,
                'quote' => $document->quote_number,
            };

            $docLabel = match($type) {
                'invoice' => "Facture",
                'quote' => "Devis",
                'credit_note' => "Avoir",
            };

            $fileName = "{$docLabel}_{$ref}.pdf";
            $message = "Bonjour {$customer->first_name},\n\nVoici votre {$docLabel} {$ref}.\n\nMerci de votre confiance !";

            // Send via Instance using /send-document
            $port = $connection->instance_port;
            $response = Http::timeout(30)->post("http://127.0.0.1:{$port}/send-document", [
                'number' => $customer->phone,
                'base64' => $pdfContent,
                'fileName' => $fileName,
                'caption' => $message
            ]);

            return $response->successful();

        } catch (\Exception $e) {
            Log::error("WhatsApp Auto Send Error: " . $e->getMessage());
            return false;
        }
    }
}
