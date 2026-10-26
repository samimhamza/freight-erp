@extends('pdf.layouts.document')

@section('content')
    @php
        $advice = $remittanceAdvice ?? ($remittanceInvoice ?? $payableInvoice);
    @endphp

    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12px;">
        <tr>
            <td style="background: #0f766e; height: 4px; line-height: 4px;"
                colspan="2"></td>
        </tr>
    </table>

    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td width="55%" valign="top">
                @php
                    $logoSrc = !empty($company['logo_base64'])
                        ? $company['logo_base64']
                        : (file_exists(public_path('images/logo.png'))
                            ? 'data:image/png;base64,' .
                                base64_encode(
                                    file_get_contents(
                                        public_path('images/logo.png'),
                                    ),
                                )
                            : asset('images/logo.png'));
                @endphp
                <img src="{{ $logoSrc }}" alt="{{ $company['name'] }}"
                    style="max-height: 52px; width: auto; margin-bottom: 6px;"><br>
                <p class="brand-meta" style="margin-top: 2px;">
                    @if ($company['address'])
                        {{ $company['address'] }}<br>
                    @endif
                    @if (
                        !empty($company['city']) &&
                            !str_contains($company['address'], $company['city']))
                        {{ $company['city'] }}, {{ $company['country'] }}<br>
                    @endif
                    @if ($company['phone'])
                        Tel: {{ $company['phone'] }}<br>
                    @endif
                    @if ($company['email'])
                        Email: {{ $company['email'] }}<br>
                    @endif
                    @if ($company['trn'])
                        TRN: {{ $company['trn'] }}
                    @endif
                </p>
            </td>
            <td width="45%" valign="top">
                <p class="doc-title">REMITTANCE ADVICE</p>
                <p class="doc-subtitle"># {{ $advice->advice_number ?? $advice->invoice_number }}</p>
                @if ($advice->advice_date ?? $advice->invoice_date)
                    <p class="doc-subtitle">
                        {{ ($advice->advice_date ?? $advice->invoice_date)->format('d M Y') }}</p>
                @endif
            </td>
        </tr>
    </table>

    <div class="spacer-16"></div>

    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td width="50%" valign="top">
                <p class="section-title">Payee / Carrier Details</p>
                <div class="panel">
                    @php
                        $carrier =
                            $advice->carrier ?? $advice->masterBill?->carrier;
                    @endphp
                    <p class="strong">{{ $carrier?->name ?? 'Carrier' }}</p>
                    @if ($carrier?->code)
                        <p class="muted">Carrier Code: {{ $carrier->code }}</p>
                    @endif
                    @if ($carrier?->account_number)
                        <p class="strong" style="margin-top: 4px; color: #0f766e;">
                            Account Number (Bank Code):
                            {{ $carrier->account_number }}</p>
                    @endif
                    <p class="muted" style="margin-top: 3px;">Master B/L:
                        {{ $advice->masterBill?->ocean_bill_of_lading ?? '—' }}
                    </p>
                </div>
            </td>
            <td width="50%" valign="top">
                <p class="section-title">Shipment &amp; Terms</p>
                <div class="panel">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="muted">Console</td>
                            <td class="text-right strong">
                                {{ $advice->masterBill?->console ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="muted">Ocean B/L</td>
                            <td class="text-right strong">
                                {{ $advice->masterBill?->ocean_bill_of_lading ?? '—' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="muted">Carrier</td>
                            <td class="text-right">
                                {{ $advice->carrier?->name ?? ($advice->masterBill?->carrier?->name ?? '—') }}
                            </td>
                        </tr>
                        @if ($advice->masterBill?->vessel)
                            <tr>
                                <td class="muted">Vessel / Voyage</td>
                                <td class="text-right">
                                    {{ $advice->masterBill->vessel->name }}
                                    / {{ $advice->masterBill->vessel->voyage }}
                                </td>
                            </tr>
                        @endif
                        @if ($advice->due_date)
                            <tr>
                                <td class="muted">Due Date</td>
                                <td class="text-right strong">
                                    {{ $advice->due_date->format('d M Y') }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="muted">Status</td>
                            <td class="text-right">
                                @php
                                    $statusClass = match (
                                        $advice->status->value
                                    ) {
                                        'PAID' => 'badge-success',
                                        'PARTIALLY_PAID' => 'badge-warning',
                                        'OVERDUE' => 'badge-danger',
                                        'SENT' => 'badge-info',
                                        default => '',
                                    };
                                @endphp
                                <span
                                    class="badge {{ $statusClass }}">{{ str($advice->status->name)->replace('_', ' ') }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="muted">Currency</td>
                            <td class="text-right strong">
                                {{ $company['currency'] ?? 'AED' }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table class="data" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th width="5%" class="text-center">#</th>
                <th width="35%">Description</th>
                <th width="10%" class="text-center">Qty</th>
                <th width="15%" class="text-right">Unit Price</th>
                <th width="15%" class="text-right">VAT</th>
                <th width="20%" class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($lines as $index => $line)
                <tr>
                    <td class="text-center muted">{{ $index + 1 }}</td>
                    <td>{{ $line['description'] }}</td>
                    <td class="text-center">{{ $line['quantity'] }}</td>
                    <td class="text-right">
                        {{ number_format($line['unit_price'], 2) }}</td>
                    <td class="text-right">{{ number_format($line['vat'], 2) }}
                    </td>
                    <td class="text-right strong">
                        {{ number_format($line['line_total'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center muted">No line items</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td width="55%" valign="top">
                @if ($advice->payments->isNotEmpty())
                    <div class="panel" style="margin-top: 14px;">
                        <p class="section-title" style="margin-bottom: 6px;">Payment
                            History</p>
                        <table width="100%" cellpadding="2" cellspacing="0"
                            style="font-size: 10px;">
                            @foreach ($advice->payments as $payment)
                                <tr>
                                    <td class="muted">
                                        {{ $payment->payment_reference }}
                                        ({{ $payment->date?->format('d M Y') }})
                                    </td>
                                    <td class="text-right strong">
                                        {{ number_format((float) $payment->amount, 2) }}
                                        AED</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endif
            </td>
            <td width="45%" valign="top">
                <table class="totals-table" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="label">Subtotal (excl. VAT)</td>
                        <td class="amount">{{ number_format($subtotal, 2) }}
                            {{ $company['currency'] ?? 'AED' }}</td>
                    </tr>
                    <tr>
                        <td class="label">VAT</td>
                        <td class="amount">
                            {{ number_format($vat_total ?? ($vatTotal ?? 0), 2) }}
                            {{ $company['currency'] ?? 'AED' }}</td>
                    </tr>
                    <tr class="grand">
                        <td class="label strong">Total Payable</td>
                        <td class="amount">{{ number_format($grand_total, 2) }}
                            {{ $company['currency'] ?? 'AED' }}</td>
                    </tr>
                    @if ($total_paid > 0)
                        <tr>
                            <td class="label">Paid</td>
                            <td class="amount" style="color: #166534;">-
                                {{ number_format($total_paid, 2) }}
                                {{ $company['currency'] ?? 'AED' }}</td>
                        </tr>
                        <tr>
                            <td class="label strong">Balance Due</td>
                            <td class="amount" style="color: #991b1b;">
                                {{ number_format($total_remaining, 2) }}
                                {{ $company['currency'] ?? 'AED' }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <div class="spacer-16"></div>

    <div class="panel">
        <p class="section-title" style="margin-bottom: 4px;">Payment Notes</p>
        <p class="muted" style="font-size: 10px; margin: 0;">
            Remittance advice <strong>{{ $advice->advice_number ?? $advice->invoice_number }}</strong> for
            carrier <strong>{{ $carrier?->name ?? 'Carrier' }}</strong>.
            Master B/L:
            <strong>{{ $advice->masterBill?->ocean_bill_of_lading ?? 'N/A' }}</strong>
            | Console:
            <strong>{{ $advice->masterBill?->console ?? 'N/A' }}</strong>.
        </p>
    </div>
@endsection

