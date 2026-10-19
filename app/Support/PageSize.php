<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

class PageSize
{
    public const PARAM = 'per_page';

    public const DEFAULT = 50;

    public const ALL = 'all';

    /**
     * Allowed numeric page sizes (plus {@see ALL}).
     *
     * @var list<int>
     */
    public const OPTIONS = [10, 50, 100, 200, 500];

    /**
     * @return list<int|string>
     */
    public static function optionValues(): array
    {
        return [...self::OPTIONS, self::ALL];
    }

    /**
     * Selected option for the UI (`10`…`500` or `all`).
     */
    public static function selected(Request $request, int $default = self::DEFAULT): int|string
    {
        $raw = $request->input(self::PARAM);

        if (is_string($raw) && strtolower(trim($raw)) === self::ALL) {
            return self::ALL;
        }

        if (is_numeric($raw)) {
            $size = (int) $raw;
            if (in_array($size, self::OPTIONS, true)) {
                return $size;
            }
        }

        return $default;
    }

    /**
     * Page size to pass to `paginate()` (ALL uses the current result total).
     *
     * @param  Builder<Model>|Relation<Model, Model, mixed>  $query
     */
    public static function perPage(
        Builder|Relation $query,
        Request $request,
        int $default = self::DEFAULT,
    ): int {
        $selected = self::selected($request, $default);

        if ($selected === self::ALL) {
            return max(1, (clone $query)->count());
        }

        return $selected;
    }

    /**
     * @param  Builder<Model>|Relation<Model, Model, mixed>  $query
     * @return LengthAwarePaginator<Model>
     */
    public static function paginate(
        Builder|Relation $query,
        Request $request,
        int $default = self::DEFAULT,
    ): LengthAwarePaginator {
        return $query
            ->paginate(self::perPage($query, $request, $default))
            ->withQueryString();
    }
}
