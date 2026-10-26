@php
    $advice = $remittanceAdvice ?? ($remittanceInvoice ?? $payableInvoice);
@endphp
<x-mail::message>
    # Remittance Advice {{ $advice->advice_number ?? $advice->invoice_number }}

    Hello Accounts Team,

    Please find attached the remittance advice
    **{{ $advice->advice_number ?? $advice->invoice_number }}** for carrier
    **{{ $advice->carrier?->name ?? ($advice->masterBill?->carrier?->name ?? 'Carrier') }}**.

    **Console:** {{ $advice->masterBill?->console ?? '—' }}
    **Ocean B/L:**
    {{ $advice->masterBill?->ocean_bill_of_lading ?? '—' }}
    @if ($advice->carrier || $advice->masterBill?->carrier)
        **Carrier:**
        {{ $advice->carrier?->name ?? $advice->masterBill?->carrier?->name }}
    @endif
    @if ($advice->due_date)
        **Due date:** {{ $advice->due_date->format('d M Y') }}
    @endif

    @if ($company['phone'] || $company['email'])
        If you have any questions, contact us at
        @if ($company['phone'])
            {{ $company['phone'] }}
        @endif
        @if ($company['phone'] && $company['email'])
            or
        @endif
        @if ($company['email'])
            {{ $company['email'] }}
        @endif.
    @endif

    Thanks,<br>
    {{ $company['name'] }}
</x-mail::message>

