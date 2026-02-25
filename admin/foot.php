<footer class="main-footer" style="display: none;">
</footer>
<!-- jQuery -->
<script src="https://cdn.jsdelivr.net/gh/quangtuu2006/admin_lite@main/plugins/jquery/jquery.min.js"
    crossorigin="anonymous"></script>
<!-- jQuery UI 1.11.4 -->
<script src="https://cdn.jsdelivr.net/gh/quangtuu2006/admin_lite@main/plugins/jquery-ui/jquery-ui.min.js"
    crossorigin="anonymous"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<script>
    $.widget.bridge('uibutton', $.ui.button)
</script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/gh/quangtuu2006/admin_lite@main/plugins/bootstrap/js/bootstrap.bundle.min.js"
    crossorigin="anonymous">
    </script>
<!-- daterangepicker -->
<script src="https://cdn.jsdelivr.net/gh/quangtuu2006/admin_lite@main/plugins/moment/moment.min.js"
    crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/gh/quangtuu2006/admin_lite@main/plugins/daterangepicker/daterangepicker.js"
    crossorigin="anonymous">
    </script>
<script src="<?= asset('assets/js/flatpickr.js') ?>"></script>
<!-- Tempusdominus Bootstrap 4 -->
<script
    src="https://cdn.jsdelivr.net/gh/quangtuu2006/admin_lite@main/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"
    crossorigin="anonymous">
    </script>
<!-- Summernote -->
<script src="https://cdn.jsdelivr.net/gh/quangtuu2006/admin_lite@main/plugins/summernote/summernote-bs4.min.js"
    crossorigin="anonymous">
    </script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/gh/quangtuu2006/admin_lite@main/dist/js/adminlte.js"
    crossorigin="anonymous"></script>

<!-- SweetAlert2 + Helper (loaded AFTER jQuery) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js" crossorigin="anonymous"></script>
<script src="<?= asset('assets/js/swal_helper.js') ?>"></script>
<script src="<?= asset('assets/js/money_popup.js') ?>"></script>

<?php
/**
 * GLOBAL ADMIN TOAST NOTIFICATION
 * Reads $_SESSION['notify'], shows a top-right toast, auto-clears.
 * ALL admin pages inherit this — write once, use everywhere.
 */
if (isset($_SESSION['notify'])):
    $n = $_SESSION['notify'];
    unset($_SESSION['notify']);
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                }
            });
            Toast.fire({
                icon: '<?= $n['type'] ?? 'info' ?>',
                title: '<?= addslashes($n['message'] ?? $n['title'] ?? 'Done') ?>'
            });
        });
    </script>
<?php endif; ?>

<script>
    $(document).ready(function () {
        if (typeof $ !== 'undefined' && $.fn.tooltip) {
            $('body').tooltip({ selector: '[data-toggle="tooltip"]' });
        }
    });
</script>