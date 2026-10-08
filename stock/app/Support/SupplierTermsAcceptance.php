<?php

namespace App\Support;

use App\supplier;
use App\SupplierTerm;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class SupplierTermsAcceptance
{
    public static function publishedTerms(): ?SupplierTerm
    {
        return SupplierTerm::query()->where('id', SupplierTerm::SINGLETON_ID)->first();
    }

    public static function hasPublishedBody(?SupplierTerm $terms = null): bool
    {
        $terms = $terms ?? self::publishedTerms();

        return $terms && trim((string) $terms->body) !== '';
    }

    public static function mustAccept(int $supplierId): bool
    {
        $terms = self::publishedTerms();
        if (! self::hasPublishedBody($terms)) {
            return false;
        }

        $supplier = supplier::find($supplierId);
        if (! $supplier) {
            return false;
        }

        if (! Schema::hasColumn('suppliers', 'terms_accepted')) {
            return false;
        }

        if (! (int) $supplier->terms_accepted) {
            return true;
        }

        if (! $supplier->terms_accepted_terms_updated_at || ! $terms->updated_at) {
            return false;
        }

        return Carbon::parse($terms->updated_at)->gt(
            Carbon::parse($supplier->terms_accepted_terms_updated_at)
        );
    }

    /**
     * @return array{required: bool, title: string, bodyHtml: string}
     */
    public static function gateForSupplier(int $supplierId): array
    {
        $terms = self::publishedTerms();
        $bodyHtml = SupplierTermsFormatter::toHtml(optional($terms)->body);
        $required = self::mustAccept($supplierId);

        return [
            'required' => $required,
            'title' => $terms && $terms->title ? $terms->title : 'Supplier terms & conditions',
            'bodyHtml' => $bodyHtml,
        ];
    }

    public static function markAccepted(supplier $supplier): void
    {
        $terms = self::publishedTerms();
        $supplier->terms_accepted = 1;
        $supplier->terms_accepted_at = now();
        $supplier->terms_accepted_terms_updated_at = $terms && $terms->updated_at
            ? $terms->updated_at
            : now();
        $supplier->save();
    }
}
