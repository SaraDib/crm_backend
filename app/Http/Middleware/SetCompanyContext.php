<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\CompanyContext;
use Symfony\Component\HttpFoundation\Response;

class SetCompanyContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $context = app(CompanyContext::class);

        if ($user) {
            if ($user->user_type === 'system') {
                // System users (Super Admin/Admin/Support) can switch context via header
                $companyId = $request->header('X-Company-ID');
                if ($companyId && is_numeric($companyId)) {
                    $context->setCompanyId((int) $companyId);
                }
            } else {
                // Company users are locked to their own company
                // For simplicity, we take the first company they are linked to
                $company = $user->companies()->first();
                if ($company) {
                    $context->setCompanyId($company->id);
                }
            }
        }

        return $next($request);
    }
}
