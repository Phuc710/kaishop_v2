<?php

class PolicyController extends Controller
{
    public function index()
    {
        global $chungapi;

        $faqItems = [
            [
                'question' => 'KaiShop nạp tiền tự động qua kênh nào? Mất bao lâu?',
                'answer' => 'KaiShop hỗ trợ nạp tiền tự động 24/7 qua chuyển khoản ngân hàng nội địa và Binance Pay (USDT). Hầu hết giao dịch được hệ thống xử lý trong vài giây sau khi ngân hàng/ví xác nhận — không cần chờ admin. Mỗi lệnh nạp có mã đối soát riêng, đảm bảo khớp chính xác và an toàn.',
            ],
            [
                'question' => 'KaiShop có uy tín không? Giao dịch có an toàn không?',
                'answer' => 'KaiShop vận hành minh bạch với lịch sử đơn hàng đầy đủ, hệ thống nạp tiền tự động qua cổng thanh toán xác thực, và kênh hỗ trợ trực tiếp qua Telegram. Thông tin thanh toán rõ ràng, không lưu thẻ ngân hàng. Bạn có thể kiểm tra lịch sử nạp và đơn hàng bất cứ lúc nào trong tài khoản.',
            ],
            [
                'question' => 'Mua hàng xong tôi nhận sản phẩm bằng cách nào?',
                'answer' => 'Với sản phẩm giao tự động (tài khoản số, source code, link tải), hệ thống trả nội dung ngay trong đơn hàng sau khi thanh toán thành công — không cần chờ. Với dịch vụ cần xử lý thủ công hoặc cần thông tin bổ sung từ bạn, admin sẽ tiếp nhận và xử lý theo quy trình đã cấu hình sẵn trên sản phẩm.',
            ],
            [
                'question' => 'KaiShop bán những loại sản phẩm và dịch vụ số nào?',
                'answer' => 'KaiShop tập trung vào nhóm sản phẩm số: tài khoản game và dịch vụ số, source code website/app, công cụ hỗ trợ MMO (marketing online), dịch vụ tự động hoá và các sản phẩm kỹ thuật số giao ngay. Danh mục được chia rõ ràng, hỗ trợ tìm kiếm và lọc theo nhóm để mua nhanh hơn.',
            ],
            [
                'question' => 'Sản phẩm có bảo hành không? Nếu lỗi tôi liên hệ ai?',
                'answer' => 'Chính sách bảo hành áp dụng theo mô tả trên từng sản phẩm cụ thể. Nếu gặp sự cố sau mua, bạn liên hệ hỗ trợ trực tiếp qua Telegram hoặc kênh liên hệ được hiển thị trên trang. Vui lòng cung cấp mã đơn hàng để admin xử lý nhanh nhất có thể.',
            ],
        ];

        $this->view('policy/index', [
            'chungapi' => $chungapi,
            'faqItems' => $faqItems
        ]);
    }
}
