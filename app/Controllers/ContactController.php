<?php

class ContactController extends Controller
{
    public function index()
    {
        global $chungapi, $siteConfig;

        $siteName = (string) ($siteConfig['ten_web'] ?? ($chungapi['ten_web'] ?? 'KaiShop'));
        $defaultTitle = 'Liên hệ với chúng tôi | ' . $siteName;
        $defaultDescription = 'Thông tin liên hệ, hỗ trợ khách hàng và các kênh mạng xã hội chính thức của ' . $siteName . '. Kết nối với chúng tôi ngay hôm nay.';
        $defaultKeywords = 'liên hệ, hỗ trợ, chăm sóc khách hàng, ' . $siteName;

        $seoTitle = trim((string) ($chungapi['contact_seo_title'] ?? '')) ?: $defaultTitle;
        $seoDescription = trim((string) ($chungapi['contact_seo_desc'] ?? '')) ?: $defaultDescription;
        $seoKeywords = trim((string) ($chungapi['contact_seo_keywords'] ?? '')) ?: $defaultKeywords;

        $pageTitle = trim((string) ($chungapi['contact_page_title'] ?? '')) ?: ('Liên hệ ' . $siteName);
        $pageSubtitle = trim((string) ($chungapi['contact_page_subtitle'] ?? '')) ?: trim((string) ($chungapi['mo_ta'] ?? ''));
        $supportNote = trim((string) ($chungapi['contact_support_note'] ?? ''));

        $contactEmail = trim((string) ($chungapi['email_cf'] ?? ''));
        $contactPhone = trim((string) ($chungapi['sdt_admin'] ?? ''));
        $contactEmailLabel = trim((string) ($chungapi['contact_email_label'] ?? '')) ?: 'Email hỗ trợ';
        $contactPhoneLabel = trim((string) ($chungapi['contact_phone_label'] ?? '')) ?: 'Số điện thoại / Zalo';

        $socialItems = [
            ['label' => 'Facebook', 'value' => (string) ($chungapi['fb_admin'] ?? ''), 'icon_class' => 'fa-brands fa-facebook-f'],
            ['label' => 'Telegram', 'value' => (string) ($chungapi['tele_admin'] ?? ''), 'icon_class' => 'fa-brands fa-telegram'],
            ['label' => 'TikTok', 'value' => (string) ($chungapi['tiktok_admin'] ?? ''), 'icon_class' => 'fa-brands fa-tiktok'],
            ['label' => 'YouTube', 'value' => (string) ($chungapi['youtube_admin'] ?? ''), 'icon_class' => 'fa-brands fa-youtube'],
        ];

        require BASE_PATH . '/views/contact/index.php';
    }
}
