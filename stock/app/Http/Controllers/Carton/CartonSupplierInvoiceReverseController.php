<?php

namespace App\Http\Controllers\Carton;

use App\Http\Controllers\Controller;
use App\Services\Carton\ReverseCartonSupplierInvoiceService;
use App\Support\CartonReverseAuthorization;
use Illuminate\Http\Request;

class CartonSupplierInvoiceReverseController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
    }

    public function confirm(int $id, ReverseCartonSupplierInvoiceService $service)
    {
        $this->authorizeReverse();

        try {
            $preview = $service->buildPreview($id);
        } catch (\Throwable $e) {
            return redirect('/supplierInvoice/carton')->with('error', $e->getMessage());
        }

        return view('supplierInvoice.carton-reverse-confirm', $preview);
    }

    public function store(Request $request, int $id, ReverseCartonSupplierInvoiceService $service)
    {
        $this->authorizeReverse();

        $request->validate([
            'reason' => 'nullable|string|max:500',
            'confirm' => 'required|accepted',
        ]);

        try {
            $service->reverse(
                $id,
                $request->input('reason'),
                (int) auth()->id()
            );
        } catch (\Throwable $e) {
            return redirect('/supplierInvoice/carton/' . $id . '/reverse')
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return redirect('/supplierInvoice/carton')->with(
            'success',
            'Carton supplier invoice reversed successfully. You can create it again.'
        );
    }

    private function authorizeReverse(): void
    {
        if (!CartonReverseAuthorization::canReverse(auth()->user())) {
            abort(403, 'You are not allowed to reverse carton supplier invoices.');
        }
    }
}
