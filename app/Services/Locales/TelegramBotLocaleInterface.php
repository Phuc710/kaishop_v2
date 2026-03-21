<?php

interface TelegramBotLocaleInterface
{
    public function getMessage(string $key, array $vars = []): string;
    public function getLocaleCode(): string;
}
