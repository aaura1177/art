<!-- Sidebar -->
@if (\Request::session()->get('country') == 'india')
<ul class="sidebar navbar-nav">
    @if (Auth::user()->hasRole('supplier'))
        @php
            $supplier = App\supplier::where('id', Auth::user()->supplier_id)->first();
            $supplierType = $supplier->type ?? '';
            $doesPackaging = ($supplier->do_packaging ?? 'No') === 'Yes';
        @endphp

        <li class="nav-item active">
            <a class="nav-link" href="{{ url('/supplier-dashboard') }}">
                <i class="fas fa-fw fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="nav-item active">
            <a class="nav-link" href="{{ url('/multiple-po-instructions') }}">
                <i class="fas fa-fw fa-info-circle"></i>
                <span>Multi-PO guide</span>
            </a>
        </li>

        <li class="nav-item active">
            <a class="nav-link" href="{{ route('supplier.terms') }}">
                <i class="fas fa-fw fa-file-contract"></i>
                <span>Terms &amp; conditions</span>
            </a>
        </li>

        @if ($supplierType == 'Service')
            <li class="nav-item active">
                <a class="nav-link" href="{{ url('/supplier-dashboard/service') }}">
                    <i class="fas fa-fw fa-shopping-cart"></i>
                    <span>Purchase orders</span>
                </a>
            </li>
        @else
            <li class="nav-item active">
                <a class="nav-link" href="{{ url('/supplier-dashboard/purchase-orders') }}">
                    <i class="fas fa-fw fa-shopping-cart"></i>
                    <span>Purchase orders</span>
                </a>
            </li>
            @if (in_array($supplierType, ['Furniture', 'Both'], true))
                <li class="nav-item active">
                    <a class="nav-link" href="{{ url('/supplier-dashboard/draft-purchase-orders') }}">
                        <i class="fas fa-fw fa-file-alt"></i>
                        <span>Draft POs</span>
                    </a>
                </li>
            @endif
            @if ($supplierType != 'Furniture')
                <li class="nav-item active">
                    <a class="nav-link" href="{{ url('/supplier-dashboard/draft-consumable-purchase-orders') }}">
                        <i class="fas fa-fw fa-file-alt"></i>
                        <span>Consumable Draft POs</span>
                    </a>
                </li>
            @endif
        @endif

        <li class="nav-item active">
            <a class="nav-link" href="{{ url('/certificates') }}">
                <i class="fas fa-fw fa-file"></i>
                <span>MSME certificates</span>
            </a>
        </li>

        <li class="nav-item active">
            <a class="nav-link" href="{{ route('ProductSuppliers.assigned') }}">
                <i class="fas fa-fw fa-box"></i>
                <span>Assigned products</span>
            </a>
        </li>

        @if ($supplierType == 'Service')
            <li class="nav-item active">
                <a class="nav-link" href="{{ url('/supplier-dashboard/accepted-service-orders') }}">
                    <i class="fas fa-fw fa-check-circle"></i>
                    <span>Accepted orders</span>
                </a>
            </li>
            <li class="nav-item active">
                <a class="nav-link" href="{{ url('/supplier-dashboard/service-invoice-orders') }}">
                    <i class="fas fa-fw fa-file-invoice"></i>
                    <span>My invoices</span>
                </a>
            </li>
        @elseif ($supplierType == 'Consumable')
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="supplierRaiseConsumableDropdown" role="button"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-fw fa-plus-circle"></i>
                    <span>Raise invoice (consumable)</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="supplierRaiseConsumableDropdown">
                    <a class="dropdown-item"
                        href="{{ url('/supplier-dashboard/accepted-purchase-orders-consumable') }}">Single PO</a>
                    <a class="dropdown-item"
                        href="{{ url('/supplier-dashboard/accepted-purchase-orders-consumable-multi') }}">Multi-PO</a>
                </div>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="supplierInvoicesConsumableDropdown" role="button"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-fw fa-file-invoice"></i>
                    <span>My invoices (consumable)</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="supplierInvoicesConsumableDropdown">
                    <a class="dropdown-item"
                        href="{{ url('/supplier-dashboard/invoice-orders?kind=consumable') }}">Single PO</a>
                    <a class="dropdown-item"
                        href="{{ url('/supplier-dashboard/invoice-orders/multi-consumable') }}">Multi-PO</a>
                </div>
            </li>
            @if ($doesPackaging)
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="supplierRaiseCartonDropdown" role="button"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-fw fa-box"></i>
                        <span>Raise invoice (carton)</span>
                    </a>
                    <div class="dropdown-menu" aria-labelledby="supplierRaiseCartonDropdown">
                        <a class="dropdown-item"
                            href="{{ url('/supplier-dashboard/accepted-purchase-orders-carton') }}">Single PO</a>
                        <a class="dropdown-item"
                            href="{{ url('/supplier-dashboard/accepted-purchase-orders-carton-multi') }}">Multi-PO</a>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="supplierInvoicesCartonDropdown" role="button"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-fw fa-file-invoice"></i>
                        <span>My invoices (carton)</span>
                    </a>
                    <div class="dropdown-menu" aria-labelledby="supplierInvoicesCartonDropdown">
                        <a class="dropdown-item"
                            href="{{ url('/supplier-dashboard/invoice-orders?kind=carton') }}">Single PO</a>
                        <a class="dropdown-item"
                            href="{{ url('/supplier-dashboard/invoice-orders/multi-carton') }}">Multi-PO</a>
                    </div>
                </li>
            @endif
        @elseif ($supplierType == 'Both')
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="supplierRaiseInvoiceDropdown" role="button"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-fw fa-plus-circle"></i>
                    <span>Raise invoice</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="supplierRaiseInvoiceDropdown">
                    <h6 class="dropdown-header">Furniture</h6>
                    <a class="dropdown-item"
                        href="{{ url('/supplier-dashboard/accepted-purchase-orders') }}">Single PO</a>
                    <a class="dropdown-item"
                        href="{{ url('/supplier-dashboard/accepted-purchase-orders') }}">Multi-PO</a>
                    <h6 class="dropdown-header">Consumable / M-series</h6>
                    <a class="dropdown-item"
                        href="{{ url('/supplier-dashboard/accepted-purchase-orders-consumable') }}">Single PO</a>
                    <a class="dropdown-item"
                        href="{{ url('/supplier-dashboard/accepted-purchase-orders-consumable-multi') }}">Multi-PO</a>
                </div>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="supplierMyInvoicesDropdown" role="button"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-fw fa-file-invoice"></i>
                    <span>My invoices</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="supplierMyInvoicesDropdown">
                    <h6 class="dropdown-header">Furniture</h6>
                    <a class="dropdown-item"
                        href="{{ url('/supplier-dashboard/invoice-orders?kind=furniture') }}">Single PO</a>
                    <a class="dropdown-item"
                        href="{{ url('/supplier-dashboard/invoice-orders/multi') }}">Multi-PO</a>
                    <h6 class="dropdown-header">Consumable / M-series</h6>
                    <a class="dropdown-item"
                        href="{{ url('/supplier-dashboard/invoice-orders?kind=consumable') }}">Single PO</a>
                    <a class="dropdown-item"
                        href="{{ url('/supplier-dashboard/invoice-orders/multi-consumable') }}">Multi-PO</a>
                </div>
            </li>
            @if ($doesPackaging)
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="supplierRaiseCartonDropdown" role="button"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-fw fa-box"></i>
                        <span>Raise invoice (carton)</span>
                    </a>
                    <div class="dropdown-menu" aria-labelledby="supplierRaiseCartonDropdown">
                        <a class="dropdown-item"
                            href="{{ url('/supplier-dashboard/accepted-purchase-orders-carton') }}">Single PO</a>
                        <a class="dropdown-item"
                            href="{{ url('/supplier-dashboard/accepted-purchase-orders-carton-multi') }}">Multi-PO</a>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="supplierInvoicesCartonDropdown" role="button"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-fw fa-file-invoice"></i>
                        <span>My invoices (carton)</span>
                    </a>
                    <div class="dropdown-menu" aria-labelledby="supplierInvoicesCartonDropdown">
                        <a class="dropdown-item"
                            href="{{ url('/supplier-dashboard/invoice-orders?kind=carton') }}">Single PO</a>
                        <a class="dropdown-item"
                            href="{{ url('/supplier-dashboard/invoice-orders/multi-carton') }}">Multi-PO</a>
                    </div>
                </li>
            @endif
        @else
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="supplierRaiseFurnitureDropdown" role="button"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-fw fa-plus-circle"></i>
                    <span>Raise invoice</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="supplierRaiseFurnitureDropdown">
                    <a class="dropdown-item"
                        href="{{ url('/supplier-dashboard/accepted-purchase-orders') }}">Single PO</a>
                    <a class="dropdown-item"
                        href="{{ url('/supplier-dashboard/accepted-purchase-orders') }}">Multi-PO</a>
                </div>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="supplierInvoicesFurnitureDropdown" role="button"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-fw fa-file-invoice"></i>
                    <span>My invoices</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="supplierInvoicesFurnitureDropdown">
                    <a class="dropdown-item"
                        href="{{ url('/supplier-dashboard/invoice-orders?kind=furniture') }}">Single PO</a>
                    <a class="dropdown-item"
                        href="{{ url('/supplier-dashboard/invoice-orders/multi') }}">Multi-PO</a>
                </div>
            </li>
        @endif

        <li class="nav-item active">
            <a class="nav-link" href="{{ url('/supplier-dashboard/returned-purchase-orders') }}">
                <i class="fas fa-fw fa-undo"></i>
                <span>Returned orders</span>
            </a>
        </li>
        <!-- Sustainability Module Routes For Supplier -->
        <li class="nav-item active">
            <a class="nav-link" href="{{ route('transport.logs.index') }}">
                <i class="fas fa-fw fa-bus"></i>
                <span>Transport Logs</span>
            </a>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="notificationDropdown" role="button"
                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fa fa-users"></i>
                <span>Production Emission</span>
            </a>
            <div class="dropdown-menu" aria-labelledby="pagesDropdown">
                <a class="dropdown-item" href="{{ route('production.logs.index') }}">Electricity Consumption</a>
            </div>
        </li>
        <!-- Sustainability Module Routes For Supplier Ends here -->
    @else
        <li class="nav-item active">
            <a class="nav-link" href="{{ url('/dashboard') }}">
                <i class="fas fa-fw fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>


        @hasrole(['Admin', 'admin', 'Administrative', 'administrative'])
            <li class="nav-item">
                <a class="nav-link" href="{{ route('role-management.index') }}">
                    <i class="fas fa-fw fa-users"></i>
                    <span>Role Management</span>
                </a>
            </li>
        @endhasrole


        <!-- Products -->
        @can('product-read')
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="productDropdown" role="button"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-fw fa-cube"></i>
                    <span>{{ __('Products') }}</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="pagesDropdown">
                    <h6 class="dropdown-header">Products:</h6>
                    @can('product-edit')
                        <a class="dropdown-item" href="{{ url('/product/create') }}">Add</a>
                    @endcan
                    <a class="dropdown-item" href="{{ url('/product') }}">Furniture</a>
                    <a class="dropdown-item" href="{{ url('/consumables') }}">Consumables</a>
                    <a class="dropdown-item" href="{{ url('/unitType') }}">Consumable Unit</a>
                    <a class="dropdown-item" href="{{ url('/product/valuation') }}">Valuation</a>
                    <a class="dropdown-item" href="{{ url('/consumable/valuation') }}">Consumable Valuation</a>
                    <a class="dropdown-item" href="{{ url('/product/product_age') }}">Age</a>
                    <a class="dropdown-item" href="{{ url('/product/legs') }}">Legs</a>
                    <a class="dropdown-item" href="{{ url('/samples') }}">Samples</a>
                    <a class="dropdown-item" href="{{ url('/finishing_rates') }}">Finishing Rate</a>
                    <a class="dropdown-item" href="{{ url('/FinshingExtraPrice') }}">Finshing Extra Price</a>
                    <a class="dropdown-item" href="{{ route('ProductSuppliers.index') }}">Supplier Products</a>
                    @can('quality-read')
                        <a class="dropdown-item" href="{{ url('/quality') }}">Quality</a>
                    @endcan
                    @can('sample-edit')
                    @endcan
                    @can('category-read')
                        <div class="dropdown-divider"></div>
                        <h6 class="dropdown-header">Categories:</h6>
                        @can('category-edit')
                            <a class="dropdown-item" href="{{ url('/category/create') }}">Add</a>
                        @endcan
                        <a class="dropdown-item" href="{{ url('/category') }}">View</a>
                        <div class="dropdown-divider"></div>
                        <h6 class="dropdown-header">Sub-Categories:</h6>
                        @can('category-edit')
                            <a class="dropdown-item" href="{{ url('/category/subCategory/create') }}">Add</a>
                        @endcan
                        <a class="dropdown-item" href="{{ url('/category/subCategory') }}">View</a>
                        <h6 class="dropdown-header">Product Ledger:</h6>
                        @can('category-edit')
                            <a class="dropdown-item" href="{{ url('/product_ledger/create   ') }}">Add</a>
                        @endcan
                        <a class="dropdown-item" href="{{ url('/product_ledger') }}">View</a>
                    @endcan
                </div>
            </li>
        @endcan


        @can('product-read')
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="productDropdown" role="button"
                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-fw fa-cube"></i>
                <span>{{ __('Service ') }}</span>
            </a>
            <div class="dropdown-menu" aria-labelledby="pagesDropdown">
                <h6 class="dropdown-header">Services:</h6>
                @can('product-edit')
                    <a class="dropdown-item" href="{{ url('/service/create/product') }}">Add Product</a>
                @endcan

                <a class="dropdown-item" href="{{ url('/service/product/view') }}">View Product</a>
                <a class="dropdown-item" href="{{ url('/purchaseOrder/create/service') }}">Add Service Po </a>
                <a class="dropdown-item" href="{{ url('/purchaseOrder/service') }}">View Service Po</a>
                <a class="dropdown-item" href="{{ url('/supplier-service-Invoice') }}">Supplier  Invoice</a>
                <a class="dropdown-item" href="{{ url('/purchaseBill/service/condition') }}">Purchase Bills</a>
                <a class="dropdown-item" href="{{ url('/purchaseBill/service') }}">Service Inward Supply
                </a>
                <a class="dropdown-item" href="{{ url('/service/categories') }}">Service Category</a>
                <a class="dropdown-item" href="{{ url('/service/categoriesindex') }}">View Service Category</a>


            </div>
        </li>
        @endcan

        @hasrole('office')
            <li class="nav-item">
                <a class="nav-link" href="{{ url('/quality') }}">
                    <i class="fas fa-fw fa-list"></i>
                    <span>Quality</span></a>
            </li>
        @endhasrole
        <li class="nav-item">
            <a class="nav-link" href="{{ url('/notifications/index') }}">
                <i class="fas fa-fw fa-list"></i><span>User Log Book</span></a>
        </li>
        @can('product-read')
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="reportsDropdown" role="button"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-fw fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="reportsDropdown">
                    <a class="dropdown-item" href="{{ url('/product/inventory_report') }}">Inventory Report (Old)</a>
                    <a class="dropdown-item" href="{{ route('products.inventory') }}">Product Inventory</a>
                    <a class="dropdown-item" href="{{ route('consumable.inventory') }}">Consumable Inventory</a>
                    <a class="dropdown-item" href="{{ route('carton.inventory') }}">Carton Inventory</a>
                    <a class="dropdown-item" href="{{ route('reports.advanced.index') }}">Advanced Reports</a>
                </div>
            </li>

            @php
                $manualStockAllowedEmails = [
                    'aaura1177@gmail.com',
                    'info@globalvisioncompany.com',
                    'finance@artisanfurniture.net',
                    'factory@globalvisioncompany.com',
                ];
                $canSeeBackmonthStock = in_array(strtolower(auth()->user()->email ?? ''), $manualStockAllowedEmails, true);
            @endphp
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="manualStockDropdown" role="button"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-fw fa-boxes"></i>
                    <span>Manual Stock</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="manualStockDropdown">
                    <a class="dropdown-item" href="{{ route('consumable.manual_invoice_count') }}">Manual Consumable Invoice Count</a>
                    <a class="dropdown-item" href="{{ route('carton.manual_invoice_count') }}">Manual Carton Invoice Count</a>
                    @if($canSeeBackmonthStock)
                        <a class="dropdown-item" href="{{ route('manual_stock.backmonth.index') }}">Backmonth Stock</a>
                    @endif
                </div>
            </li>
        @endcan




        <!-- Supplier -->
        @can('supplier-read')
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="productDropdown" role="button"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-fw fa-truck"></i>
                    <span>{{ __('Supplier') }}</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="pagesDropdown">
                    @can('supplier-edit')
                        <a class="dropdown-item" href="{{ url('/supplier/create') }}">Add</a>
                    @endcan
                    <a class="dropdown-item" href="{{ url('/supplier') }}">View</a>
                    <a class="dropdown-item" href="{{ url('/supplier/pricing') }}">Pricing</a>
                    <a class="dropdown-item" href="{{ url('/supplier/pricing/history') }}">Price history</a>
                     <a class="dropdown-item" href="{{ route('admin.supplier_terms.view') }}">Supplier terms (preview)</a>
                     <a class="dropdown-item" href="{{ route('admin.supplier_terms.acceptance_status') }}">Supplier terms acceptance status</a>
                    @can('supplier-edit')
                        <a class="dropdown-item" href="{{ route('admin.supplier_terms.edit') }}">Supplier terms &amp; conditions (edit)</a>
                    @endcan
                </div>
            </li>
        @endcan

        <!-- Buyers -->
        @can('buyer-read')
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="productDropdown" role="button"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-fw fa-handshake"></i>
                    <span>{{ __('Buyer') }}</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="pagesDropdown">
                    @can('buyer-edit')
                        <a class="dropdown-item" href="{{ url('/buyer/create') }}">Add</a>
                    @endcan
                    <a class="dropdown-item" href="{{ url('/buyer') }}">View</a>
                </div>
            </li>
        @endcan

        <!-- Contractors -->
        @can('contractor-read')
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="productDropdown" role="button"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-fw fa-suitcase"></i>
                    <span>{{ __('Contractor') }}</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="pagesDropdown">
                    @can('contractor-read')
                        <a class="dropdown-item" href="{{ url('/contractor/create') }}">Add</a>
                    @endcan
                    <a class="dropdown-item" href="{{ url('/contractor') }}">View</a>
                    <a class="dropdown-item" href="{{ url('/performanceCards') }}">Performance Card</a>
                </div>
            </li>
        @endcan

        <!-- Allocations -->
        @can('allocation-read')
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="productDropdown" role="button"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-fw fa-sitemap"></i>
                    <span>{{ __('Allocations') }}</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="pagesDropdown">
                    @can('allocation-edit')
                        <a class="dropdown-item" href="{{ url('/allocation/create') }}">Add</a>
                    @endcan
                    <a class="dropdown-item" href="{{ route('allocation-management.index') }}">View</a>
                </div>
            </li>
        @endcan

        <!-- Wholesale PO Management -->
        @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('administrator') || auth()->user()->can('wholesale-po-read'))
            <li class="nav-item dropdown {{ request()->routeIs('wholesale-po.*') ? 'show' : '' }}">
                <a class="nav-link dropdown-toggle {{ request()->routeIs('wholesale-po.*') ? 'active' : '' }}" href="#" id="wholesalePoMainDropdown"
                    role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-fw fa-file-excel"></i>
                    <span>Wholesale PO</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="wholesalePoMainDropdown">
                    <a class="dropdown-item" href="{{ route('wholesale-po.index') }}">View</a>
                    @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('administrator') || auth()->user()->can('wholesale-po-edit'))
                        <a class="dropdown-item" href="{{ route('wholesale-po.create') }}">Upload / Create</a>
                    @endif
                </div>
            </li>
        @endif


        <!-- Reject & Repair -->
        @can('rejectrepair-read')
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="productDropdown" role="button"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-fw fa-times-circle"></i>
                    <span>{{ __('Reject & Repair') }}</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="pagesDropdown">
                    @can('rejectrepair-edit')
                        <a class="dropdown-item" href="{{ url('/rejectRepair/create') }}">Add</a>
                    @endcan
                    <a class="dropdown-item" href="{{ url('/rejectRepair') }}">View</a>
                </div>
            </li>
        @endcan

        <!-- Purchase Orders -->
        @can('po-read')
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="poDropdown" role="button"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-fw fa-shopping-cart"></i>
                    <span>{{ __('Purchase Order') }}</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="poDropdown">
                    <h6 class="dropdown-header">Furniture</h6>
                    @can('po-edit')
                        <a class="dropdown-item" href="{{ url('/purchaseOrder/create') }}">Add WF PO</a>
                        <a class="dropdown-item" href="{{ url('/draftPurchaseOrder/create') }}">Add Draft PO</a>
                    @endcan
                    <a class="dropdown-item" href="{{ url('/purchaseOrder') }}">View WF POs</a>
                    <a class="dropdown-item" href="{{ url('/draftPurchaseOrder') }}">View Draft POs</a>

                    <div class="dropdown-divider"></div>
                    <h6 class="dropdown-header">Consumables</h6>
                    @can('po-edit')
                        <a class="dropdown-item" href="{{ url('/purchaseOrder/createConsumablePo') }}">Add Consumable PO</a>
                        <a class="dropdown-item" href="{{ url('/draftConsumablePurchaseOrder/create') }}">Add Consumable Draft PO</a>
                    @endcan
                    <a class="dropdown-item" href="{{ url('/purchaseOrder/consumablePos') }}">View Consumable POs</a>
                    <a class="dropdown-item" href="{{ url('/draftConsumablePurchaseOrder') }}">View Consumable Draft POs</a>
                    <a class="dropdown-item" href="{{ url('/monthendpo/consumable') }}">View Consumable MonthEnd POs</a>

                    <div class="dropdown-divider"></div>
                    <h6 class="dropdown-header">Carton</h6>
                    @can('po-edit')
                        <a class="dropdown-item" href="{{ url('/purchaseOrder/createCartonPo') }}">Add Carton PO</a>
                    @endcan
                    <a class="dropdown-item" href="{{ url('/purchaseOrder/cartonPos') }}">View Carton POs</a>

                    <div class="dropdown-divider"></div>
                    <h6 class="dropdown-header">Sample</h6>
                    @can('po-edit')
                        <a class="dropdown-item" href="{{ url('/purchaseOrder/createSamplePo') }}">Add Sample PO</a>
                    @endcan
                    <a class="dropdown-item" href="{{ url('/samplePurchaseOrder') }}">View Sample POs</a>

                    <div class="dropdown-divider"></div>
                    <h6 class="dropdown-header">Other</h6>
                    <a class="dropdown-item" href="{{ url('/purchaseOrder/recommendedPurchaseOrder') }}">View Recommended POs</a>
                </div>
            </li>
        @endcan

        @can('invoice-read')
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="supplierInvoiceDropdown" role="button"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-fw fa-cog"></i><span>Supplier Invoices</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="supplierInvoiceDropdown">
                    <a class="dropdown-item" href="{{ url('/supplierInvoice') }}">Supplier Invoices</a>
                    <a class="dropdown-item" href="{{ url('/supplierInvoice/carton') }}">Supplier Invoices Carton</a>
                    <a class="dropdown-item" href="{{ url('/supplierInvoice/multi') }}">Multi Supplier Invoices</a>
                    <a class="dropdown-item" href="{{ url('/supplierInvoice/multi-consumable') }}">Multi Invoices (Consumable)</a>
                    <a class="dropdown-item" href="{{ url('/supplierInvoice/multi-carton') }}">Multi Invoices (Carton)</a>
                    <a class="dropdown-item" href="{{ url('/challan') }}">Challan</a>
                </div>
            </li>
        @endcan
        @can('purchase-bill-list')
            @if (auth()->user()->id == '25' || auth()->user()->role == 'Admin' || auth()->user()->role == 'Administrative' || auth()->user()->role == 'administrative' || auth()->user()->role == 'admin' || auth()->user()->role == 'auditor' || auth()->user()->role == 'compliance')
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="purchaseBillsDropdown" role="button"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-fw fa-list"></i><span>Purchase Bills</span>
                    </a>
                    <div class="dropdown-menu" aria-labelledby="purchaseBillsDropdown">
                        <a class="dropdown-item" href="{{ url('/purchaseBill/condition') }}">Purchase Bills</a>
                        <a class="dropdown-item" href="{{ url('/purchaseBill/condition/Multi') }}">Purchase Bills Multi</a>
                        <a class="dropdown-item" href="{{ url('/purchaseBill/condition/consumables') }}">Purchase Bills Consumables</a>
                        <a class="dropdown-item" href="{{ url('/purchaseBill/condition/carton') }}">Purchase Bills Carton</a>
                    </div>
                </li>
            @endif
        @endcan


        <!-- Purchase Bills -->
        @can('pb-read')
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="productDropdown" role="button"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-fw fa-shopping-cart"></i><span>{{ __('Inward Supply') }}</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="pagesDropdown">
                    @can('pb-edit')
                        <a class="dropdown-item" href="{{ url('/purchaseBill/create') }}">Add</a>
                        <a class="dropdown-item" href="{{ url('/purchaseBill/admin/consumable/create') }}">Add — consumables (admin)</a>
                        <a class="dropdown-item" href="{{ url('/purchaseBill/admin/carton/create') }}">Add — carton (admin)</a>
                    @endcan
                    <a class="dropdown-item" href="{{ url('/purchaseBill') }}">View</a>
                    <a class="dropdown-item" href="{{ url('/purchaseBill/verified') }}">Verified</a>
                    <a class="dropdown-item" href="{{ url('/purchaseBill/samples') }}">Samples</a>
                </div>
            </li>
        @endcan

        <!-- Invoices -->
        @can('invoice-read')
            <li class="nav-item dropdown">

                <a class="nav-link dropdown-toggle" href="#" id="productDropdown" role="button"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-fw fa-list"></i>
                    <span>{{ __('Invoices') }}</span>
                </a>

                <div class="dropdown-menu" aria-labelledby="pagesDropdown">
                    @can('invoice-edit')
                        <a class="dropdown-item" href="{{ url('/invoice/create') }}">Add</a>
                    @endcan
                    @can('invoice-read')
                        <a class="dropdown-item" href="{{ url('/invoice') }}">View</a>
                        <a class="dropdown-item" href="{{ url('/invoice/swapping') }}">Swapping</a>
                        <a class="dropdown-item" href="{{ url('/carton-swapping') }}">Carton Swapping</a>
                    @endcan
                    @can('packing')
                        <a class="dropdown-item" href="{{ url('/invoice/packing_list') }}">Packing List</a>
                    @endcan
                    @can('invoice-read')
                        <a class="dropdown-item" href="{{ url('/invoice/credit_note_add') }}">Add Credit Note</a>
                        <a class="dropdown-item" href="{{ url('/invoice/credit_notes') }}">Credit Notes</a>
                    @endcan
                </div>
            </li>
        @endcan

        @can('packing')
            <li class="nav-item">
                <a class="nav-link" href="{{ url('/invoice/packing_list') }}">
                    <i class="fas fa-fw fa-list"></i>
                    <span>Packing List</span></a>
            </li>
        @endcan

        <!-- StockOut -->
        @can('stockout-read')
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="productDropdown" role="button"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-fw fa-folder"></i>
                    <span>{{ __('StockOut') }}</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="pagesDropdown">
                    @can('stockout-edit')
                        <a class="dropdown-item" href="{{ url('/stockout/create') }}">Add</a>
                    @endcan
                    <a class="dropdown-item" href="{{ url('/stockout') }}">View</a>
                </div>
            </li>
        @endcan

        <!-- Reports -->
        @can('report')
            <li class="nav-item dropdown">
                <a class="nav-link" href="{{ url('/report/shutout') }}">
                    <i class="fas fa-fw fa-file"></i>
                    <span>{{ __('ShutOut Report') }}</span>
                </a>
                <!-- <div class="dropdown-menu" aria-labelledby="pagesDropdown">
                                        <a class="dropdown-item" href="{{ url('/report/shutout') }}">ShutOut</a>
                                        </div>  -->
            </li>
        @endcan

        @can('pricing-read')
            <!-- Pricing -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="productDropdown" role="button"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-fw fa-folder"></i>
                    <span>{{ __('Pricing') }}</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="pagesDropdown">
                    <h6 class="dropdown-item">Pricing</h6>
                    @can('pricing-edit')
                        <a class="dropdown-item" href="{{ url('/pricing/create') }}">Add</a>
                    @endcan
                    <a class="dropdown-item" href="{{ url('/pricing') }}">View</a>
                  <a class="dropdown-item" href="{{ url('/pricing/Excel/sheet') }}">Excel Sheet</a>

                    <a class="dropdown-item" href="{{ url('/buyer/create-bulk-pricing') }}">Create Bulk Pricing</a>
                    <a class="dropdown-item" href="{{ url('/buyer/create-bulk-all-cost-input') }}">Create Bulk All Cost Input</a>
                    @can('pricing-edit')
                        <div class="dropdown-divider"></div>
                        <h6 class="dropdown-item">Temporary Product:</h6>
                        <a class="dropdown-item" href="{{ url('/temporaryProduct/create') }}">Add</a>
                        <a class="dropdown-item" href="{{ url('/temporaryProduct') }}">View</a>
                        <div class="dropdown-divider"></div>
                        <h6 class="dropdown-item">Temporary Buyer</h6>
                        <a class="dropdown-item" href="{{ url('/temporaryBuyer/create') }}">Add</a>
                        <a class="dropdown-item" href="{{ url('/temporaryBuyer') }}">View</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{ url('/apCalculator/apcalculate') }}">Avg. Profit Calculator</a>
                        <a class="dropdown-item" href="{{ url('/pricing/related_product_calculator') }}">Related Product
                            Calculator</a>
                    @endcan
            </li>
        @endcan

        <!-- Courier -->
        @can('courier')
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="productDropdown" role="button"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-fw fa-shopping-cart"></i>
                    <span>{{ __('Courier') }}</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="pagesDropdown">
                    <a class="dropdown-item" href="{{ url('/courier/create') }}">Add</a>
                    <a class="dropdown-item" href="{{ url('/courier') }}">View</a>
                </div>
            </li>
        @endcan

        <!-- Hardware -->
        @can('hardwares')
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="productDropdown" role="button"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-fw fa-shopping-cart"></i>
                    <span>{{ __('Hardwares') }}</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="pagesDropdown">
                    <a class="dropdown-item" href="{{ url('/hardwares/create') }}">Add</a>
                    <a class="dropdown-item" href="{{ url('/hardwares') }}">View</a>
                    {{-- MonthEnd Consumable Onboarding hidden; month-end via consumable monthEndpo_supplier --}}
                </div>
            </li>
        @endcan
        @can('hardwares')
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="productDropdown" role="button"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-fw fa-shopping-cart"></i>
                    <span>{{ __('Small Hardwares') }}</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="pagesDropdown">
                    <a class="dropdown-item" href="{{ url('/smallhardwares/create') }}">Add</a>
                    <a class="dropdown-item" href="{{ url('/smallhardwares') }}">View</a>
                </div>
            </li>
        @endcan

        @can('smallhardware')
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="productDropdown" role="button"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-fw fa-shopping-cart"></i>
                    <span>{{ __('Dust Cover & Pouch') }}</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="pagesDropdown">
                    <a class="dropdown-item" href="{{ url('/smallhardware/1') }}">{{ __('View') }}</a>
                    <a class="dropdown-item" href="{{ url('/smhsupplier') }}">{{ __('Suppliers') }}</a>
                </div>
            </li>
        @endcan

        <!-- Shipping lines -->
        @can('shipping-lines')
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="productDropdown" role="button"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-fw fa-shopping-cart"></i>
                    <span>{{ __('Shipping') }}</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="pagesDropdown">
                    <a class="dropdown-item" href="{{ url('/shipping') }}">View</a>
                    <a class="dropdown-item" href="{{ url('/shipping/create') }}">Add</a>
                    <a class="dropdown-item" href="{{ url('/shippingLines') }}">Shipping Lines</a>
                </div>
            </li>
        @endcan
        <!-- Packaging -->
        @can('packaging')
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="productDropdown" role="button"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-fw fa-shopping-cart"></i>
                    <span>{{ __('Packaging') }}</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="pagesDropdown">
                    <a class="dropdown-item" href="{{ url('/packaging') }}">View</a>
                </div>
            </li>
        @endcan

        <!-- Corner and L bill -->
        @can('cornerpackaging')
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="productDropdown" role="button"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-fw fa-shopping-cart"></i>
                    <span>{{ __('Corner and L') }}</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="pagesDropdown">
                    <a class="dropdown-item" href="{{ url('/cornerpackaging/create') }}">Add</a>
                    <a class="dropdown-item" href="{{ url('/cornerpackaging') }}">View</a>
                </div>
            </li>
        @endcan

        <!-- Create User -->
        @can('settings-edit')
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="createuserDropdown" role="button"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-users"></i>
                    <span>User</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="pagesDropdown">
                    <a class="dropdown-item" href="{{ url('/setting/createuser') }}">Add</a>
                    <a class="dropdown-item" href="{{ url('/setting/viewuser') }}">View</a>
                </div>
            </li>
        @endcan

        @can('settings-edit')
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="notificationDropdown" role="button"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-users"></i>
                    <span>Notifications</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="pagesDropdown">
                    <a class="dropdown-item" href="{{ url('/notifications/create') }}">Add</a>
                    <a class="dropdown-item" href="">View</a>
                </div>
            </li>
        @endcan

        @hasrole(['Admin', 'admin', 'Administrative', 'administrative'])
            @php
                $sustainabilityRoutes = [
                    'sustainability.stage0.index',
                    'sustainability.stage1.index',
                    'sustainability.stage2.index',
                    'sustainability.stage3.power-consumption',
                    'sustainability.stage3.miscellaneous.index',
                    'sustainability.stage3.employee.index',
                    'sustainability.stage4.index',
                    'sustainability.stage5.index',
                    'sustainability.stage6.index',
                    'sustainability.summary.index',
                    'sustainability.variable.index',
                    'sustainability.port.index',
                ];
                $isSustainabilityActive = request()->routeIs($sustainabilityRoutes);

            @endphp

            <li class="nav-item dropdown {{ $isSustainabilityActive ? 'show' : '' }}">
                <a class="nav-link dropdown-toggle {{ $isSustainabilityActive ? 'active' : '' }}" href="#" id="sustainabilityDropdown"
                role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="{{ $isSustainabilityActive ? 'true' : 'false' }}">
                    <i class="fa fa-users"></i>
                    <span>Sustainability</span>
                </a>
                <div class="dropdown-menu {{ $isSustainabilityActive ? 'show' : '' }}" aria-labelledby="sustainabilityDropdown">
                    <a class="dropdown-item" href="{{ route('sustainability.variable.index') }}">Default Values</a>
                    <a class="dropdown-item" href="{{ route('sustainability.port.index') }}">Ports</a>
                    <a class="dropdown-item" href="{{ route('sustainability.stage0.index') }}">Stage 0</a>
                    <a class="dropdown-item" href="{{ route('sustainability.stage1.index') }}">Stage 1</a>
                    <a class="dropdown-item" href="{{ route('sustainability.stage2.index') }}">Stage 2</a>
                    <a class="dropdown-item" href="{{ route('sustainability.stage3.power-consumption') }}">Stage 3</a>
                    <!-- <a class="dropdown-item" href="{{ route('sustainability.stage3.miscellaneous.index') }}">Stage 3 - Miscellaneous</a>
                    <a class="dropdown-item" href="{{ route('sustainability.stage3.employee.index') }}">Stage 3 - Employee</a> -->
                    <a class="dropdown-item" href="{{ route('sustainability.stage4.index') }}">Stage 4</a>
                    <a class="dropdown-item" href="{{ route('sustainability.stage5.index') }}">Stage 5</a>
                    <a class="dropdown-item" href="{{ route('sustainability.stage6.index') }}">Stage 6</a>
                    <a class="dropdown-item" href="{{ route('sustainability.summary.index') }}">Summary</a>
                </div>
            </li>
        @endhasrole
        <!-- Settings -->
        @can('settings-edit')
            <li class="nav-item">
                <a class="nav-link" href="{{ url('/setting') }}">
                    <i class="fas fa-fw fa-cog"></i>
                    <span>Settings</span></a>
            </li>
        @endcan


        @php $isAllocationModule = request()->routeIs('allocation-management.*');  @endphp

        @if(auth()->user()->hasRole('admin') || auth()->user()->can('container-allocation-read' || auth()->user()->role == 'Administrative' || auth()->user()->role == 'administrative' || auth()->user()->role == 'Admin'))
            <li class="nav-item dropdown {{ $isAllocationModule ? 'show' : '' }}">
                <a class="nav-link dropdown-toggle {{ $isAllocationModule ? 'active' : '' }}" href="#" id="containerAllocationDropdown"
                role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="{{ $isAllocationModule ? 'true' : 'false' }}">
                    <i class="fa fa-users"></i>
                    <span>Container Allocation</span>
                </a>
                <div class="dropdown-menu {{ $isAllocationModule ? 'show' : '' }}" aria-labelledby="containerAllocationDropdown">
                    <a class="dropdown-item" href="{{ route('allocation-management.index') }}">View</a>
                    <a class="dropdown-item" href="{{ route('allocation-management.history') }}">History</a>
                </div>
            </li>
        @endif

        @php $isWholesalePoModule = request()->routeIs('wholesale-po.*'); @endphp
        @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('administrator') || auth()->user()->can('wholesale-po-read'))
            <li class="nav-item dropdown {{ $isWholesalePoModule ? 'show' : '' }}">
                <a class="nav-link dropdown-toggle {{ $isWholesalePoModule ? 'active' : '' }}" href="#" id="wholesalePoDropdown"
                   role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="{{ $isWholesalePoModule ? 'true' : 'false' }}">
                    <i class="fas fa-fw fa-file-excel"></i>
                    <span>Wholesale PO</span>
                </a>
                <div class="dropdown-menu {{ $isWholesalePoModule ? 'show' : '' }}" aria-labelledby="wholesalePoDropdown">
                    <a class="dropdown-item" href="{{ route('wholesale-po.index') }}">View</a>
                    @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('administrator') || auth()->user()->can('wholesale-po-edit'))
                        <a class="dropdown-item" href="{{ route('wholesale-po.create') }}">Upload / Create</a>
                    @endif
                </div>
            </li>
        @endif
    @endif
</ul>
@elseif(\Request::session()->get('country') == 'uk')
    <ul class="sidebar navbar-nav">
        @if (!auth()->user()->hasRole('ukmanager'))
            <li class="nav-item active">
                <a class="nav-link" href="{{ url('/invoices/invoice-uk') }}">
                    <i class="fas fa-fw fa-list"></i>
                    <span>Invoices</span>
                </a>
            </li>
        @endif
        <li class="nav-item active">
            <a class="nav-link" href="{{ url('/product/erp') }}">
                <i class="fas fa-fw fa-list"></i>
                <span>ERP</span>
            </a>
        </li>

        <li class="nav-item active">
            <a class="nav-link" href="{{ url('/fulfillment/list') }}">
                <i class="fas fa-fw fa-list"></i>
                <span>FulFillment</span>
            </a>
        </li>
        @hasrole(['Admin', 'admin', 'Administrative', 'administrative'])
            <li class="nav-item active">
                <a class="nav-link" href="{{ url('/buyer_uk') }}">
                    <i class="fas fa-fw fa-list"></i>
                    <span>Buyers</span>
                </a>
            </li>
            <li class="nav-item active">
                <a class="nav-link" href="{{ url('/settings_uk') }}">
                    <i class="fas fa-fw fa-list"></i>
                    <span>Settings</span>
                </a>
            </li>
        @endhasrole
    </ul>
@elseif(\Request::session()->get('country') == 'us')
    <ul class="sidebar navbar-nav">
        @if (!auth()->user()->hasRole('usmanager'))
            <li class="nav-item active">
                <a class="nav-link" href="{{ url('/invoices/invoice-us') }}">
                    <i class="fas fa-fw fa-list"></i>
                    <span>Invoices</span>
                </a>
            </li>
        @endif
        <li class="nav-item active">
            {{-- <a class="nav-link" href="{{ url('/product/erp-us') }}"> --}}
            <a class="nav-link" href="{{ url('/product/erp') }}">
                <i class="fas fa-fw fa-list"></i>
                <span>ERP</span>
            </a>
        </li>

        <li class="nav-item active">
            <a class="nav-link" href="{{ url('/fulfillment/list') }}">
                <i class="fas fa-fw fa-list"></i>
                <span>FulFillment</span>
            </a>
        </li>
        @hasrole(['Admin', 'admin', 'Administrative', 'administrative'])
            <li class="nav-item active">
                <a class="nav-link" href="{{ url('/buyer_us') }}">
                    <i class="fas fa-fw fa-list"></i>
                    <span>Buyers</span>
                </a>
            </li>
            <li class="nav-item active">
                <a class="nav-link" href="{{ url('/settings_us') }}">
                    <i class="fas fa-fw fa-list"></i>
                    <span>Settings</span>
                </a>
            </li>
        @endhasrole
    </ul>
@elseif(\Request::session()->get('country') == 'eu')
    <ul class="sidebar navbar-nav">
        @if (!auth()->user()->hasRole('eumanager'))
            <li class="nav-item active">
                <a class="nav-link" href="{{ url('/invoices/invoice-eu') }}">
                    <i class="fas fa-fw fa-list"></i>
                    <span>Invoices</span>
                </a>
            </li>
        @endif
        <li class="nav-item active">
            <a class="nav-link" href="{{ url('/product/erp') }}">
                {{-- <a class="nav-link" href="{{ url('/product/erp-eu') }}"> --}}
                <i class="fas fa-fw fa-list"></i>
                <span>ERP</span>
            </a>
        </li>
        <li class="nav-item active">
            <a class="nav-link" href="{{ url('/fulfillment/list') }}">
                <i class="fas fa-fw fa-list"></i>
                <span>FulFillment</span>
            </a>
        </li>
        @hasrole(['Admin', 'admin', 'Administrative', 'administrative'])
            <li class="nav-item active">
                <a class="nav-link" href="{{ url('/buyer_eu') }}">
                    <i class="fas fa-fw fa-list"></i>
                    <span>Buyers</span>
                </a>
            </li>
            <li class="nav-item active">
                <a class="nav-link" href="{{ url('/settings_eu') }}">
                    <i class="fas fa-fw fa-list"></i>
                    <span>Settings</span>
                </a>
            </li>
        @endhasrole
    </ul>
@elseif(\Request::session()->get('country') == 'canada')
<ul class="sidebar navbar-nav">


    @if (!auth()->user()->hasRole('canadamanager'))
        <li class="nav-item active">
            <a class="nav-link" href="{{ url('/invoices/invoice-canada') }}">
                <i class="fas fa-fw fa-list"></i>
                <span>Invoices</span>
            </a>
        </li>
    @endif
    <li class="nav-item active">
        <a class="nav-link" href="{{ url('/product/erp') }}">
            {{-- <a class="nav-link" href="{{ url('/product/erp-eu') }}"> --}}
            <i class="fas fa-fw fa-list"></i>
            <span>ERP</span>
        </a>
    </li>
    <li class="nav-item active">
        <a class="nav-link" href="{{ url('/fulfillment/list') }}">
            <i class="fas fa-fw fa-list"></i>
            <span>FulFillment</span>
        </a>
    </li>
    @hasrole(['Admin', 'admin', 'Administrative', 'administrative'])
        <li class="nav-item active">
            <a class="nav-link" href="{{ url('/buyer_canada') }}">
                <i class="fas fa-fw fa-handshake"></i>
                <span>Buyers</span>
            </a>
        </li>
        <li class="nav-item active">
            <a class="nav-link" href="{{ url('/settings_canada') }}">
                <i class="fas fa-fw fa-cog"></i>
                <span>Settings</span>
            </a>
        </li>
    @endhasrole
</ul>

@elseif(\Request::session()->get('country') == 'california')
    <ul class="sidebar navbar-nav">


        @if (!auth()->user()->hasRole('californiamanager'))
            <li class="nav-item active">
                <a class="nav-link" href="{{ url('/invoices/invoice-california') }}">
                    <i class="fas fa-fw fa-list"></i>
                    <span>Invoices</span>
                </a>
            </li>
        @endif
        <li class="nav-item active">
            <a class="nav-link" href="{{ url('/product/erp') }}">
                {{-- <a class="nav-link" href="{{ url('/product/erp-eu') }}"> --}}
                <i class="fas fa-fw fa-list"></i>
                <span>ERP</span>
            </a>
        </li>
        <li class="nav-item active">
            <a class="nav-link" href="{{ url('/fulfillment/list') }}">
                <i class="fas fa-fw fa-list"></i>
                <span>FulFillment</span>
            </a>
        </li>
        @hasrole(['Admin', 'admin', 'Administrative', 'administrative'])
            <li class="nav-item active">
                <a class="nav-link" href="{{ url('/buyer_california') }}">
                    <i class="fas fa-fw fa-handshake"></i>
                    <span>Buyers</span>
                </a>
            </li>
            <li class="nav-item active">
                <a class="nav-link" href="{{ url('/settings_california') }}">
                    <i class="fas fa-fw fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
        @endhasrole
    </ul>
@endif