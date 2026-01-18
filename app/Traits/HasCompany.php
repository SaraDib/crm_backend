<?php

namespace App\Traits;

use App\Models\Company;
use App\Services\CompanyContext;
use Illuminate\Database\Eloquent\Builder;

trait HasCompany
{
    protected static function bootHasCompany()
    {
        $context = app(CompanyContext::class);

        static::creating(function ($model) use ($context) {
            if (!$model->company_id && $context->hasCompanyId()) {
                $model->company_id = $context->getCompanyId();
            }
        });

        static::addGlobalScope('company', function (Builder $builder) use ($context) {
            // Si on a un ID d'entreprise dans le contexte, on filtre
            if ($context->hasCompanyId()) {
                $builder->where($builder->getQuery()->from . '.company_id', $context->getCompanyId());
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
