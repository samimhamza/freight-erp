@extends('pdf.layouts.document')

@section('content')
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
                <p class="doc-title">TAX INVOICE</p>
                <p class="doc-subtitle"># {{ $invoice->invoice_number }}</p>
                @if ($invoice->invoice_date)
                    <p class="doc-subtitle">
                        {{ $invoice->invoice_date->format('d M Y') }}</p>
                @endif
            </td>
        </tr>
    </table>

    <div class="spacer-16"></div>

    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td width="50%" valign="top">
                <p class="section-title">Bill To</p>
                <div class="panel">
                    @if ($invoice->customer)
                        <p class="strong">{{ $invoice->customer->name }}</p>
                        @if ($invoice->customer->address)
                            <p>{{ $invoice->customer->address }}</p>
                        @endif
                        @if ($invoice->customer->country)
                            <p>{{ $invoice->customer->country->name }}</p>
                        @endif
                        @if ($invoice->customer->phone)
                            <p class="muted">Tel: {{ $invoice->customer->phone }}
                            </p>
                        @endif
                        @if ($invoice->customer->email)
                            <p class="muted">{{ $invoice->customer->email }}</p>
                        @endif
                    @else
                        <p class="muted">—</p>
                    @endif
                </div>
            </td>
            <td width="50%" valign="top">
                <p class="section-title">Shipment &amp; Terms</p>
                <div class="panel">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        @php
                            $houseBillDisplay = $invoice->house_bill_number;
                            $containersList = $invoice->all_container_numbers;
                        @endphp
                        @if ($houseBillDisplay)
                            <tr>
                                <td class="muted">House B/L</td>
                                <td class="text-right strong">
                                    {{ $houseBillDisplay }}</td>
                            </tr>
                        @endif
                        @if (!empty($containersList))
                            <tr>
                                <td class="muted">
                                    {{ count($containersList) > 1 ? 'Containers' : 'Container' }}
                                </td>
                                <td class="text-right strong">
                                    {{ implode(', ', $containersList) }}</td>
                            </tr>
                        @endif
                        @if ($invoice->houseBill?->masterBill)
                            <tr>
                                <td class="muted">Master B/L</td>
                                <td class="text-right">
                                    {{ $invoice->houseBill->masterBill->ocean_bill_of_lading }}
                                </td>
                            </tr>
                            @if ($invoice->houseBill->masterBill->vessel)
                                <tr>
                                    <td class="muted">Vessel / Voyage</td>
                                    <td class="text-right">
                                        {{ $invoice->houseBill->masterBill->vessel->name }}
                                        /
                                        {{ $invoice->houseBill->masterBill->vessel->voyage }}
                                    </td>
                                </tr>
                            @endif
                        @endif
                        @if ($invoice->due_date)
                            <tr>
                                <td class="muted">Due Date</td>
                                <td class="text-right strong">
                                    {{ $invoice->due_date->format('d M Y') }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="muted">Status</td>
                            <td class="text-right">
                                @php
                                    $statusClass = match (
                                        $invoice->status->value
                                    ) {
                                        'PAID' => 'badge-success',
                                        'PARTIALLY_PAID' => 'badge-warning',
                                        'OVERDUE' => 'badge-danger',
                                        'SENT' => 'badge-info',
                                        default => '',
                                    };
                                @endphp
                                <span
                                    class="badge {{ $statusClass }}">{{ str($invoice->status->name)->replace('_', ' ') }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="muted">Currency</td>
                            <td class="text-right strong">
                                {{ $company['currency'] }}</td>
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

    <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 14px;">
        <tr>
            <td width="55%" valign="top" style="padding-right: 14px;">
                @if (!empty($company['bank']))
                    <div class="panel">
                        <p class="section-title" style="margin-bottom: 6px;">Bank Details</p>
                        <table width="100%" cellpadding="2" cellspacing="0"
                            style="font-size: 10px;">
                            @if (!empty($company['bank']['swift']))
                                <tr>
                                    <td class="muted" width="38%">BIC/SWIFT</td>
                                    <td class="strong">
                                        {{ $company['bank']['swift'] }}</td>
                                </tr>
                            @endif
                            @if (!empty($company['bank']['iban']))
                                <tr>
                                    <td class="muted">IBAN</td>
                                    <td class="strong">
                                        {{ $company['bank']['iban'] }}</td>
                                </tr>
                            @endif
                            @if (!empty($company['bank']['account_number']))
                                <tr>
                                    <td class="muted">Account Number</td>
                                    <td class="strong">
                                        {{ $company['bank']['account_number'] }}
                                    </td>
                                </tr>
                            @endif
                            @if (!empty($company['bank']['account_title']))
                                <tr>
                                    <td class="muted">Account Title</td>
                                    <td class="strong">
                                        {{ $company['bank']['account_title'] }}
                                    </td>
                                </tr>
                            @endif
                            @if (!empty($company['bank']['name']))
                                <tr>
                                    <td class="muted">Bank</td>
                                    <td class="strong">
                                        {{ $company['bank']['name'] }}</td>
                                </tr>
                            @endif
                        </table>
                    </div>
                @endif

                @if ($invoice->payments->isNotEmpty())
                    <div class="spacer-8"></div>
                    <p class="section-title">Payment History</p>
                    <table class="data" cellpadding="0" cellspacing="0"
                        style="margin-top: 4px;">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoice->payments as $payment)
                                <tr>
                                    <td>{{ $payment->date?->format('d M Y') ?? '—' }}
                                    </td>
                                    <td class="text-right">
                                        {{ number_format((float) $payment->amount, 2) }}
                                        {{ $company['currency'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </td>
            <td width="45%" valign="top">
                <table class="totals-table" cellpadding="0" cellspacing="0"
                    style="margin-top: 0;">
                    <tr>
                        <td class="label">Subtotal (excl. VAT)</td>
                        <td class="amount">{{ number_format($subtotal, 2) }}
                            {{ $company['currency'] }}</td>
                    </tr>
                    <tr>
                        <td class="label">VAT</td>
                        <td class="amount">{{ number_format($vat_total, 2) }}
                            {{ $company['currency'] }}</td>
                    </tr>
                    <tr class="grand">
                        <td class="label strong">Total Due</td>
                        <td class="amount">{{ number_format($grand_total, 2) }}
                            {{ $company['currency'] }}</td>
                    </tr>
                    @if ($total_paid > 0)
                        <tr>
                            <td class="label">Paid</td>
                            <td class="amount" style="color: #166534;">
                                {{ number_format($total_paid, 2) }}
                                {{ $company['currency'] }}</td>
                        </tr>
                        <tr>
                            <td class="label strong">Balance</td>
                            <td class="amount" style="color: #991b1b;">
                                {{ number_format($total_remaining, 2) }}
                                {{ $company['currency'] }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <div class="spacer-16"></div>

    <div class="panel">
        <p class="section-title" style="margin-bottom: 4px;">Payment Instructions
        </p>
        <p class="muted" style="font-size: 10px; margin: 0;">
            Please reference invoice
            <strong>{{ $invoice->invoice_number }}</strong> on all bank transfers.
            @if ($company['trn'])
                Our TRN is <strong>{{ $company['trn'] }}</strong>.
            @endif
            @if ($company['email'])
                Email: <strong>{{ $company['email'] }}</strong>.
            @endif
        </p>
    </div>
@endsection
