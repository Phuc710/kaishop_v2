<?php

class CheckCardCardGeneratorService
{
    public function generate(string $bin, string $mm, string $yy, string $cvv): string
    {
        $number = preg_replace('/\D/', '', $bin);

        while (strlen($number) < 15) {
            $number .= (string) random_int(0, 9);
        }

        $number .= $this->luhnCheckDigit($number);

        $month = strtoupper(trim($mm)) === 'RN'
            ? str_pad((string) random_int(1, 12), 2, '0', STR_PAD_LEFT)
            : trim($mm);

        $year = strtoupper(trim($yy)) === 'RN'
            ? (string) random_int(26, 31)
            : trim($yy);

        $code = strtoupper(trim($cvv)) === 'RN'
            ? (string) random_int(100, 999)
            : trim($cvv);

        return "{$number}|{$month}|{$year}|{$code}";
    }

    public function mask(string $card): string
    {
        $parts = explode('|', $card);
        $number = $parts[0] ?? '';

        if (strlen($number) > 10) {
            $parts[0] = substr($number, 0, 6) . '...' . substr($number, -4);
        }

        return implode('|', $parts);
    }

    private function luhnCheckDigit(string $partial): int
    {
        $sum = 0;
        $double = true;

        for ($i = strlen($partial) - 1; $i >= 0; $i--) {
            $digit = (int) $partial[$i];

            if ($double) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $double = !$double;
        }

        return (10 - ($sum % 10)) % 10;
    }
}
