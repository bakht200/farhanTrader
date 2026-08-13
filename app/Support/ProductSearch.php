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
                // Strip non-alphanumerics in SQL so punctuation differences still match
                $q->orWhereRaw(
                    "LOWER(REGEXP_REPLACE(COALESCE({$name}, ''), '[^a-zA-Z0-9]+', ' ')) LIKE ?",
                    [$normalizedLike]
                )->orWhereRaw(
                    "LOWER(REGEXP_REPLACE(COALESCE({$sku}, ''), '[^a-zA-Z0-9]+', ' ')) LIKE ?",
                    [$normalizedLike]
                );
            }
        });
    }
}
