<?php

namespace App\Services;

class CompanyContext
{
    protected ?int $companyId = null;

    public function setCompanyId(int $id): void
    {
        $this->companyId = $id;
    }

    public function getCompanyId(): ?int
    {
        return $this->companyId;
    }

    public function hasCompanyId(): bool
    {
        return !is_null($this->companyId);
    }

    public function getCompany(): ?\App\Models\Company
    {
        if (!$this->hasCompanyId()) {
            return null;
        }
        return \App\Models\Company::find($this->companyId);
    }
}
