<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\CustomerPayment;
use App\Models\SupplierInvoice;
use App\Models\Reminder;
use App\Models\Quote;
use App\Services\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats()
    {
        $companyId = app(CompanyContext::class)->getCompanyId();

        // Total Clients
        $totalCustomers = Customer::where('company_id', $companyId)->count();

        // Invoices Pending (draft or sent)
        $pendingInvoices = CustomerInvoice::where('company_id', $companyId)
            ->whereIn('status', ['draft', 'sent', 'partial'])
            ->count();

        // Sales of the month
        $monthlySales = CustomerInvoice::where('company_id', $companyId)
            ->where('status', 'paid')
            ->whereMonth('invoice_date', now()->month)
            ->whereYear('invoice_date', now()->year)
            ->sum('total');

        // Recent Payments
        $recentPayments = CustomerPayment::where('company_id', $companyId)
            ->with(['customer', 'invoice'])
            ->orderBy('payment_date', 'desc')
            ->limit(5)
            ->get();

        // Monthly Earnings Chart Data (last 6 months)
        $monthlyEarnings = CustomerPayment::where('company_id', $companyId)
            ->select(
                DB::raw('SUM(amount) as total'),
                DB::raw("DATE_FORMAT(payment_date, '%Y-%m') as month")
            )
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(6)
            ->get()
            ->reverse()
            ->values();

        // Customer Growth (last 6 months)
        $customerGrowth = Customer::where('company_id', $companyId)
            ->select(
                DB::raw('COUNT(id) as count'),
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month")
            )
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(6)
            ->get()
            ->reverse()
            ->values();

        // Supplier Invoices Pending
        $pendingSupplierBills = SupplierInvoice::where('company_id', $companyId)
            ->where('status', 'pending')
            ->sum('amount');
            
        // Upcoming Reminders
        $upcomingReminders = Reminder::where('company_id', $companyId)
            ->where('status', 'pending')
            ->where('reminder_date', '>=', now()->toDateString())
            ->orderBy('reminder_date', 'asc')
            ->limit(5)
            ->get();
            
        // Total TVA Collectée (sum of tax_amount from customer invoices)
        $totalTva = CustomerInvoice::where('company_id', $companyId)
            ->where('status', '!=', 'cancelled')
            ->sum('tax_amount');
            
        return response()->json([
            'total_customers' => $totalCustomers,
            'pending_invoices' => $pendingInvoices,
            'monthly_sales' => $monthlySales,
            'pending_supplier_bills' => $pendingSupplierBills,
            'recent_payments' => $recentPayments,
            'monthly_earnings' => $monthlyEarnings,
            'customer_growth' => $customerGrowth,
            'upcoming_reminders' => $upcomingReminders,
            'total_tva' => $totalTva,
        ]);
    }
}
