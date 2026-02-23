<!-- Image Manager Modal -->
<div class="modal fade" id="imageManagerModal" tabindex="-1" role="dialog" aria-labelledby="imageManagerModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageManagerModalLabel">Thư viện ảnh</h5>
                <div class="ml-auto d-flex align-items-center">
                    <button class="btn btn-danger btn-sm mr-2" id="btnDeleteSelected" style="display: none;"
                        onclick="deleteSelectedImages()">
                        <i class="fas fa-trash"></i> Xóa (<span id="deleteCount">0</span>)
                    </button>
                    <input type="text" id="imageSearch" class="form-control form-control-sm mr-2"
                        placeholder="Tìm kiếm ảnh..." style="width: 200px;">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-9">
                        <div class="custom-file">
                            <!-- multiple attribute for multi-select -->
                            <input type="file" class="custom-file-input" id="uploadImageInput" accept="image/*"
                                multiple>
                            <label class="custom-file-label" for="uploadImageInput">Chọn ảnh...</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary btn-block" type="button" id="btnUploadAction">
                            <i class="fas fa-upload"></i> Upload
                        </button>
                    </div>
                </div>
                <div class="row" id="imageList" style="max-height: 500px; overflow-y: auto;">
                    <!-- Images will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <small class="text-muted mr-auto">Tip: Click để chọn nhiều ảnh. Double-click để chọn ảnh cho sản
                    phẩm.</small>
                <button type="button" class="btn btn-primary" id="btnChooseImage">Chọn ảnh</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<style>
    .image-item {
        position: relative;
        cursor: pointer;
        border: 2px solid transparent;
        transition: all 0.2s;
    }

    .image-item.selected {
        border-color: #007bff;
        background-color: #e8f0fe;
        transform: scale(0.95);
    }

    .image-item .check-icon {
        display: none;
        position: absolute;
        top: 5px;
        right: 5px;
        background: #007bff;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        text-align: center;
        line-height: 20px;
        font-size: 12px;
    }

    .image-item.selected .check-icon {
        display: block;
    }
</style>

<script>
    var selectedImages = [];
    var allImages = []; // Store api data for filtering
    var imageManagerAjaxUrl = '<?= APP_DIR ?>/admin/ajax-image.php';

    function openImageManager() {
        $('#imageManagerModal').modal('show');
        loadImages();
        selectedImages = [];
        updateDeleteButton();
    }

    function loadImages() {
        $.ajax({
            url: imageManagerAjaxUrl,
            type: 'POST',
            data: { action: 'list' },
            dataType: 'json',
            cache: false, // Prevent caching
            success: function (response) {
                if (response.data) {
                    allImages = response.data;
                    // Re-apply search if exists
                    var searchText = $('#imageSearch').val().toLowerCase();
                    if (searchText.length >= 3) {
                        var filteredImages = allImages.filter(function (img) {
                            return img.name.toLowerCase().indexOf(searchText) > -1;
                        });
                        renderImages(filteredImages);
                    } else {
                        renderImages(allImages);
                    }
                } else {
                    $('#imageList').html('<div class="col-12 text-center">Chưa có ảnh nào</div>');
                }
            }
        });
    }

    function renderImages(images) {
        var html = '';
        if (images.length > 0) {
            images.forEach(function (img) {
                var isSelected = selectedImages.includes(img.url);
                var selectedClass = isSelected ? 'selected' : '';

                html += '<div class="col-md-2 col-4 mb-3">';
                html += '<div class="card h-100 image-item ' + selectedClass + '" onclick="toggleSelection(\'' + img.url + '\')" ondblclick="selectImage(\'' + img.url + '\')">';
                html += '<div class="check-icon"><i class="fas fa-check"></i></div>';
                html += '<img src="' + img.url + '" class="card-img-top" loading="lazy" style="height: 100px; object-fit: cover;">';
                html += '<div class="card-body p-1 text-center small text-truncate" title="' + img.name + '">' + img.name + '</div>';
                html += '</div></div>';
            });
        } else {
            html = '<div class="col-12 text-center">Không tìm thấy ảnh</div>';
        }
        $('#imageList').html(html);
    }

    // Toggle selection for multiple delete
    function toggleSelection(url) {
        var index = selectedImages.indexOf(url);
        if (index > -1) {
            selectedImages.splice(index, 1);
        } else {
            selectedImages.push(url);
        }

        // Find element by src
        var img = $('#imageList img[src="' + url + '"]');
        var card = img.closest('.image-item');
        if (selectedImages.includes(url)) {
            card.addClass('selected');
        } else {
            card.removeClass('selected');
        }

        updateDeleteButton();
    }

    function updateDeleteButton() {
        var count = selectedImages.length;
        $('#deleteCount').text(count);
        if (count > 0) {
            $('#btnDeleteSelected').show();
        } else {
            $('#btnDeleteSelected').hide();
        }
    }

    function selectImage(url) {
        $('#image').val(url).trigger('change');
        $('#imageManagerModal').modal('hide');
    }

    // Handle "Choose Image" button click
    $(document).on('click', '#btnChooseImage', function () {
        if (selectedImages.length === 0) {
            Swal.fire('Thông báo', 'Vui lòng chọn một ảnh', 'warning');
            return;
        }
        if (selectedImages.length > 1) {
            Swal.fire('Thông báo', 'Vui lòng chỉ chọn một ảnh để làm ảnh đại diện', 'warning');
            return;
        }
        selectImage(selectedImages[0]);
    });

    // Update label when files are selected
    $(document).on('change', '#uploadImageInput', function () {
        var files = $(this).prop('files');
        var label = 'Chọn nhiều ảnh để upload (Tự động chuyển sang WebP)';
        if (files.length > 0) {
            var names = [];
            for (var i = 0; i < files.length; i++) {
                names.push(files[i].name);
            }
            label = files.length + ' file(s): ' + names.join(', ');
        }
        $(this).next('.custom-file-label').html(label);
    });

    // Handle Upload Button Click
    $(document).on('click', '#btnUploadAction', function () {
        var file_input = $('#uploadImageInput');
        var files = file_input.prop('files');

        if (files.length === 0) {
            Swal.fire('Lỗi', 'Vui lòng chọn ít nhất một ảnh để upload', 'warning');
            return;
        }

        var form_data = new FormData();
        for (var i = 0; i < files.length; i++) {
            form_data.append('files[]', files[i]);
        }
        form_data.append('action', 'upload');

        Swal.fire({
            title: 'Đang upload...',
            text: 'Vui lòng chờ giây lát, đang chuyển đổi sang WebP',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: imageManagerAjaxUrl,
            type: 'POST',
            dataType: 'text',
            cache: false,
            contentType: false,
            processData: false,
            data: form_data,
            success: function (response) {
                try {
                    var json = JSON.parse(response);
                    if (json.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Thành công',
                            text: json.message,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                        // Clear search to show new images
                        $('#imageSearch').val('');
                        loadImages();
                        file_input.val(''); // Reset input
                        $('.custom-file-label').html('Chọn nhiều ảnh để upload (Tự động chuyển sang WebP)');
                        $('#imageList').scrollTop(0);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi',
                            text: json.error || (json.details ? json.details.join(', ') : 'Lỗi không xác định'),
                        });
                    }
                } catch (e) {
                    Swal.fire('Lỗi', 'Lỗi phản hồi từ server: ' + e.message, 'error');
                }
            },
            error: function (xhr, status, error) {
                var errorMsg = 'Lỗi kết nối server: ' + error;
                if (xhr.status === 413) {
                    errorMsg = 'File upload quá lớn so với giới hạn của hosting (413 Request Entity Too Large)';
                } else if (xhr.status === 500) {
                    errorMsg = 'Lỗi hệ thống từ server (500 Internal Server Error). Vui lòng kiểm tra lại cấu hình PHP.';
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Upload thất bại',
                    text: errorMsg,
                });
            }
        });
    });

    // Search Functionality
    $('#imageSearch').on('keyup', function () {
        var value = $(this).val().toLowerCase();

        // If search is less than 3 chars, reset to full list
        if (value.length < 3) {
            if (allImages.length > 0) {
                renderImages(allImages);
            }
            return;
        }

        var filteredImages = allImages.filter(function (img) {
            return img.name.toLowerCase().indexOf(value) > -1;
        });
        renderImages(filteredImages);
    });

    // Delete Selected Images
    function deleteSelectedImages() {
        if (selectedImages.length === 0) return;

        Swal.fire({
            title: 'Bạn có chắc chắn?',
            text: "Bạn muốn xóa " + selectedImages.length + " ảnh đã chọn?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Xóa ngay',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: imageManagerAjaxUrl,
                    type: 'POST',
                    data: {
                        action: 'delete',
                        files: selectedImages
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Đã xóa!',
                                text: response.message,
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000
                            });
                            selectedImages = [];
                            updateDeleteButton();
                            loadImages();
                        } else {
                            Swal.fire('Lỗi', response.error, 'error');
                        }
                    }
                });
            }
        });
    }

    // Custom file input label update
    $(".custom-file-input").on("change", function () {
        var files = $(this).prop('files');
        var label = files.length > 1 ? files.length + ' files selected' : (files[0] ? files[0].name : 'Chọn nhiều ảnh...');
        $(this).siblings(".custom-file-label").addClass("selected").html(label);
    });
</script>
