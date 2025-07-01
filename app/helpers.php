<?php

if (!function_exists('available_locales')) {
    function available_locales(): array
    {
        return explode(',', config('app.available_locales', 'fr,en'));
    }
}