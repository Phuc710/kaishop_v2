<?php
/**
 * SwalPHP - PHP Helper cho SweetAlert2
 * Dùng trong admin PHP khi cần echo <script>Swal...</script>
 */
class SwalPHP
{
    /**
     * Thành công + quay lại trang trước (auto-close 1.5s)
     */
    public static function successBack($message)
    {
        echo '<script>
            Swal.fire({icon:"success",title:"Thành công!",text:"' . addslashes($message) . '",showConfirmButton:false,timer:1500,timerProgressBar:true})
            .then(()=>{window.history.back();window.location.reload();});
        </script>';
    }

    /**
     * Lỗi + quay lại trang trước
     */
    public static function errorBack($message)
    {
        echo '<script>
            Swal.fire({icon:"error",title:"Lỗi!",text:"' . addslashes($message) . '",confirmButtonColor: "#3085d6"})
            .then(()=>{window.history.back();window.location.reload();});
        </script>';
    }

    /**
     * Thành công + reload trang hiện tại (auto-close 1.5s)
     */
    public static function successReload($message)
    {
        echo '<script>
            Swal.fire({icon:"success",title:"Thành công!",text:"' . addslashes($message) . '",showConfirmButton:false,timer:1500,timerProgressBar:true})
            .then(()=>{window.location.reload();});
        </script>';
    }

    /**
     * Thành công + redirect (auto-close 1.5s)
     */
    public static function successRedirect($message, $url)
    {
        echo '<script>
            Swal.fire({icon:"success",title:"Thành công!",text:"' . addslashes($message) . '",showConfirmButton:false,timer:1500,timerProgressBar:true})
            .then(()=>{window.location.href="' . $url . '";});
        </script>';
    }

    /**
     * Lỗi + redirect
     */
    public static function errorRedirect($message, $url)
    {
        echo '<script>
            Swal.fire({icon:"error",title:"Lỗi!",text:"' . addslashes($message) . '",confirmButtonColor: "#3085d6"})
            .then(()=>{window.location.href="' . $url . '";});
        </script>';
    }

    /**
     * Confirm xóa rồi redirect
     */
    public static function confirmDelete($message, $deleteUrl)
    {
        echo '<script>
            Swal.fire({title:"Xác nhận",text:"' . addslashes($message) . '",icon:"warning",
                showCancelButton:true,confirmButtonColor: "#3085d6",cancelButtonColor:"#6c757d"})
            .then((r)=>{if(r.isConfirmed){window.location.href="' . $deleteUrl . '";}});
        </script>';
    }
}
