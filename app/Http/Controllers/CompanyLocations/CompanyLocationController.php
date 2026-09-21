<?php

namespace App\Http\Controllers\CompanyLocations;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyLocationRequest;
use App\Http\Requests\UpdateCompanyLocationRequest;
use App\Services\CompanyLocations\CompanyLocationService;

class CompanyLocationController extends Controller
{
    public function __construct(
        private CompanyLocationService $companyLocationService
    ) {}

    /**
     * Create company location.
     */
    public function store(StoreCompanyLocationRequest $request)
    {
        $location = $this->companyLocationService->create(
            $request->validated()
        );

        return ResponseHelper::success(
            data: $location,
            message: __('company_location.created'),
            statusCode: 201
        );
    }

    /**
     * Update company location.
     */
    public function update(UpdateCompanyLocationRequest $request, int $id)
    {
        // dd($request->validated());
        $location = $this->companyLocationService->update($id, $request->validated());

        return ResponseHelper::success(
            data: $location,
            message: __('company_location.updated')
        );
    }

    /**
     * Deactivate company location.
     */
    public function deactivate(int $id)
    {
        $location = $this->companyLocationService->deactivate($id);

        return ResponseHelper::success(
            data: $location,
            message: __('company_location.deactivated')
        );
    }

    /**
     * Activate company location.
     */
    public function activate(int $id)
    {
        $location = $this->companyLocationService->activate($id);

        return ResponseHelper::success(
            data: $location,
            message: __('company_location.activated')
        );
    }

    /**
     * Get active company location.
     */
    public function activeLocation()
    {
        $location = $this->companyLocationService->getActiveLocation();

        return ResponseHelper::success(
            data: $location,
            message: __('company_location.active_retrieved')
        );
    }
}
