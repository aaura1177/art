@extends('layouts.app')

@section('content')
    <div class="container mt-5">
        <h2 class="text-center mb-4">Excel Data Import & Export</h2>

        <div class="row mt-4 align-items-center">
            <div class="col-md-7 border border-5">
                <form action="{{ route('product.import') }}" method="POST" enctype="multipart/form-data"
                    class="d-flex align-items-center gap-2  justify-content-between">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary">Import Data</button>

                    <input type="file" id="file" name="file" class="form-control w-50" required>
                    <label for="file" class="form-label mb-0 me-2 text-danger">Product EAN File:</label>

                </form>
            </div>
            <div class="col-md-2">
                {{-- <form action="{{ route('product.export') }}" method="POST">
                    @csrf
                    <button type="submit" onclick="return confirm('Are you sure you want to export the data?')" class="btn btn-success w-100">Export Data</button>
                </form> --}}

                <form action="{{ route('product.sampleexport') }}" method="POST">
                    @csrf
                    <button onclick="return confirm('Are you sure you want to export the data?')" type="submit" class="btn btn-success w-100">Sample Export </button>
                </form>
            </div>
            <div class="col-md-3">
                <form action="{{ route('product.furnitureexport') }}" method="POST">
                    @csrf
                    <button onclick="return confirm('Are you sure you want to export the data?')" type="submit" class="btn btn-success w-100">Furniture Export Data</button>
                </form>
            </div>
        </div>



        
        <div class="row mt-4 align-items-center">
            <div class="col-md-7 border border-5">
                <form action="{{ route('product.purchaseledger') }}" method="POST" enctype="multipart/form-data"
                    class="d-flex align-items-center gap-2  justify-content-between">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary">Import Data</button>
                    <input type="file" id="file" name="file" class="form-control w-50" required>
                    <label for="file" class="form-label mb-0 me-2 text-danger">Purchase Ledger File:</label>

                </form>
            </div>


            {{-- <div class="col-md-2">
               
            </div> --}}
            <div class="col-md-3">
                <form action="{{ route('product.consumablesexport') }}" method="POST">
                    @csrf
                    <button onclick="return confirm('Are you sure you want to export the data?')" type="submit" class="btn btn-success w-100">Consumables Export</button>
                </form>
            </div>
          
        </div>



        <div class="row mt-4 align-items-center">
         

           

          
        </div>

    </div>
@endsection

@section('footer')
    <script type="text/javascript">
        $(function() {
            $('[data-bs-toggle="tooltip"]').tooltip()
        });

        function deleteModal(id) {
            $('#deleteProduct').modal('show');
            $('#deleteProductForm').attr('action', "{{ url('/product/delete') }}" + '/' + id);
        }

        function updateProduct(id) {
            location.href = "{{ url('/product/view') }}" + '/' + id;
        }

        function downloadcsv() {
            $("#modalPrintForm").attr("action", "{{ url('/product/exportCodeWiseExcel') }}");
            return true;
        }

        function printpdf() {
            $("#modalPrintForm").attr("action", "{{ url('/product/printList') }}");
            return true;
        }

        function fetchImage(obj, product) {
            if ($(obj).html() == "") {
                var img = "{{ asset('uploads/allproducts/') }}/" + product + "/" + product + "-1.jpg";
                $(obj).html(
                    '<a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#viewImage" onclick="updateSrc(\'' +
                    product + '\')"><img class="img-thumbnail img-fluid product-img-100" src="' + img +
                    '" alt="No Image" /></a>');
            }
        }

        function updateSrc(product) {
            var img = "{{ asset('uploads/allproducts/') }}/" + product + "/" + product + "-1.jpg";
            $("#bigProImage").attr('src', img);
            $("#viewImage").find("h4").html(product);
        }
    </script>
@endsection
