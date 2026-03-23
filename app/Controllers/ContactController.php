<?php

class ContactController extends Controller
{
    public function index()
    {
        global $chungapi;

        if (function_exists('app_request_path') && app_request_path(true) === '/lienhe') {
            header('Location: ' . url('lien-he'), true, 301);
            exit;
        }

        $siteName = (string) ($chungapi['ten_web'] ?? 'KaiShop');

        $data = [
            'siteName' => $siteName,
            'seoTitle' => 'Liên hệ hỗ trợ | ' . $siteName,
            'seoDescription' => 'Liên hệ hỗ trợ nhanh qua email, Zalo hoặc các kênh mạng xã hội của ' . $siteName . '. Chúng tôi luôn sẵn sàng hỗ trợ bạn.',
            'seoKeywords' => SeoContentHelper::keywordString([
                'liên hệ ' . $siteName,
                'hỗ trợ khách hàng',
                'telegram hỗ trợ',
                'zalo hỗ trợ',
                $siteName,
            ]),
            'seoCanonical' => url('lien-he'),
            'pageTitle' => 'Liên hệ ' . $siteName,
            'pageSubtitle' => 'Liên hệ hỗ trợ nhanh qua các kênh mạng xã hội bên dưới.',
            'supportNote' => (string) ($chungapi['contact_support_note'] ?? 'Hỗ trợ trong giờ làm việc hoặc qua kênh online.'),
            'contactEmail' => (string) ($chungapi['email_cf'] ?? ''),
            'contactEmailLabel' => 'Email hỗ trợ',
            'contactPhone' => (string) ($chungapi['sdt_admin'] ?? ''),
            'contactPhoneLabel' => 'Số điện thoại / Zalo',
            'socialItems' => [
                [
                    'label' => 'Facebook',
                    'value' => (string) ($chungapi['fb_admin'] ?? ''),
                    'icon_class' => 'fa-brands fa-facebook',
                ],
                [
                    'label' => 'Telegram',
                    'value' => (string) ($chungapi['tele_admin'] ?? ''),
                    'icon_class' => 'fa-brands fa-telegram',
                ],
                [
                    'label' => 'TikTok',
                    'value' => (string) ($chungapi['tiktok_admin'] ?? ''),
                    'icon_class' => 'fa-brands fa-tiktok',
                ],
                [
                    'label' => 'YouTube',
                    'value' => (string) ($chungapi['youtube_admin'] ?? ''),
                    'icon_class' => 'fa-brands fa-youtube',
                ],
            ],
            'chungapi' => $chungapi,
        ];

        $this->view('contact/index', $data);
    }
}
