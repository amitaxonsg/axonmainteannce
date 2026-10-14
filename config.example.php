<?php
declare(strict_types=1);
return [
  'app_url' => 'https://maintenance.axon.com.sg',
  'app_key' => 'REPLACE_WITH_64_HEX_CHARACTERS_FROM_OPENSSL_RAND',
  'database' => __DIR__ . '/var/portal.sqlite',
  'timezone' => 'Asia/Singapore',
  'mail_enabled' => false,
  'stripe_enabled' => false,
  'low_balance_default' => 3.0,
];
