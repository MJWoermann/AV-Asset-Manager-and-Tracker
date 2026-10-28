<?php

namespace App\Http\Controllers;

use App\Exports\CheckoutReportExport;
use App\Http\Requests\CheckoutReportRequest;
use App\Http\Requests\StoreCheckoutPurposeRequest;
use App\Http\Requests\UpdateCheckoutPurposeRequest;
use App\Models\CheckoutPurpose;
use App\Models\ItemType;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\ManufacturerModel;
use App\Models\User;
use App\Services\CheckoutPurposeService;
use App\Services\CheckoutReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CheckoutReportController extends Controller
{
    public function index(CheckoutReportRequest $request, CheckoutReportService $reports): View
    {
        $filters = $reports->filters($request->validated());

        return view('reports.checkouts', [
            'filters' => $filters,
            'query' => $reports->queryParameters($filters),
            'filtersActive' => $reports->filtersActive($filters),
            'modelRows' => $filters['view'] === 'models' ? $reports->modelSummary($filters) : [],
            'holderGroups' => $filters['view'] === 'holders' ? $reports->holderGroups($filters) : [],
            'purposes' => CheckoutPurpose::query()->orderBy('sort_order')->orderBy('name')->get(),
            ...$this->filterOptions(),
        ]);
    }

    public function export(CheckoutReportRequest $request, CheckoutReportService $reports): BinaryFileResponse
    {
        $filters = $reports->filters($request->validated());
        $format = $request->string('format')->toString();

        if (! in_array($format, ['xlsx', 'csv'], true)) {
            $format = 'xlsx';
        }

        $modelRows = $filters['view'] === 'models' ? $reports->modelSummary($filters) : [];
        $holderGroups = $filters['view'] === 'holders' ? $reports->holderGroups($filters, true) : [];
        $filename = 'checkouts-by-'.$filters['view'].'-'.now()->format('Ymd-His').'.'.$format;

        return Excel::download(
            new CheckoutReportExport($filters['view'], $modelRows, $holderGroups),
            $filename,
        );
    }

    public function storePurpose(StoreCheckoutPurposeRequest $request, CheckoutPurposeService $purposes): RedirectResponse
    {
        $purposes->create($request->string('name')->toString());

        return back()->with('status', 'Checkout option added.');
    }

    public function updatePurpose(
        UpdateCheckoutPurposeRequest $request,
        CheckoutPurpose $checkoutPurpose,
        CheckoutPurposeService $purposes,
    ): RedirectResponse {
        $purposes->update(
            $checkoutPurpose,
            $request->string('name')->toString(),
            $request->boolean('is_active'),
        );

        return back()->with('status', 'Checkout option saved.');
    }

    public function destroyPurpose(CheckoutPurpose $checkoutPurpose, CheckoutPurposeService $purposes): RedirectResponse
    {
        $purposes->delete($checkoutPurpose);

        return back()->with('status', 'Checkout option deleted.');
    }

    /**
     * @return array{
     *     purposeOptions: list<array{value: string, label: string}>,
     *     manufacturerOptions: list<array{value: string, label: string}>,
     *     modelOptions: list<array{value: string, label: string}>,
     *     itemTypeOptions: list<array{value: string, label: string}>,
     *     locationOptions: list<array{value: string, label: string}>,
     *     userOptions: list<array{value: string, label: string}>
     * }
     */
    protected function filterOptions(): array
    {
        return [
            'purposeOptions' => CheckoutPurpose::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn (CheckoutPurpose $purpose): array => [
                    'value' => $purpose->slug,
                    'label' => $purpose->is_active ? $purpose->name : $purpose->name.' (inactive)',
                ])
                ->all(),
            'manufacturerOptions' => Manufacturer::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Manufacturer $manufacturer): array => [
                    'value' => (string) $manufacturer->id,
                    'label' => $manufacturer->name,
                ])
                ->all(),
            'modelOptions' => ManufacturerModel::query()
                ->with('manufacturer:id,name')
                ->orderBy('name')
                ->get(['id', 'manufacturer_id', 'name'])
                ->map(fn (ManufacturerModel $model): array => [
                    'value' => (string) $model->id,
                    'label' => trim(($model->manufacturer?->name ?? '').' — '.$model->name, ' —'),
                ])
                ->all(),
            'itemTypeOptions' => ItemType::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (ItemType $type): array => [
                    'value' => (string) $type->id,
                    'label' => $type->name,
                ])
                ->all(),
            'locationOptions' => Location::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Location $location): array => [
                    'value' => (string) $location->id,
                    'label' => $location->name,
                ])
                ->all(),
            'userOptions' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(fn (User $user): array => [
                    'value' => (string) $user->id,
                    'label' => $user->name.' ('.$user->email.')',
                ])
                ->all(),
        ];
    }
}
