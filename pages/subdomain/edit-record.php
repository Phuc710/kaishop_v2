 
<head>
    <?php require __DIR__.'/../../hethong/head2.php';?>
    <title>Edit Record subdomain | <?=$chungapi['ten_web'];?></title>
    <?php require __DIR__.'/../../hethong/nav.php';?>
<?php
if(isset($_GET['id'])) {
$id = antixss($_GET['id']);
$check_mien = $ketnoi->query("SELECT * FROM `list_record_domain` WHERE `id` = '$id' ");
if($check_mien->num_rows == 1){
    $toz_rec = $check_mien->fetch_array();
    $toz_mien = $ketnoi->query("SELECT * FROM `history_subdomain` WHERE `id` = '".$toz_rec['id_domain']."' ")->fetch_array();
    if($toz_mien['username']!=$username){
    echo '<script type="text/javascript">if(!alert("Miền không tồn tại hay không phải của bạn!")){window.location.href = BASE_URL + "/";}</script>';
    }
}else{
    echo '<script type="text/javascript">if(!alert("Miền không tồn tại hay không phải của bạn!")){window.location.href = BASE_URL + "/";}</script>';
}
}
?>
</head>

<main>
    <section class="py-110 bg-offWhite">
        <div class="container">
            <div class="rounded-3">
                <section class="space-y-6">
                    <div class="row justify-content-center">

                        <!-- THÔNG TIN THANH TOÁN -->
                        <div class="col-md-6 mb-5">
                            <div class="profile-info-card">
                                <div class="profile-info-header">
                                    <h4 class="text-18 fw-semibold text-dark-300">EDIT RECORD</h4>
                                </div>
                                <div class="profile-info-body bg-white">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Đuôi miền</label>
                                       <select name="type" id="type" aria-label="Select a DNS Record Type"
                                            data-control="select2" class="form-select form-select-solid form-select-lg">
                                            <option value="<?=$toz_rec['type'];?>"><?=$toz_rec['type'];?> (đang chọn)
                                            </option>
                                            <option value="A">A (IPv4 Address)</option>
                                            <option value="AAAA">AAAA (IPv6 Address)</option>
                                            <option value="CNAME">CNAME (Canonical Name)</option>
                                            <option value="MX">MX (Mail Exchange)</option>
                                            <option value="TXT">TXT (Text)</option>
                                            <option value="PTR">PTR (Pointer)</option>
                                            <option value="SOA">SOA (Start of Authority)</option>
                                            <option value="SRV">SRV (Service)</option>
                                            <option value="TLSA">TLSA (TLSA Certificate Association)</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
    <label for="username" class="form-label">Name</label>
    <input type="text" class="form-control shadow-none" id="name" placeholder="<?=$toz_rec['name'];?>">
    <div class="form-text"></div>
</div>

                                    <div class="mb-3">
    <label for="username" class="form-label">Content</label>
    <input type="text" class="form-control shadow-none" id="content" placeholder="<?=$toz_rec['content'];?>">
    <div class="form-text"></div>
</div>

                                    <button type="button" onclick="luu()" class="btn btn-primary w-100">
                                            <span id="button1" class="indicator-label">Cập nhật</span>
                                            <span id="button2" class="indicator-progress" style="display: none;"> <i class="fa fa-spinner fa-spin"></i> Đang xử lý.. </span>
                            </button>
                            <a href="javascript:history.back()" class="btn btn-link d-block mt-2">Quay lại</a>
                                </div>
                            </div>
                        </div>

                    </div>
                </section>
            </div>
        </div>
    </section>
</main>

 
            <script>
                                        function luu() {
                                            const button1 = document.getElementById("button1");
                                            const button2 = document.getElementById("button2");

                                            button1.style.display = "none";
                                            button2.style.display = "inline-block";
                                            button2.disabled = true;

                                            const id = "<?=$id;?>";
                                            const type = document.getElementById("type").value;
                                            const name = document.getElementById("name").value;
                                            const content = document.getElementById("content").value;




                                            // Hiển thị sweet alert với nội dung xác nhận mua miền
                                            Swal.fire({
                                                title: 'Xác nhận',
                                                text: "Bạn có chắc chắn thông tin chính xác?",
                                                icon: 'warning',
                                                showCancelButton: true,
                                                confirmButtonColor: '#3085d6',
                                                cancelButtonColor: '#d33',
                                                confirmButtonText: 'Đồng ý',
                                                cancelButtonText: 'Hủy bỏ'
                                            }).then((result) => {
                                                if (result.isConfirmed) {
                                                    const xhr = new XMLHttpRequest();
                                                    xhr.open("POST", "/ajax/subdomain/edit-record.php");
                                                    xhr.setRequestHeader("Content-Type",
                                                        "application/x-www-form-urlencoded");
                                                    xhr.onload = function() {
                                                        button1.style.display = "inline-block";
                                                        button2.style.display = "none";
                                                        button2.disabled = false;

                                                        if (xhr.status === 200) {
                                                            const response = JSON.parse(xhr.responseText);
                                                            if (response.success) {
                                                                Swal.fire({
                                                                    icon: "success",
text: "Mua miền thành công",
                                                                }).then(function() {
                                                                    // Tải lại trang sau khi nhấn OK
                                                                    location.reload();
                                                                });
                                                            } else {
                                                                Swal.fire({
                                                                    icon: "error",
                                                                    text: response.message,
                                                                });
                                                            }
                                                        } else {
                                                            Swal.fire({
                                                                icon: "error",
                                                                text: "Error: " + xhr.statusText,
                                                            });
                                                        }
                                                    };
                                                    xhr.onerror = function() {
                                                        button1.style.display = "inline-block";
                                                        button2.style.display = "none";
                                                        button2.disabled = false;

                                                        Swal.fire({
                                                            icon: "error",
                                                            text: "Error: " + xhr.statusText,
                                                        });
                                                    };
                                                    xhr.send(
                                                   "id=" + encodeURIComponent(id) +
                                                   "&type=" + encodeURIComponent(type) +
                                                   "&name=" + encodeURIComponent(name) +
                                                   "&content=" + encodeURIComponent(content)
                                                    );
                                                } else {
                                                    button1.style.display = "inline-block";
                                                    button2.style.display = "none";
                                                    button2.disabled = false;
                                                }
                                            });
                                        }
</script>
            </div>
            <!--end::Col-->
        </div>
                        </div>
                        <?php require __DIR__.'/../../hethong/foot.php';?>
                        <!--end::Content-->
                    </div>
                    <!--end::Wrapper-->
                </div>
                <!--end::Page-->
            </div>
            <!--end::Root-->

</html>