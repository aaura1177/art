<?php

namespace App\Http\Controllers;

use App\supplier;
use App\Support\SupplierTermsAcceptance;
use App\Support\SupplierTermsFormatter;
use App\Support\SupplierTermsHtmlSanitizer;
use App\SupplierTerm;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierTermsController extends Controller
{
    public function edit()
    {
        $terms = SupplierTerm::singleton();
        $terms->load('updatedBy');

        return view('supplier_terms.admin_edit', ['terms' => $terms]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'body' => 'nullable|string|max:400000',
        ]);

        $terms = SupplierTerm::singleton();

        $terms->title = $validated['title'] ?? '';
        $body = $validated['body'] ?? '';
        if (SupplierTermsFormatter::isStoredHtml($body)) {
            $body = SupplierTermsHtmlSanitizer::sanitize($body);
        }
        $terms->body = $body;
        $terms->updated_by_user_id = Auth::id();
        $terms->save();

        return redirect()
            ->route('admin.supplier_terms.edit')
            ->with('success', 'Supplier terms saved.');
    }

    public function adminSupplierAcceptanceStatus(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $suppliersQuery = supplier::query()
            ->select([
                'id',
                'name',
                'c_name',
                'short_name',
                'email',
                'type',
                'terms_accepted',
                'terms_accepted_at',
                'terms_accepted_terms_updated_at',
            ]);

        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $suppliersQuery->where(function ($inner) use ($like) {
                $inner
                    ->where('name', 'like', $like)
                    ->orWhere('c_name', 'like', $like)
                    ->orWhere('short_name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }

        $suppliers = $suppliersQuery
            ->orderBy('c_name')
            ->paginate(50)
            ->appends(['q' => $q]);

        return view('supplier_terms.admin_acceptance_status', [
            'suppliers' => $suppliers,
            'q' => $q,
        ]);
    }

    public function showSupplier()
    {
        $terms = SupplierTermsAcceptance::publishedTerms();
        $bodyHtml = SupplierTermsFormatter::toHtml(optional($terms)->body);

        return view('supplier_terms.show', [
            'terms' => $terms,
            'bodyHtml' => $bodyHtml,
        ]);
    }

    public function accept(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->hasRole('supplier') || ! $user->supplier_id) {
            abort(403);
        }

        if (! SupplierTermsAcceptance::hasPublishedBody()) {
            return redirect()->route('supplier.dashboard');
        }

        $request->validate([
            'accept_terms' => 'required|accepted',
        ]);

        $supplier = supplier::findOrFail($user->supplier_id);
        SupplierTermsAcceptance::markAccepted($supplier);

        return redirect()
            ->route('supplier.dashboard')
            ->with('success', 'Terms and conditions accepted. You can now use the supplier dashboard.');
    }

    public function downloadPdf(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->hasRole('supplier') || ! $user->supplier_id) {
            abort(403);
        }

        $terms = SupplierTermsAcceptance::publishedTerms();
        if (! SupplierTermsAcceptance::hasPublishedBody($terms)) {
            return redirect()->route('supplier.dashboard')->with('info', 'Terms have not been published yet.');
        }

        $bodyHtml = SupplierTermsFormatter::toHtml(optional($terms)->body);
        $title = $terms && $terms->title ? $terms->title : 'Supplier terms & conditions';

        $pdf = Pdf::loadView('supplier_terms.pdf', [
            'title' => $title,
            'bodyHtml' => $bodyHtml,
        ])->setPaper('a4', 'portrait');

        $filename = 'supplier-terms-' . date('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    public function apiForSupplier(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->supplier_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $terms = SupplierTermsAcceptance::publishedTerms();
        $supplier = supplier::find($user->supplier_id);

        $rawBody = optional($terms)->body ?? '';

        return response()->json([
            'success' => true,
            'title' => optional($terms)->title ?? '',
            'body' => SupplierTermsFormatter::toPlainTextForApi($rawBody ?: null),
            'updated_at' => $terms && $terms->updated_at ? $terms->updated_at->toIso8601String() : null,
            'must_accept' => SupplierTermsAcceptance::mustAccept((int) $user->supplier_id),
            'terms_accepted_at' => $supplier && $supplier->terms_accepted_at
                ? $supplier->terms_accepted_at->toIso8601String()
                : null,
        ]);
    }

    public function apiAcceptForSupplier(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->supplier_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (! SupplierTermsAcceptance::hasPublishedBody()) {
            return response()->json([
                'success' => true,
                'message' => 'No published terms to accept.',
                'must_accept' => false,
                'terms_accepted_at' => null,
            ]);
        }

        $request->validate([
            'accept_terms' => 'required|accepted',
        ]);

        $supplier = supplier::findOrFail($user->supplier_id);
        SupplierTermsAcceptance::markAccepted($supplier);
        $supplier->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Terms and conditions accepted.',
            'must_accept' => SupplierTermsAcceptance::mustAccept((int) $user->supplier_id),
            'terms_accepted_at' => $supplier->terms_accepted_at
                ? $supplier->terms_accepted_at->toIso8601String()
                : null,
        ]);
    }
}
