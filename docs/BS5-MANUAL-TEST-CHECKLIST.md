# BS5 Manual Test Checklist — All Pages

**Total screens:** 422 (from `stock/resources/views`, excluding layouts/auth-passwords/emails/errors/components)

**Branch:** `uivendor` · **Site:** https://service.auraprojects.store

---

## How to test (one page at a time — server-safe)

Do **not** open many tabs at once. Test sequentially to avoid hitting process limits.

### Per page — mark `[x]` when done

| # | Check | Pass |
|---|-------|------|
| 1 | Page loads (no 500/404) | [ ] |
| 2 | Layout OK (sidebar, navbar, footer) | [ ] |
| 3 | No console errors (F12) | [ ] |
| 4 | No CDN requests in Network tab (`cdn.`, `jsdelivr`, `cdnjs`) | [ ] |
| 5 | `ui-vendor` assets load (200) | [ ] |
| 6 | Modals open/close — X button top-right | [ ] |
| 7 | Alerts dismiss — X top-right | [ ] |
| 8 | Sidebar collapse menus work | [ ] |
| 9 | DataTables/search/pagination (if present) | [ ] |
| 10 | Forms submit or validate (spot-check) | [ ] |

### Priority order (recommended)

1. **Core** — auth, dashboard, sidebar every module link
2. **High traffic** — product, invoice, purchaseOrder, supplier, supplierUser
3. **July 7 overlap** — draftConsumablePurchaseOrder, bulk_buyer_update, pricing, consumable carton flows
4. **Remaining modules** — work through sections below A→Z

### Partials / JS-only views

These are not standalone URLs — test them **inside** the parent page that includes them:

- `partials/*`
- `draftConsumablePurchaseOrder/partials/form_scripts`
- `draftPurchaseOrder/partials/form_scripts`
- `purchaseOrder/partials/*`
- `supplier/partials/*`
- `courier/partials/*`
- `invoice/*modal*`, `invoice/challanModal`, `invoice/credit_note_modal`
- `supplier_terms/_*` (underscore partials)

---

## Progress summary

| Module | Pages | Done | Notes |
|--------|------:|-----:|-------|
| admin | 16 | [ ] | |
| allocation | 3 | [ ] | |
| apCalculator | 1 | [ ] | |
| auth | 5 | [ ] | |
| bulk_buyer_update | 2 | [ ] | |
| buyer | 3 | [ ] | |
| carton | 2 | [ ] | |
| category | 6 | [ ] | |
| consumable | 12 | [ ] | |
| consumable_inventory | 4 | [ ] | |
| ContainerAllocations | 9 | [ ] | |
| contractor | 4 | [ ] | |
| cornerpackaging | 4 | [ ] | |
| courier | 4 | [ ] | |
| dashboard | 1 | [ ] | |
| draftConsumablePurchaseOrder | 4 | [ ] | |
| draftPurchaseOrder | 4 | [ ] | |
| example | 1 | [ ] | |
| finishRate | 3 | [ ] | |
| forgetPass | 1 | [ ] | |
| fullfillments | 2 | [ ] | |
| hardwares | 5 | [ ] | |
| invoice | 58 | [ ] | |
| manual_stock | 2 | [ ] | |
| notifications | 2 | [ ] | |
| packaging | 3 | [ ] | |
| packingSheet | 3 | [ ] | |
| partials | 4 | [ ] | |
| performanceCards | 4 | [ ] | |
| Permissions | 3 | [ ] | |
| pricing | 7 | [ ] | |
| product | 23 | [ ] | |
| production_emission | 1 | [ ] | |
| ProductionLogs | 4 | [ ] | |
| product_ledger | 3 | [ ] | |
| purchaseBill | 23 | [ ] | |
| purchaseOrder | 27 | [ ] | |
| quality | 3 | [ ] | |
| rejectRepair | 4 | [ ] | |
| report | 2 | [ ] | |
| reports | 1 | [ ] | |
| Roles | 3 | [ ] | |
| samples | 3 | [ ] | |
| ServiceProduct | 6 | [ ] | |
| setting | 8 | [ ] | |
| shipping | 3 | [ ] | |
| shippingLines | 3 | [ ] | |
| smallhardware | 3 | [ ] | |
| smallhardwares | 3 | [ ] | |
| smhsupplier | 3 | [ ] | |
| stockout | 4 | [ ] | |
| supplier | 7 | [ ] | |
| supplierInvoice | 18 | [ ] | |
| supplier_terms | 7 | [ ] | |
| supplierUser | 37 | [ ] | |
| Sustainability | 29 | [ ] | |
| temporaryBuyer | 3 | [ ] | |
| temporaryProduct | 3 | [ ] | |
| transport_logs | 3 | [ ] | |
| TransportLogs | 3 | [ ] | |

**Total done:** _____ / 422

---

## Full page checklist (by module)

### ContainerAllocations (9)
- [ ] `ContainerAllocations/carton-po-generation`
- [ ] `ContainerAllocations/history`
- [ ] `ContainerAllocations/index`
- [ ] `ContainerAllocations/po-generation`
- [ ] `ContainerAllocations/view`
- [ ] `ContainerAllocations/view_allocated_suppliers`
- [ ] `ContainerAllocations/view-history-detail`
- [ ] `ContainerAllocations/view-supplier`
- [ ] `ContainerAllocations/view_supplier_detailed_history`

### Permissions (3)
- [ ] `Permissions/create`
- [ ] `Permissions/edit`
- [ ] `Permissions/index`

### ProductionLogs (4)
- [ ] `ProductionLogs/create`
- [ ] `ProductionLogs/edit`
- [ ] `ProductionLogs/fuelIndex`
- [ ] `ProductionLogs/powerIndex`

### Roles (3)
- [ ] `Roles/create`
- [ ] `Roles/edit`
- [ ] `Roles/index`

### ServiceProduct (6)
- [ ] `ServiceProduct/create`
- [ ] `ServiceProduct/create_serviceCategories`
- [ ] `ServiceProduct/edit`
- [ ] `ServiceProduct/editcategories`
- [ ] `ServiceProduct/index`
- [ ] `ServiceProduct/indexcategories`

### Sustainability (29)
- [ ] `Sustainability/port/create`
- [ ] `Sustainability/port/edit`
- [ ] `Sustainability/port/index`
- [ ] `Sustainability/stage0/index`
- [ ] `Sustainability/stage1/create`
- [ ] `Sustainability/stage1/edit`
- [ ] `Sustainability/stage1/index`
- [ ] `Sustainability/stage2/create`
- [ ] `Sustainability/stage2/edit`
- [ ] `Sustainability/stage2/fuelIndex`
- [ ] `Sustainability/stage2/powerIndex`
- [ ] `Sustainability/stage3/create`
- [ ] `Sustainability/stage3/edit`
- [ ] `Sustainability/stage3/employee/create`
- [ ] `Sustainability/stage3/employee/edit`
- [ ] `Sustainability/stage3/employee/index`
- [ ] `Sustainability/stage3/miscellaneous/create`
- [ ] `Sustainability/stage3/miscellaneous/edit`
- [ ] `Sustainability/stage3/miscellaneous/index`
- [ ] `Sustainability/stage3/powerIndex`
- [ ] `Sustainability/stage4/edit`
- [ ] `Sustainability/stage4/index`
- [ ] `Sustainability/stage5/edit`
- [ ] `Sustainability/stage5/index`
- [ ] `Sustainability/stage6/create`
- [ ] `Sustainability/stage6/edit`
- [ ] `Sustainability/stage6/index`
- [ ] `Sustainability/summary/index`
- [ ] `Sustainability/variable/index`

### TransportLogs (3)
- [ ] `TransportLogs/create`
- [ ] `TransportLogs/edit`
- [ ] `TransportLogs/index`

### admin (16)
- [ ] `admin/artisan_emissions/index`
- [ ] `admin/countries/index`
- [ ] `admin/distribution_emission/index`
- [ ] `admin/employeedetails/index`
- [ ] `admin/employee_expenses/index`
- [ ] `admin/inventory_report/detail_report`
- [ ] `admin/inventory_report/index`
- [ ] `admin/LogBook/index`
- [ ] `admin/logistics_emissions/index`
- [ ] `admin/logistics_parteners/index`
- [ ] `admin/production_emission/index`
- [ ] `admin/ProductSuppliers/index`
- [ ] `admin/supplier-prices/index`
- [ ] `admin/transport_emissions/index`
- [ ] `admin/transportlogs/index`
- [ ] `admin/travel_expenses/index`

### allocation (3)
- [ ] `allocation/create`
- [ ] `allocation/index`
- [ ] `allocation/view`

### apCalculator (1)
- [ ] `apCalculator/create`

### auth (5)
- [ ] `auth/2fa_settings`
- [ ] `auth/2fa_verify`
- [ ] `auth/login`
- [ ] `auth/register`
- [ ] `auth/verify`

### bulk_buyer_update (2)
- [ ] `bulk_buyer_update/all_cost_input`
- [ ] `bulk_buyer_update/index`

### buyer (3)
- [ ] `buyer/create`
- [ ] `buyer/index`
- [ ] `buyer/view`

### carton (2)
- [ ] `carton/manual_invoice_count`
- [ ] `carton/pending_detail`

### category (6)
- [ ] `category/create`
- [ ] `category/index`
- [ ] `category/subCategory/create`
- [ ] `category/subCategory/index`
- [ ] `category/subCategory/view`
- [ ] `category/view`

### consumable (12)
- [ ] `consumable/carton_swapping`
- [ ] `consumable/create`
- [ ] `consumable/create_carton_swapping`
- [ ] `consumable/index`
- [ ] `consumable/manual_invoice_count`
- [ ] `consumable/pending_detail`
- [ ] `consumable/unitType`
- [ ] `consumable/unitTypeCrate`
- [ ] `consumable/unitTypeEdit`
- [ ] `consumable/valuation`
- [ ] `consumable/valuationpdf`
- [ ] `consumable/view`

### consumable_inventory (4)
- [ ] `consumable_inventory/carton_detail_report`
- [ ] `consumable_inventory/cartonindex`
- [ ] `consumable_inventory/detail_report`
- [ ] `consumable_inventory/index`

### contractor (4)
- [ ] `contractor/create`
- [ ] `contractor/downloadBill`
- [ ] `contractor/index`
- [ ] `contractor/view`

### cornerpackaging (4)
- [ ] `cornerpackaging/create`
- [ ] `cornerpackaging/download`
- [ ] `cornerpackaging/index`
- [ ] `cornerpackaging/view`

### courier (4)
- [ ] `courier/create`
- [ ] `courier/index`
- [ ] `courier/partials/us_rule_blocks`
- [ ] `courier/view`

### dashboard (1)
- [ ] `dashboard`

### draftConsumablePurchaseOrder (4)
- [ ] `draftConsumablePurchaseOrder/form`
- [ ] `draftConsumablePurchaseOrder/index`
- [ ] `draftConsumablePurchaseOrder/modal`
- [ ] `draftConsumablePurchaseOrder/partials/form_scripts`

### draftPurchaseOrder (4)
- [ ] `draftPurchaseOrder/form`
- [ ] `draftPurchaseOrder/index`
- [ ] `draftPurchaseOrder/modal`
- [ ] `draftPurchaseOrder/partials/form_scripts`

### example (1)
- [ ] `example`

### finishRate (3)
- [ ] `finishRate/create`
- [ ] `finishRate/index`
- [ ] `finishRate/view`

### forgetPass (1)
- [ ] `forgetPass`

### fullfillments (2)
- [ ] `fullfillments/fullfillment_full_list`
- [ ] `fullfillments/sku_logs`

### hardwares (5)
- [ ] `hardwares/create`
- [ ] `hardwares/index`
- [ ] `hardwares/monthend_consumable_form`
- [ ] `hardwares/monthend_onboarding_index`
- [ ] `hardwares/view`

### invoice (58)
- [ ] `invoice/carton_swapping`
- [ ] `invoice/challan`
- [ ] `invoice/challanModal`
- [ ] `invoice/contractorbill`
- [ ] `invoice/cornerbill`
- [ ] `invoice/cornerbillEdit`
- [ ] `invoice/create`
- [ ] `invoice/create_california`
- [ ] `invoice/create_canada`
- [ ] `invoice/create_carton_swapping`
- [ ] `invoice/create_eu`
- [ ] `invoice/createinvoicesheet`
- [ ] `invoice/createswapping`
- [ ] `invoice/create_uk`
- [ ] `invoice/create_us`
- [ ] `invoice/credit_note_add`
- [ ] `invoice/credit_note_modal`
- [ ] `invoice/credit_notes`
- [ ] `invoice/discharge`
- [ ] `invoice/dischargeCreate`
- [ ] `invoice/downloadUpholstreyBill`
- [ ] `invoice/dustcoverbill`
- [ ] `invoice/dustcoverbillfinal`
- [ ] `invoice/hardwarebill`
- [ ] `invoice/hardwarebillfinal`
- [ ] `invoice/index`
- [ ] `invoice/index_california`
- [ ] `invoice/index_canada`
- [ ] `invoice/index_eu`
- [ ] `invoice/index_uk`
- [ ] `invoice/index_us`
- [ ] `invoice/modal`
- [ ] `invoice/modal_california`
- [ ] `invoice/modal_canada`
- [ ] `invoice/modal_eu`
- [ ] `invoice/modalpackingsheet`
- [ ] `invoice/modal_uk`
- [ ] `invoice/modal_us`
- [ ] `invoice/monthendpopdf`
- [ ] `invoice/packing_create`
- [ ] `invoice/packing_list`
- [ ] `invoice/packing_view`
- [ ] `invoice/pouchbill`
- [ ] `invoice/pouchbillfinal`
- [ ] `invoice/preview`
- [ ] `invoice/preview_hardware_monthend`
- [ ] `invoice/printinv`
- [ ] `invoice/sale_register`
- [ ] `invoice/smallhardwarebill`
- [ ] `invoice/smallhardwarebillfinal`
- [ ] `invoice/swapping`
- [ ] `invoice/upholsterybill`
- [ ] `invoice/view`
- [ ] `invoice/view_california`
- [ ] `invoice/view_canada`
- [ ] `invoice/view_eu`
- [ ] `invoice/view_uk`
- [ ] `invoice/view_us`

### manual_stock (2)
- [ ] `manual_stock/backmonth_index`
- [ ] `manual_stock/backmonth_show`

### notifications (2)
- [ ] `notifications/create`
- [ ] `notifications/index`

### packaging (3)
- [ ] `packaging/create`
- [ ] `packaging/index`
- [ ] `packaging/view`

### packingSheet (3)
- [ ] `packingSheet/create`
- [ ] `packingSheet/index`
- [ ] `packingSheet/view`

### partials (4)
- [ ] `partials/month-end-consumable-auto-fill-qty-js`
- [ ] `partials/month-end-consumable-invoice-math-js`
- [ ] `partials/print-gst-roundoff-row`
- [ ] `partials/supplier-invoice-tds-js`

### performanceCards (4)
- [ ] `performanceCards/create`
- [ ] `performanceCards/index`
- [ ] `performanceCards/modal`
- [ ] `performanceCards/view`

### pricing (7)
- [ ] `pricing/create`
- [ ] `pricing/duplicate`
- [ ] `pricing/edit_pricing_ajax`
- [ ] `pricing/index`
- [ ] `pricing/related_product_calculator`
- [ ] `pricing/sheet`
- [ ] `pricing/view`

### product (23)
- [ ] `product/age`
- [ ] `product/create`
- [ ] `product/erp`
- [ ] `product/erp_history`
- [ ] `product/erp_history_manager`
- [ ] `product/erp_history_reason`
- [ ] `product/erp_manager`
- [ ] `product/erp_us_history`
- [ ] `product/export_import`
- [ ] `product/families`
- [ ] `product/FinshingExtraPrice`
- [ ] `product/FinshingExtraPricecreate`
- [ ] `product/FinshingExtraPriceedit`
- [ ] `product/index`
- [ ] `product/inventory`
- [ ] `product/inventoryDateWiseReport`
- [ ] `product/inventory_pdf`
- [ ] `product/legs`
- [ ] `product/productAnnualReport`
- [ ] `product/productList`
- [ ] `product/valuation`
- [ ] `product/valuationpdf`
- [ ] `product/view`

### product_ledger (3)
- [ ] `product_ledger/create`
- [ ] `product_ledger/index`
- [ ] `product_ledger/view`

### production_emission (1)
- [ ] `production_emission/index`

### purchaseBill (23)
- [ ] `purchaseBill/create`
- [ ] `purchaseBill/create_admin_carton`
- [ ] `purchaseBill/create_admin_consumable`
- [ ] `purchaseBill/createSample`
- [ ] `purchaseBill/create_service`
- [ ] `purchaseBill/downloaded`
- [ ] `purchaseBill/edit_admin_consumable`
- [ ] `purchaseBill/index`
- [ ] `purchaseBill/modal`
- [ ] `purchaseBill/modalcarton`
- [ ] `purchaseBill/modalconsumable`
- [ ] `purchaseBill/modalmulti`
- [ ] `purchaseBill/modalSample`
- [ ] `purchaseBill/modal_service`
- [ ] `purchaseBill/purchase_bills`
- [ ] `purchaseBill/purchase_billsCarton`
- [ ] `purchaseBill/purchase_billsConsumables`
- [ ] `purchaseBill/purchase_billsMulti`
- [ ] `purchaseBill/purchase_bills_service`
- [ ] `purchaseBill/samples`
- [ ] `purchaseBill/service`
- [ ] `purchaseBill/verified`
- [ ] `purchaseBill/view`

### purchaseOrder (27)
- [ ] `purchaseOrder/ConsumableMonthend`
- [ ] `purchaseOrder/consumablePos`
- [ ] `purchaseOrder/create`
- [ ] `purchaseOrder/createCartonPo`
- [ ] `purchaseOrder/createConsumablePo`
- [ ] `purchaseOrder/createSamplePo`
- [ ] `purchaseOrder/createService`
- [ ] `purchaseOrder/edit_service`
- [ ] `purchaseOrder/index`
- [ ] `purchaseOrder/modal`
- [ ] `purchaseOrder/modalCartonPo`
- [ ] `purchaseOrder/modalConsumablePo`
- [ ] `purchaseOrder/modalRecommendedPurchaseOrder`
- [ ] `purchaseOrder/modalSample`
- [ ] `purchaseOrder/monthend`
- [ ] `purchaseOrder/partials/po_supplier_limit_check`
- [ ] `purchaseOrder/partials/send_to_supplier_actions`
- [ ] `purchaseOrder/recommendedPurchaseOrder`
- [ ] `purchaseOrder/samplePurchaseOrder`
- [ ] `purchaseOrder/service_modal`
- [ ] `purchaseOrder/view`
- [ ] `purchaseOrder/viewCartonPo`
- [ ] `purchaseOrder/viewConsumablePo`
- [ ] `purchaseOrder/viewConsumablePoMonthEnd`
- [ ] `purchaseOrder/viewRecommendedPo`
- [ ] `purchaseOrder/viewSample`
- [ ] `purchaseOrder/viewService`

### quality (3)
- [ ] `quality/create`
- [ ] `quality/index`
- [ ] `quality/view`

### rejectRepair (4)
- [ ] `rejectRepair/create`
- [ ] `rejectRepair/index`
- [ ] `rejectRepair/view`
- [ ] `rejectRepair/viewDebitNote`

### report (2)
- [ ] `report/index`
- [ ] `report/shutout`

### reports (1)
- [ ] `reports/advanced_index`

### samples (3)
- [ ] `samples/create`
- [ ] `samples/index`
- [ ] `samples/view`

### setting (8)
- [ ] `setting/createuser`
- [ ] `setting/index`
- [ ] `setting/index_california`
- [ ] `setting/index_canada`
- [ ] `setting/index_eu`
- [ ] `setting/index_uk`
- [ ] `setting/index_us`
- [ ] `setting/viewuser`

### shipping (3)
- [ ] `shipping/create`
- [ ] `shipping/index`
- [ ] `shipping/view`

### shippingLines (3)
- [ ] `shippingLines/create`
- [ ] `shippingLines/index`
- [ ] `shippingLines/view`

### smallhardware (3)
- [ ] `smallhardware/create`
- [ ] `smallhardware/index`
- [ ] `smallhardware/view`

### smallhardwares (3)
- [ ] `smallhardwares/create`
- [ ] `smallhardwares/index`
- [ ] `smallhardwares/view`

### smhsupplier (3)
- [ ] `smhsupplier/create`
- [ ] `smhsupplier/index`
- [ ] `smhsupplier/view`

### stockout (4)
- [ ] `stockout/create`
- [ ] `stockout/edit`
- [ ] `stockout/index`
- [ ] `stockout/view`

### supplier (7)
- [ ] `supplier/create`
- [ ] `supplier/index`
- [ ] `supplier/partials/po_monthly_limits`
- [ ] `supplier/partials/po_monthly_limits_script`
- [ ] `supplier/pricing`
- [ ] `supplier/pricing_pdf`
- [ ] `supplier/view`

### supplierInvoice (18)
- [ ] `supplierInvoice/carton-reverse-confirm`
- [ ] `supplierInvoice/create`
- [ ] `supplierInvoice/create_via_serviceInvoice`
- [ ] `supplierInvoice/create_via_suppinvoice`
- [ ] `supplierInvoice/create_via_suppinvoicecarton`
- [ ] `supplierInvoice/create_via_suppinvoiceconsumable`
- [ ] `supplierInvoice/create_via_suppinvoiceMulti`
- [ ] `supplierInvoice/create_via_suppinvoiceMultiCarton`
- [ ] `supplierInvoice/create_via_suppinvoiceMultiConsumable`
- [ ] `supplierInvoice/index`
- [ ] `supplierInvoice/indexcarton`
- [ ] `supplierInvoice/indexmulti`
- [ ] `supplierInvoice/indexmultiCarton`
- [ ] `supplierInvoice/indexmultiConsumable`
- [ ] `supplierInvoice/modal`
- [ ] `supplierInvoice/modalmulti`
- [ ] `supplierInvoice/service_index`
- [ ] `supplierInvoice/service_modal`

### supplierUser (37)
- [ ] `supplierUser/acceptedPurchaseOrders`
- [ ] `supplierUser/acceptedPurchaseOrdersCarton`
- [ ] `supplierUser/acceptedPurchaseOrdersCartonMulti`
- [ ] `supplierUser/acceptedPurchaseOrdersConsumable`
- [ ] `supplierUser/acceptedPurchaseOrdersConsumableMulti`
- [ ] `supplierUser/acceptedServiceOrders`
- [ ] `supplierUser/assigned_products`
- [ ] `supplierUser/certificates`
- [ ] `supplierUser/dashboard`
- [ ] `supplierUser/draftConsumablePurchaseOrders`
- [ ] `supplierUser/draftPurchaseOrders`
- [ ] `supplierUser/editInvoice`
- [ ] `supplierUser/editInvoicecarton`
- [ ] `supplierUser/editInvoiceconsumable`
- [ ] `supplierUser/editInvoicemulti`
- [ ] `supplierUser/editInvoiceMultiCarton`
- [ ] `supplierUser/editInvoiceMultiConsumable`
- [ ] `supplierUser/instructions`
- [ ] `supplierUser/invoiceOrders`
- [ ] `supplierUser/invoiceOrdersMulti`
- [ ] `supplierUser/invoiceOrdersMultiCarton`
- [ ] `supplierUser/invoiceOrdersMultiConsumable`
- [ ] `supplierUser/modal`
- [ ] `supplierUser/modalcarton`
- [ ] `supplierUser/purchaseOrders`
- [ ] `supplierUser/raiseInvoice`
- [ ] `supplierUser/raiseInvoicecosumableCarton`
- [ ] `supplierUser/raiseInvoiceMultiple`
- [ ] `supplierUser/raiseInvoiceMultipleCarton`
- [ ] `supplierUser/raiseInvoiceMultipleConsumable`
- [ ] `supplierUser/raiseServiceInvoice`
- [ ] `supplierUser/returnedPurchaseOrders`
- [ ] `supplierUser/serivce_modal`
- [ ] `supplierUser/service_editInvoice`
- [ ] `supplierUser/service_invoiceOrders`
- [ ] `supplierUser/serviceOrder`
- [ ] `supplierUser/viewChallan`

### supplier_terms (7)
- [ ] `supplier_terms/_accept_modal`
- [ ] `supplier_terms/admin_acceptance_status`
- [ ] `supplier_terms/admin_edit`
- [ ] `supplier_terms/_document`
- [ ] `supplier_terms/pdf`
- [ ] `supplier_terms/show`
- [ ] `supplier_terms/_styles`

### temporaryBuyer (3)
- [ ] `temporaryBuyer/create`
- [ ] `temporaryBuyer/index`
- [ ] `temporaryBuyer/view`

### temporaryProduct (3)
- [ ] `temporaryProduct/create`
- [ ] `temporaryProduct/index`
- [ ] `temporaryProduct/view`

### transport_logs (3)
- [ ] `transport_logs/create`
- [ ] `transport_logs/edit`
- [ ] `transport_logs/index`


---

## Layout / global (test once)

- [ ] `auth/login`
- [ ] `auth/2fa_verify`
- [ ] `auth/2fa_settings`
- [ ] `forgetPass`
- [ ] `dashboard` — stat cards, notifications, Chart.js line chart
- [ ] Logout modal — X top-right, Cancel, Logout
- [ ] Country dropdown (admin)
- [ ] User dropdown — Settings / Logout
- [ ] Sidebar toggle (hamburger)
- [ ] Scroll-to-top button
- [ ] Flash alert after delete (e.g. product) — X top-right

---

## Sign-off

| Role | Name | Date | All 422 reviewed |
|------|------|------|------------------|
| Tester | | | [ ] |
| Reviewer | | | [ ] |

