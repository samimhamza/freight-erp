<x-mail::message>
    # Arrival Notice - {{ $houseBill->house_bill }}

    Dear {{ $customer->name }},

    We would like to notify you that your shipment under House Bill
    **{{ $houseBill->house_bill }}** (Master B/L:
    **{{ $masterBill->ocean_bill_of_lading }}**) has arrived or is scheduled to
    arrive at
    **{{ $masterBill->destination?->name ?? ($masterBill->portOfDischarge?->name ?? 'destination port') }}**.

    ### Shipment Overview:
    - **House Bill of Lading:** {{ $houseBill->house_bill }}
    - **Master Bill of Lading:** {{ $masterBill->ocean_bill_of_lading }}
    @if ($masterBill->console)
        - **Consol Reference:** {{ $masterBill->console }}
    @endif
    @if ($masterBill->carrier)
        - **Carrier:** {{ $masterBill->carrier->name }}
    @endif
    @if ($masterBill->vessel)
        - **Vessel / Voyage:** {{ $masterBill->vessel->name }} /
        {{ $masterBill->vessel->voyage }}
    @endif
    @if ($masterBill->origin)
        - **Port of Loading:** {{ $masterBill->origin->name }}
        ({{ $masterBill->origin->code }})
    @endif
    @php
        $discharge = $masterBill->portOfDischarge ?? $masterBill->destination;
    @endphp
    @if ($discharge)
        - **Port of Discharge:** {{ $discharge->name }}
        ({{ $discharge->code }})
    @endif
    @if ($masterBill->ata)
        - **Actual Arrival Date (ATA):**
        {{ $masterBill->ata->format('d M Y') }}
    @elseif ($masterBill->eta)
        - **Estimated Arrival Date (ETA):**
        {{ $masterBill->eta->format('d M Y') }}
    @endif
    - **Total Containers:** {{ $houseBill->containers->count() }}

    Please find attached the official **Arrival Notice** PDF detailing your
    containers, weights, packages, and collection instructions.

    Please arrange the collection of original shipping documents and settlement
    of any outstanding clearance or freight charges prior to cargo release.

    @if ($company['phone'] || $company['email'])
        If you require any assistance with customs clearance or inland delivery,
        please reach out to our team at
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

    Warm regards,<br>
    {{ $company['name'] }}
</x-mail::message>
