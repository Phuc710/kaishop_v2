<?php

class LocaleViewService
{
    private array $translations = [];

    public function __construct()
    {
        if (app_locale() === 'en') {
            $file = BASE_PATH . '/app/Lang/en.php';
            $this->translations = is_file($file) ? ((array) require $file) : [];
        }
    }

    public function transform(string $html, string $view): string
    {
        if (app_locale() !== 'en') {
            return $html;
        }

        if (!empty($this->translations)) {
            $html = strtr($html, $this->translations);
        }

        $localeBootstrap = <<<HTML
<script>
window.KAI_LOCALE = 'en';
window.KAI_FORCE_ONLY_BINANCE = true;
window.KAI_FORCE_ONLY_BANK = false;
try { localStorage.setItem('kai_currency_is_usd', 'true'); } catch (e) {}
</script>
HTML;

        if (stripos($html, '</head>') !== false) {
            $html = preg_replace('/<\/head>/i', $localeBootstrap . PHP_EOL . '</head>', $html, 1) ?? ($html . $localeBootstrap);
        } else {
            $html = $localeBootstrap . $html;
        }

        return $html;
    }
}
