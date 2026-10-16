<?php

namespace App\Http\Controllers\Api;

use App\Exports\AssetsExport;
use App\Http\Controllers\Controller;
use App\Models\AssetList;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportApiController extends Controller
{
    public function assets(Request $request): BinaryFileResponse
    {
        $format = $request->validate(['format' => ['nullable', 'in:xlsx,csv']])['format'] ?? 'xlsx';
        $listId = $request->integer('list_id') ?: null;
        $filename = 'assets-export-'.now()->format('Ymd-His').'.'.$format;

        return Excel::download(new AssetsExport($listId), $filename);
    }

    public function list(AssetList $list, Request $request): BinaryFileResponse
    {
        $format = $request->validate(['format' => ['nullable', 'in:xlsx,csv']])['format'] ?? 'xlsx';
        $filename = (Str::slug($list->name) ?: 'list').'-'.now()->format('Ymd-His').'.'.$format;

        return Excel::download(new AssetsExport($list->id), $filename);
    }
}
