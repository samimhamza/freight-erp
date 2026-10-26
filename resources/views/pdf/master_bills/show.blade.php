@extends('pdf.layouts.document')

@section('content')
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td width="55%" valign="top">
                @php
                    $logoSrc = ! empty($company['logo_base64'])
                        ? $company['logo_base64']
                        : (file_exists(public_path('images/logo.png'))
                            ? 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('images/logo.png')))
                            : asset('images/logo.png'));
                @endphp
                <img src="{{ $logoSrc }}" alt="{{ $company['name'] }}" style="max-height: 52px; width: auto; margin-bottom: 4px;"><br>
                <p class="brand-meta">Master Bill of Lading</p>
            </td>
            <td width="45%" valign="top">
                <p class="doc-title">MASTER BILL</p>
                <p class="doc-subtitle">{{ $masterBill->ocean_bill_of_lading }}</p>
            </td>
        </tr>
    </table>

    <div class="spacer-16"></div>

    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td width="50%" valign="top">
                <p class="section-title">Shipment</p>
                <div class="panel">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr><td class="muted">Console</td><td class="text-right strong">{{ $masterBill->console }}</td></tr>
                        <tr><td class="muted">Date</td><td class="text-right">{{ $masterBill->date?->format('d M Y H:i') }}</td></tr>
                        @if ($masterBill->vessel)
                            <tr>
                                <td class="muted">Vessel</td>
                                <td class="text-right">{{ $masterBill->vessel->name }} / {{ $masterBill->vessel->voyage }} / {{ $masterBill->vessel->imo }}</td>
                            </tr>
                        @endif
                        @if ($masterBill->carrier)
                            <tr><td class="muted">Carrier</td><td class="text-right">{{ $masterBill->carrier->name ?? $masterBill->carrier }}</td></tr>
                        @endif
                        @if ($masterBill->customs_entry_number)
                            <tr><td class="muted">Custom Entry</td><td class="text-right">{{ $masterBill->customs_entry_number }}</td></tr>
                        @endif
                    </table>
                </div>
            </td>
            <td width="50%" valign="top">
                <p class="section-title">Route &amp; Schedule</p>
                <div class="panel">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        @if ($masterBill->origin)
                            <tr><td class="muted">Origin</td><td class="text-right">{{ $masterBill->origin->name }} ({{ $masterBill->origin->code }})</td></tr>
                        @endif
                        @if ($masterBill->portOfDischarge)
                            <tr><td class="muted">Port of Discharge</td><td class="text-right">{{ $masterBill->portOfDischarge->name }} ({{ $masterBill->portOfDischarge->code }})</td></tr>
                        @endif
                        @if ($masterBill->destination)
                            <tr><td class="muted">Destination</td><td class="text-right">{{ $masterBill->destination->name }} ({{ $masterBill->destination->code }})</td></tr>
                        @endif
                        @if ($masterBill->etd)
                            <tr><td class="muted">ETD</td><td class="text-right">{{ $masterBill->etd->format('d M Y') }}</td></tr>
                        @endif
                        @if ($masterBill->eta)
                            <tr><td class="muted">ETA</td><td class="text-right">{{ $masterBill->eta->format('d M Y') }}</td></tr>
                        @endif
                        @if ($masterBill->ata)
                            <tr><td class="muted">ATA</td><td class="text-right">{{ $masterBill->ata->format('d M Y') }}</td></tr>
                        @endif
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="spacer-16"></div>

    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td width="25%" valign="top"><div class="panel"><p class="muted">Containers</p><p class="strong" style="font-size: 16px;">{{ $totals['containers'] }}</p></div></td>
            <td width="25%" valign="top"><div class="panel"><p class="muted">House Bills</p><p class="strong" style="font-size: 16px;">{{ $totals['house_bills'] }}</p></div></td>
            <td width="25%" valign="top"><div class="panel"><p class="muted">Packages</p><p class="strong" style="font-size: 16px;">{{ number_format($totals['packages'], 0) }}</p></div></td>
            <td width="25%" valign="top"><div class="panel"><p class="muted">Vehicles</p><p class="strong" style="font-size: 16px;">{{ $totals['vehicles'] }}</p></div></td>
        </tr>
    </table>

    <div class="spacer-8"></div>

    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td width="50%" valign="top"><div class="panel"><p class="muted">Total Weight</p><p class="strong">{{ number_format($totals['gross_weight'], 2) }} KG</p></div></td>
            <td width="50%" valign="top"><div class="panel"><p class="muted">Total Volume</p><p class="strong">{{ number_format($totals['volume'], 2) }} M3</p></div></td>
        </tr>
    </table>

    <table class="data" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th>House Bill</th>
                <th>Consignee</th>
                <th class="text-center">Containers</th>
                <th class="text-center">Packages</th>
                <th class="text-right">Gross KG</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($masterBill->houseBills as $houseBill)
                <tr>
                    <td class="strong">{{ $houseBill->house_bill }}</td>
                    <td>{{ $houseBill->customer->name }}</td>
                    <td class="text-center">{{ $houseBill->containers_count ?? $houseBill->containers->count() }}</td>
                    <td class="text-center">{{ $houseBill->containers->sum('packages') }}</td>
                    <td class="text-right">{{ number_format($houseBill->containers->sum('gross_weight'), 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center muted">No house bills</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
