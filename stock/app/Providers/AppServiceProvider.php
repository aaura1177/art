<?php

namespace App\Providers;

use App\Support\SupplierTermsAcceptance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrap();
        Schema::defaultStringLength(191);

        View::composer('layouts.app', function ($view) {
            $gate = ['required' => false, 'title' => '', 'bodyHtml' => ''];
            $pendingPoVersions = [];
            $user = Auth::user();
            if ($user && $user->hasRole('supplier') && $user->supplier_id) {
                $gate = SupplierTermsAcceptance::gateForSupplier((int) $user->supplier_id);
                try {
                    $pendingPoVersions = \App\Support\PurchaseOrderVersionWriter::pendingVersionPopupRows(
                        (int) $user->supplier_id
                    );
                } catch (\Throwable $e) {
                    $pendingPoVersions = [];
                }
            }
            $view->with('supplierTermsGate', $gate);
            $view->with('supplierPendingPoVersions', $pendingPoVersions);
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
