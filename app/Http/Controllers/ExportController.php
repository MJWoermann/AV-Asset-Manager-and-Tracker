<?php

namespace App\Http\Controllers;

use App\Exports\AssetsExport;
use App\Models\AssetList;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
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
