<?php

$allowlist = array_map(
    static fn (string $email): string => strtolower(trim($email)),
    explode(',', (string) env('AKSUJET_EDITORIAL_ALLOWLIST', '')),
);

return [
    'editorial_allowlist' => array_values(array_filter($allowlist, static fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)),
];
