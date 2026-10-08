@include('layouts.header')

<div id="wrapper">
	@include('layouts.sidebar')
	
	<div id="content-wrapper">
  		<div class="container-fluid">
  			@include('layouts.message')
			@yield('content')
		</div>
	</div>
	
</div> <!-- /#wrapper -->	

@include('layouts.footer')
@yield('footer')
@if (!empty($supplierTermsGate['required']))
    @include('supplier_terms._accept_modal')
@endif
@include('supplierUser.partials.pending_po_versions_modal')
