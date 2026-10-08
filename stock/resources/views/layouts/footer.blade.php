<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<!-- Logout Modal-->
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                <a class="btn btn-primary" href="#" onclick="logout();">Logout</a>
                <form id="frm-logout" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
                <script>
                    function logout() {
                        event.preventDefault();
                        document.getElementById('frm-logout').submit();
                    }
                </script>
            </div>
        </div>
    </div>
</div>

<!-- Sticky Footer -->
<footer class="sticky-footer">
    <div class="container my-auto">
        <div class="copyright text-center my-auto">
            <span>Global Vision Direct (P) Ltd</span>
        </div>
    </div>
</footer>

@include('layouts.partials.vendor-scripts-footer')

{{-- pagination CSS File  --}}
<link href="{{ asset('pagination/pagination.css') }}" rel="stylesheet">

<script>
$(document).ready(function () {
    $('.alert').each(function () {
        var $alert = $(this);
        if ($alert.find('[data-bs-dismiss="alert"]').length === 0) {
            $alert.addClass('alert-dismissible fade show');
            $alert.append(
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
            );
        }
    });
});
</script>
