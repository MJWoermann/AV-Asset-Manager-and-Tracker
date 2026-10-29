<?php

namespace App\Http\Controllers;

use App\Exports\AssetsExport;
use App\Models\AssetList;
use App\Support\PerformanceLimits;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function assets(Request $request): BinaryFileResponse
    {
        $validated = $request->validate([
            'format' => ['nullable', 'in:xlsx,csv'],
            'list_id' => ['nullable', 'integer'],
            'asset_ids' => ['nullable', 'string', 'max:500000'],
        ]);

        $format = $validated['format'] ?? 'xlsx';
        $listId = $request->integer('list_id') ?: null;
        $assetIds = $listId === null
            ? $this->selectedAssetIds($validated['asset_ids'] ?? null)
            : null;
        $filename = 'assets-export-'.now()->format('Ymd-His').'.'.$format;

        return Excel::download(new AssetsExport($listId, $assetIds), $filename);
    }

    public function list(AssetList $list, Request $request): BinaryFileResponse
    {
        $format = $request->validate(['format' => ['nullable', 'in:xlsx,csv']])['format'] ?? 'xlsx';
        $filename = (Str::slug($list->name) ?: 'list').'-'.now()->format('Ymd-His').'.'.$format;

        return Excel::download(new AssetsExport($list->id), $filename);
    }

    /**
     * @return list<int>|null
     */
    private function selectedAssetIds(?string $raw): ?array
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $ids = [];

        foreach (explode(',', $raw) as $part) {
            $part = trim($part);

            if ($part === '') {
                continue;
            }

            if (! ctype_digit($part) || (string) (int) $part !== $part) {
                throw ValidationException::withMessages([
                    'asset_ids' => 'Selected assets must be a list of asset ids.',
                ]);
            }

            $ids[] = (int) $part;
        }

        $ids = array_values(array_unique($ids));

        if ($ids === []) {
            return null;
        }

        $max = PerformanceLimits::int('export_max_rows');

        if (count($ids) > $max) {
            throw ValidationException::withMessages([
                'asset_ids' => sprintf('Select at most %d assets to export.', $max),
            ]);
        }

        return $ids;
    }
}
