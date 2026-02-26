<?php

class ContactController extends Controller
{
    public function index()
    {
        global $chungapi;

        $siteName = (string) ($chungapi['ten_web'] ?? 'KaiShop');

        // SEO Data
        $data = [
            'siteName' => $siteName,
            'seoTitle' => 'Liên hệ hỗ trợ | ' . $siteName,
            'seoDescription' => 'Liên hệ hỗ trợ nhanh qua email, Zalo hoặc các kênh mạng xã hội của ' . $siteName . '. Chúng tôi luôn sẵn sàng hỗ trợ bạn.',
            'pageTitle' => 'Liên hệ ' . $siteName,
            'pageSubtitle' => 'Liên hệ hỗ trợ nhanh qua email, Zalo hoặc các kênh mạng xã hội bên dưới.',
            'supportNote' => 'Hỗ trợ trong giờ làm việc hoặc qua kênh online.',

            'contactEmail' => (string) ($chungapi['email_cf'] ?? ''),
            'contactEmailLabel' => 'Email hỗ trợ',

            'contactPhone' => (string) ($chungapi['sdt_admin'] ?? ''),
            'contactPhoneLabel' => 'Số điện thoại / Zalo',

            'socialItems' => [
                [
                    'label' => 'Facebook',
                    'value' => (string) ($chungapi['fb_admin'] ?? ''),
                    'icon_class' => 'fa-brands fa-facebook'
                ],
                [
                    'label' => 'Telegram',
                    'value' => (string) ($chungapi['tele_admin'] ?? ''),
                    'icon_class' => 'fa-brands fa-telegram'
                ],
                [
                    'label' => 'TikTok',
                    'value' => (string) ($chungapi['tiktok_admin'] ?? ''),
                    'icon_class' => 'fa-brands fa-tiktok'
                ],
                [
                    'label' => 'YouTube',
                    'value' => (string) ($chungapi['youtube_admin'] ?? ''),
                    'icon_class' => 'fa-brands fa-youtube'
                ]
            ],
            'chungapi' => $chungapi
        ];

        $this->view('contact/index', $data);
    }
}
