<div class="settings-page-lists">
    <ul class="settings-head">
        <li>
            <a href="<?= url('/profile') ?>" class="menu-item">Hồ sơ</a>
        </li>
        <li>
            <a href="<?= url('/password') ?>" class="menu-item">Đổi mật khẩu</a>
        </li>
        <li>
            <a href="<?= url('/history-code') ?>" class="menu-item">Lịch sử mua mã nguồn</a>
        </li>

    </ul>
</div>
<script>
    $(document).ready(function () {
        var url = window.location.pathname;
        var urlRegExp = new RegExp(url.replace(/\/$/, '') + "$");
        $('.menu-item').each(function () {
            if (urlRegExp.test(this.href.replace(/\/$/, ''))) {
                $(this).addClass('active');
            }
        });
    });
</script>