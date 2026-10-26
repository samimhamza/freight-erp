<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? config('company.name') }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.45;
            margin: 0;
            padding: 0;
        }
        .page { width: 100%; }
        .brand-name {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
            margin: 0;
        }
        .brand-meta {
            font-size: 10px;
            color: #64748b;
            margin-top: 4px;
        }
        .doc-title {
            font-size: 22px;
            font-weight: bold;
            color: #0f766e;
            text-align: right;
            margin: 0;
        }
        .doc-subtitle {
            font-size: 10px;
            color: #64748b;
            text-align: right;
            margin-top: 4px;
        }
        .section-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
            margin: 0 0 6px 0;
        }
        .panel {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 10px 12px;
        }
        .panel p { margin: 0 0 3px 0; }
        .muted { color: #64748b; }
        .strong { font-weight: bold; color: #0f172a; }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }
        table.data th {
            background: #0f766e;
            color: #ffffff;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 8px 10px;
            text-align: left;
        }
        table.data td {
            border-bottom: 1px solid #e2e8f0;
            padding: 8px 10px;
            vertical-align: top;
        }
        table.data tr:nth-child(even) td { background: #f8fafc; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals-table { width: 100%; margin-top: 14px; }
        .totals-table td { padding: 4px 0; }
        .totals-table .label { color: #64748b; }
        .totals-table .amount { text-align: right; font-weight: bold; }
        .totals-table .grand td {
            border-top: 2px solid #0f766e;
            padding-top: 8px;
            font-size: 13px;
            color: #0f766e;
        }
        .footer {
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            font-size: 9px;
            color: #94a3b8;
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: bold;
            background: #e2e8f0;
            color: #334155;
        }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #e0f2fe; color: #075985; }
        .spacer-8 { height: 8px; }
        .spacer-16 { height: 16px; }
    </style>
</head>
<body>
    <div class="page">
        @yield('content')
        <div class="footer">
            {{ config('company.name') }}
            @if (config('company.trn'))
                &mdash; TRN: {{ config('company.trn') }}
            @endif
            @if (config('company.phone'))
                &mdash; {{ config('company.phone') }}
            @endif
            @if (config('company.email'))
                &mdash; {{ config('company.email') }}
            @endif
        </div>
    </div>
</body>
</html>
