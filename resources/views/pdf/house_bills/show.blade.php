@extends('pdf.layouts.document')

@section('content')
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
                    style="max-height: 52px; width: auto; margin-bottom: 4px;"><br>
                <p class="brand-meta">House Bill of Lading</p>
            </td>
            <td width="45%" valign="top">
                <p class="doc-title">HOUSE BILL</p>
                <p class="doc-subtitle">{{ $houseBill->house_bill }}</p>
            </td>
        </tr>
    </table>

    <div class="spacer-16"></div>

    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td width="33%" valign="top">
                <p class="section-title">Shipper / Consignor</p>
                <div class="panel">
                    <p class="strong">{{ $houseBill->consignor->name }}</p>
                    @if ($houseBill->consignor->address)
                        <p>{{ $houseBill->consignor->address }}</p>
                    @endif
                    @if ($houseBill->consignor->country)
                        <p>{{ $houseBill->consignor->country->name }}</p>
                    @endif
                </div>
            </td>
            <td width="33%" valign="top">
                <p class="section-title">Consignee</p>
                <div class="panel">
                    <p class="strong">{{ $houseBill->customer->name }}</p>
                    @if ($houseBill->customer->address)
                        <p>{{ $houseBill->customer->address }}</p>
                    @endif
                    @if ($houseBill->customer->country)
                        <p>{{ $houseBill->customer->country->name }}</p>
                    @endif
                </div>
            </td>
            <td width="33%" valign="top">
                <p class="section-title">Notify Party</p>
                <div class="panel">
                    <p class="strong">{{ $houseBill->notifyParty->name }}</p>
                    @if ($houseBill->notifyParty->address)
                        <p>{{ $houseBill->notifyParty->address }}</p>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="spacer-16"></div>

    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td width="50%" valign="top">
                <p class="section-title">Voyage</p>
                <div class="panel">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="muted">Master B/L</td>
                            <td class="text-right strong">
                                {{ $houseBill->masterBill->ocean_bill_of_lading }}
                            </td>
                        </tr>
                        @if ($houseBill->masterBill->vessel)
                            <tr>
                                <td class="muted">Vessel / Voyage</td>
                                <td class="text-right">
                                    {{ $houseBill->masterBill->vessel->name }}
                                    / {{ $houseBill->masterBill->vessel->voyage }}
                                </td>
                            </tr>
                        @endif
                        @if ($houseBill->masterBill->origin)
                            <tr>
                                <td class="muted">Origin</td>
                                <td class="text-right">
                                    {{ $houseBill->masterBill->origin->name }}
                                    ({{ $houseBill->masterBill->origin->code }})
                                </td>
                            </tr>
                        @endif
                        @if ($houseBill->masterBill->destination)
                            <tr>
                                <td class="muted">Destination</td>
                                <td class="text-right">
                                    {{ $houseBill->masterBill->destination->name }}
                                    ({{ $houseBill->masterBill->destination->code }})
                                </td>
                            </tr>
                        @endif
                    </table>
                </div>
            </td>
            <td width="50%" valign="top">
                <p class="section-title">Totals</p>
                <div class="panel">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="muted">Containers</td>
                            <td class="text-right strong">
                                {{ $totals['containers'] }}</td>
                        </tr>
                        <tr>
                            <td class="muted">Packages</td>
                            <td class="text-right strong">
                                {{ number_format($totals['packages'], 0) }}</td>
                        </tr>
                        <tr>
                            <td class="muted">Vehicles</td>
                            <td class="text-right strong">{{ $totals['vehicles'] }}
                            </td>
                        </tr>
                        <tr>
                            <td class="muted">Gross Weight</td>
                            <td class="text-right strong">
                                {{ number_format($totals['gross_weight'], 2) }} KG
                            </td>
                        </tr>
                        <tr>
                            <td class="muted">Volume</td>
                            <td class="text-right strong">
                                {{ number_format($totals['volume'], 2) }} M3</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table class="data" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th>Container</th>
                <th>Type</th>
                <th>Seal</th>
                <th class="text-center">Pkgs</th>
                <th class="text-right">Net KG</th>
                <th class="text-right">Gross KG</th>
                <th class="text-center">Vehicles</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($houseBill->containers as $row)
                <tr>
                    <td class="strong">{{ $row->container->container_number }}</td>
                    <td>{{ $row->container->type }}</td>
                    <td>{{ $row->seal }}</td>
                    <td class="text-center">{{ $row->packages }}</td>
                    <td class="text-right">
                        {{ number_format((float) $row->net_weight, 2) }}</td>
                    <td class="text-right">
                        {{ number_format((float) $row->gross_weight, 2) }}</td>
                    <td class="text-center">{{ $row->vehicles->count() }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center muted">No containers</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
