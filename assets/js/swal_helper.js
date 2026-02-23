/**
 * SwalHelper - Centralized SweetAlert2 Wrapper
 * Dung chung cho toan bo project
 */
const SwalHelper = {
    // ============ GLOBAL TOAST INSTANCE ============
    _getToast() {
        return Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2500,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
        });
    },

    // ============ TOAST (generic top-right notification) ============
    toast(message, icon = 'success') {
        this._getToast().fire({
            icon: icon,
            title: message
        });
    },

    // ============ SUCCESS (top-right toast, auto-close 3s, no button) ============
    success(message, callback = null) {
        this._getToast().fire({
            icon: 'success',
            title: message
        }).then(() => {
            if (callback) callback();
        });
    },

    // ============ ERROR (has OK button) ============
    error(message, callback = null) {
        Swal.fire({
            icon: 'error',
            title: 'Thất Bại',
            text: message || 'Có lỗi xảy ra.',
            confirmButtonText: 'OK',
            confirmButtonColor: '#3085d6'
        }).then(() => {
            if (callback) callback();
        });
    },

    // ============ WARNING ============
    warning(message, callback = null) {
        Swal.fire({
            icon: 'warning',
            title: 'Cảnh Báo',
            text: message,
            confirmButtonColor: '#3085d6'
        }).then(() => {
            if (callback) callback();
        });
    },

    // ============ INFO ============
    info(message) {
        Swal.fire({
            icon: 'info',
            title: 'Thông Báo',
            text: message,
            confirmButtonColor: '#3085d6'
        });
    },

    // ============ CONFIRM ============
    confirm(title, text, onConfirm, onCancel = null) {
        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                onConfirm();
            } else if (onCancel) {
                onCancel();
            }
        });
    },

    // ============ CONFIRM DELETE ============
    confirmDelete(onConfirm) {
        Swal.fire({
            title: 'Xác nhận xóa?',
            text: 'Hành động này không thể hoàn tác!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                onConfirm();
            }
        });
    },

    // ============ CONFIRM LOGOUT ============
    confirmLogout(logoutUrl) {
        Swal.fire({
            title: 'Đăng xuất?',
            text: 'Bạn có chắc muốn đăng xuất?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = logoutUrl;
            }
        });
    },

    // ============ LOADING ============
    loading(message = 'Đang xử lý...') {
        Swal.fire({
            title: message,
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => { Swal.showLoading(); }
        });
    },

    // ============ CLOSE LOADING ============
    closeLoading() {
        Swal.close();
    },

    // ============ SUCCESS + REDIRECT (auto-close 1.5s) ============
    successRedirect(message, url) {
        Swal.fire({
            icon: 'success',
            title: 'Thành Công',
            text: message,
            showConfirmButton: false,
            timer: 1500,
            timerProgressBar: true
        }).then(() => {
            window.location.href = url;
        });
    },

    // ============ SUCCESS + OK + REDIRECT (default 1.2s) ============
    successOkRedirect(message, url, delay = 1200) {
        Swal.fire({
            title: 'Thành Công',
            text: message || 'Thao tác thành công.',
            icon: 'success',
            showConfirmButton: true,
            confirmButtonText: 'OK',
            confirmButtonColor: '#3085d6',
            timer: delay
        }).then(() => {
            window.location.href = url;
        });
    },

    successToastRedirect(message, url) {
        Swal.fire({
            icon: 'success',
            title: message,
            showConfirmButton: false,
            timer: 1500,
            timerProgressBar: true
        }).then(() => {
            window.location.href = url;
        });
    },

    successReload(message) {
        Swal.fire({
            icon: 'success',
            title: 'Thành Công',
            text: message,
            showConfirmButton: false,
            timer: 1500,
            timerProgressBar: true
        }).then(() => {
            window.location.reload();
        });
    },

    successToastReload(message) {
        this._getToast().fire({
            icon: 'success',
            title: message
        }).then(() => {
            window.location.reload();
        });
    },

    // ============ ERROR + REDIRECT ============
    errorRedirect(message, url) {
        Swal.fire({
            icon: 'error',
            title: 'Thất Bại',
            text: message,
            confirmButtonColor: '#3085d6'
        }).then(() => {
            window.location.href = url;
        });
    },

    // ============ ERROR + RELOAD ============
    errorReload(message) {
        Swal.fire({
            icon: 'error',
            title: 'Thất Bại',
            text: message,
            confirmButtonColor: '#3085d6'
        }).then(() => {
            window.location.reload();
        });
    },

};

// ============ BACKWARD COMPATIBLE ============
function showMessage(message, type) {
    SwalHelper.toast(message, type === 'success' ? 'success' : 'error');
}
