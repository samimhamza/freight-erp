<x-mail::message>
# Invoice {{ $invoice->invoice_number }}

Hello {{ $customer->name }},

Please find attached your invoice **{{ $invoice->invoice_number }}** from {{ $company['name'] }}.

@if ($invoice->due_date)
**Due date:** {{ $invoice->due_date->format('d M Y') }}
@endif

@if ($company['phone'] || $company['email'])
If you have any questions, contact us at
@if ($company['phone']) {{ $company['phone'] }}@endif
@if ($company['phone'] && $company['email']) or @endif
@if ($company['email']) {{ $company['email'] }}@endif.
@endif

Thanks,<br>
{{ $company['name'] }}
</x-mail::message>
