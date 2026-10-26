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
                <p class="doc-title">ARRIVAL NOTICE</p>
                <p class="doc-subtitle">House B/L:
                    <strong>{{ $houseBill->house_bill }}</strong>
                </p>
                <p class="doc-subtitle">Master B/L:
                    <strong>{{ $masterBill->ocean_bill_of_lading }}</strong>
                </p>
                @if ($masterBill->console)
                    <p class="doc-subtitle">Consol Ref:
                        <strong>{{ $masterBill->console }}</strong>
                    </p>
                @endif
                <p class="doc-subtitle">Date:
                    {{ ($noticeDate ?? now())->format('d M Y') }}</p>
            </td>
        </tr>
    </table>

    <div class="spacer-16"></div>

    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td width="33%" valign="top">
                <p class="section-title">Shipper / Consignor</p>
                <div class="panel">
                    <p class="strong">{{ $consignor->name }}</p>
                    @if ($consignor->address)
                        <p>{{ $consignor->address }}</p>
                    @endif
                    @if ($consignor->country)
                        <p>{{ $consignor->country->name }}</p>
                    @endif
                    @if ($consignor->phone)
                        <p class="muted">Tel: {{ $consignor->phone }}</p>
                    @endif
                </div>
            </td>
            <td width="33%" valign="top" style="padding: 0 6px;">
                <p class="section-title">Consignee (Customer)</p>
                <div class="panel">
                    <p class="strong">{{ $customer->name }}</p>
                    @if ($customer->address)
                        <p>{{ $customer->address }}</p>
                    @endif
                    @if ($customer->country)
                        <p>{{ $customer->country->name }}</p>
                    @endif
                    @if ($customer->phone)
                        <p class="muted">Tel: {{ $customer->phone }}</p>
                    @endif
                    @if ($customer->email)
                        <p class="muted">{{ $customer->email }}</p>
                    @endif
                </div>
            </td>
            <td width="33%" valign="top">
                <p class="section-title">Notify Party</p>
                <div class="panel">
                    @if ($notifyParty)
                        <p class="strong">{{ $notifyParty->name }}</p>
                        @if ($notifyParty->address)
                            <p>{{ $notifyParty->address }}</p>
                        @endif
                        @if ($notifyParty->country)
                            <p>{{ $notifyParty->country->name }}</p>
                        @endif
                        @if ($notifyParty->phone)
                            <p class="muted">Tel: {{ $notifyParty->phone }}</p>
                        @endif
                    @else
                        <p class="muted">SAME AS CONSIGNEE</p>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="spacer-16"></div>

    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td width="50%" valign="top" style="padding-right: 6px;">
                <p class="section-title">Routing &amp; Transport</p>
                <div class="panel">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="muted">Master B/L (Ocean)</td>
                            <td class="text-right strong">
                                {{ $masterBill->ocean_bill_of_lading }}</td>
                        </tr>
                        @if ($masterBill->carrier)
                            <tr>
                                <td class="muted">Carrier / Line</td>
                                <td class="text-right strong">
                                    {{ $masterBill->carrier->name }}</td>
                            </tr>
                        @endif
                        @if ($masterBill->vessel)
                            <tr>
                                <td class="muted">Vessel / Voyage</td>
                                <td class="text-right">
                                    {{ $masterBill->vessel->name }} /
                                    {{ $masterBill->vessel->voyage }}
                                    @if ($masterBill->vessel->imo)
                                        <span class="muted">(IMO:
                                            {{ $masterBill->vessel->imo }})</span>
                                    @endif
                                </td>
                            </tr>
                        @endif
                        @if ($masterBill->origin)
                            <tr>
                                <td class="muted">Port of Loading (Origin)</td>
                                <td class="text-right">
                                    {{ $masterBill->origin->name }}
                                    ({{ $masterBill->origin->code }})</td>
                            </tr>
                        @endif
                        @php
                            $discharge =
                                $masterBill->portOfDischarge ??
                                $masterBill->destination;
                        @endphp
                        @if ($discharge)
                            <tr>
                                <td class="muted">Port of Discharge</td>
                                <td class="text-right">{{ $discharge->name }}
                                    ({{ $discharge->code }})</td>
                            </tr>
                        @endif
                        @if (
                            $masterBill->destination &&
                                (!$masterBill->portOfDischarge ||
                                    $masterBill->destination->id !== $masterBill->portOfDischarge->id))
                            <tr>
                                <td class="muted">Final Destination</td>
                                <td class="text-right">
                                    {{ $masterBill->destination->name }}
                                    ({{ $masterBill->destination->code }})</td>
                            </tr>
                        @endif
                        @if ($masterBill->customs_entry_number)
                            <tr>
                                <td class="muted">Customs Entry No</td>
                                <td class="text-right">
                                    {{ $masterBill->customs_entry_number }}</td>
                            </tr>
                        @endif
                    </table>
                </div>
            </td>
            <td width="50%" valign="top" style="padding-left: 6px;">
                <p class="section-title">Schedule &amp; Cargo Summary</p>
                <div class="panel">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        @if ($masterBill->etd)
                            <tr>
                                <td class="muted">ETD (Departure)</td>
                                <td class="text-right">
                                    {{ $masterBill->etd->format('d M Y') }}</td>
                            </tr>
                        @endif
                        @if ($masterBill->eta)
                            <tr>
                                <td class="muted">ETA (Estimated Arrival)</td>
                                <td class="text-right">
                                    {{ $masterBill->eta->format('d M Y') }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="muted strong">ATA (Actual Arrival)</td>
                            <td class="text-right strong" style="color: #0f766e;">
                                {{ $masterBill->ata ? $masterBill->ata->format('d M Y') : 'Pending Arrival' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="muted">Total Containers</td>
                            <td class="text-right strong">
                                {{ $totals['containers'] }}</td>
                        </tr>
                        <tr>
                            <td class="muted">Total Packages</td>
                            <td class="text-right strong">
                                {{ number_format($totals['packages']) }} PKG</td>
                        </tr>
                        <tr>
                            <td class="muted">Gross Weight</td>
                            <td class="text-right strong">
                                {{ number_format($totals['gross_weight'], 2) }} KG
                            </td>
                        </tr>
                        @if ($totals['volume'] > 0)
                            <tr>
                                <td class="muted">Total Volume</td>
                                <td class="text-right strong">
                                    {{ number_format($totals['volume'], 2) }} M3
                                </td>
                            </tr>
                        @endif
                        @if ($totals['vehicles'] > 0)
                            <tr>
                                <td class="muted">Total Vehicles</td>
                                <td class="text-right strong">
                                    {{ $totals['vehicles'] }} Units</td>
                            </tr>
                        @endif
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="spacer-16"></div>

    <p class="section-title">Container Breakdown</p>
    <table class="data" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th width="5%" class="text-center">#</th>
                <th width="20%">Container No</th>
                <th width="12%">Type</th>
                <th width="15%">Seal</th>
                <th width="10%" class="text-center">Pkgs</th>
                <th width="12%" class="text-right">Net KG</th>
                <th width="14%" class="text-right">Gross KG</th>
                <th width="12%" class="text-right">Volume</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($containers as $index => $row)
                <tr>
                    <td class="text-center muted">{{ $index + 1 }}</td>
                    <td class="strong">{{ $row->container->container_number }}
                    </td>
                    <td>{{ $row->container->type?->value ?? $row->container->type }}
                    </td>
                    <td>{{ $row->seal }}</td>
                    <td class="text-center">{{ $row->packages }}</td>
                    <td class="text-right">
                        {{ $row->net_weight ? number_format((float) $row->net_weight, 2) : '—' }}
                    </td>
                    <td class="text-right">
                        {{ number_format((float) $row->gross_weight, 2) }}</td>
                    <td class="text-right">
                        {{ $row->container?->volume ? number_format((float) $row->container->volume, 2) . ' M3' : '—' }}
                    </td>
                </tr>
                @if ($row->descriptions_of_goods)
                    <tr>
                        <td></td>
                        <td colspan="7"
                            style="font-size: 10px; color: #475569; padding-top: 2px; padding-bottom: 6px;">
                            <strong>Goods:</strong>
                            {{ $row->descriptions_of_goods }}
                        </td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="8" class="text-center muted">No containers
                        recorded for this house bill</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @php
        $hasVehicles = $containers->some(fn($c) => $c->vehicles->isNotEmpty());
    @endphp

    @if ($hasVehicles)
        <div class="spacer-16"></div>
        <p class="section-title">Vehicles Breakdown</p>
        <table class="data" cellpadding="0" cellspacing="0">
            <thead>
                <tr>
                    <th width="5%" class="text-center">#</th>
                    <th width="22%">Container</th>
                    <th width="28%">VIN / Chassis</th>
                    <th width="25%">Make / Model</th>
                    <th width="10%" class="text-center">Year</th>
                    <th width="10%">Color</th>
                </tr>
            </thead>
            <tbody>
                @php $vIndex = 1; @endphp
                @foreach ($containers as $cRow)
                    @foreach ($cRow->vehicles as $vehicle)
                        <tr>
                            <td class="text-center muted">{{ $vIndex++ }}</td>
                            <td>{{ $cRow->container->container_number }}</td>
                            <td class="strong">{{ $vehicle->vin }}</td>
                            <td>{{ $vehicle->make }} {{ $vehicle->model }}</td>
                            <td class="text-center">{{ $vehicle->year }}</td>
                            <td>{{ $vehicle->color ?? '—' }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="spacer-16"></div>

    <div class="panel">
        <p class="section-title" style="margin-bottom: 4px;">Important Notice
            &amp; Instructions</p>
        <ul
            style="margin: 0; padding-left: 16px; font-size: 10px; color: #475569; line-height: 1.5;">
            <li>Please arrange the collection of original shipping documents and
                settlement of all outstanding freight and clearance charges prior to
                cargo release.</li>
            <li>Demurrage and container detention charges will be applied strictly
                as per carrier tariff upon expiration of the standard free days.
            </li>
            <li>Cargo must be cleared and collected from the port within the
                permissible free time to avoid terminal storage penalties.</li>
            <li>{{ $company['name'] }} is ready to assist you with customs
                clearance, port handling, and inland transportation to your final
                destination.</li>
        </ul>
    </div>

    <div style="margin-top: 18px;">
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td width="60%" valign="top">
                    <p style="margin: 0 0 4px 0;">Yours sincerely,</p>
                    <p class="strong"
                        style="margin: 0; font-size: 12px; color: #0f766e;">
                        {{ $company['name'] }}</p>
                    <p class="muted" style="margin: 2px 0 0 0; font-size: 10px;">
                        Operations &amp; Customer Support Department</p>
                </td>
                <td width="40%" valign="bottom" class="text-right">
                    <p class="muted" style="margin: 0; font-size: 9px;">Generated
                        on {{ now()->format('d M Y H:i') }}</p>
                </td>
            </tr>
        </table>
    </div>
@endsection
