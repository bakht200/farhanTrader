<?php

namespace App\Support;

class ProductSearch
{
    /**
     * Normalize text for fuzzy product matching (ignore punctuation / extra spaces).
     */
    public static function normalize(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9]+/i', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    /**
     * SQL expression that strips common punctuation so searches like
     * "STRAWBERRY SYRUP (MOCCA)" match "STRAWBERRY SYRUP MOCCA (1*12)".
     * Uses nested REPLACE for broad MySQL/MariaDB compatibility (no REGEXP_REPLACE).
     */
    public static function sqlNormalizedColumn(string $column): string
    {
        $expr = "LOWER(COALESCE({$column}, ''))";
        foreach (['(', ')', '-', '/', '*', '.', ',', '_', '[', ']', '{', '}', '+', '#', "'", '"'] as $char) {
            $escaped = str_replace("'", "''", $char);
            $expr = "REPLACE({$expr}, '{$escaped}', ' ')";
        }

        return "TRIM(REPLACE(REPLACE(REPLACE({$expr}, '  ', ' '), '  ', ' '), '  ', ' '))";
    }

    /**
     * Apply name/sku/brand search that also matches without parentheses, e.g.
     * "STRAWBERRY SYRUP (MOCCA)" finds "STRAWBERRY SYRUP MOCCA (1*12)".
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  string  $tablePrefix  e.g. '' or 'products.'
     */
    public static function apply($query, string $search, string $tablePrefix = ''): void
    {
        $search = trim($search);
        if ($search === '') {
            return;
        }

        $normalized = self::normalize($search);
        $like = '%'.$search.'%';
        $normalizedLike = $normalized !== '' ? '%'.$normalized.'%' : null;

        $name = $tablePrefix.'name';
        $sku = $tablePrefix.'sku';
        $brand = $tablePrefix.'brand';

        $query->where(function ($q) use ($like, $normalizedLike, $name, $sku, $brand) {
            $q->where($name, 'like', $like)
                ->orWhere($sku, 'like', $like)
                ->orWhere($brand, 'like', $like);

            if ($normalizedLike) {
                $normName = self::sqlNormalizedColumn($name);
                $normSku = self::sqlNormalizedColumn($sku);
                $q->orWhereRaw("{$normName} LIKE ?", [$normalizedLike])
                    ->orWhereRaw("{$normSku} LIKE ?", [$normalizedLike]);
            }
        });
    }
}
