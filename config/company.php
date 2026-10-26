<?php

return [
    'name' => env('COMPANY_NAME', 'ACME SHIPPING & LOGISTICS L.L.C'),
    'legal_name' => env('COMPANY_LEGAL_NAME', 'ACME SHIPPING & LOGISTICS L.L.C'),
    'address' => env('COMPANY_ADDRESS', '123 Logistics Boulevard, Suite 500'),
    'city' => env('COMPANY_CITY', 'Dubai'),
    'country' => env('COMPANY_COUNTRY', 'UAE'),
    'phone' => env('COMPANY_PHONE', '+971 4 123 4567'),
    'email' => env('COMPANY_EMAIL', 'info@acmelogistics.example.com'),
    'website' => env('COMPANY_WEBSITE', 'https://acmelogistics.example.com'),
    'trn' => env('COMPANY_TRN', '100000000000003'),
    'currency' => env('COMPANY_CURRENCY', 'AED'),
    'remittance_advice_email' => env('REMITTANCE_ADVICE_EMAIL', env('REMITTANCE_INVOICE_EMAIL', env('PAYABLE_INVOICE_EMAIL', 'accounts@acmelogistics.example.com'))),
    'remittance_invoice_email' => env('REMITTANCE_INVOICE_EMAIL', env('REMITTANCE_ADVICE_EMAIL', env('PAYABLE_INVOICE_EMAIL', 'accounts@acmelogistics.example.com'))),
    'payable_invoice_email' => env('PAYABLE_INVOICE_EMAIL', env('REMITTANCE_ADVICE_EMAIL', 'accounts@acmelogistics.example.com')),
    'bank' => [
        'name' => env('COMPANY_BANK_NAME', 'Demo Commercial Bank'),
        'account_title' => env('COMPANY_BANK_ACCOUNT_TITLE', 'ACME SHIPPING & LOGISTICS L.L.C'),
        'account_number' => env('COMPANY_BANK_ACCOUNT_NUMBER', '12345678901234'),
        'iban' => env('COMPANY_BANK_IBAN', 'AE120000001234567890123'),
        'swift' => env('COMPANY_BANK_SWIFT', 'DEMOAEAD'),
    ],
    'logo' => 'images/logo.png',
];
