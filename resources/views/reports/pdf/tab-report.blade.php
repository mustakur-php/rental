<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * {
            font-family: 'cairo', sans-serif;
            box-sizing: border-box;
        }
        body {
            font-size: 11px;
            color: #1e293b;
            margin: 0;
            padding: 20px;
            direction: rtl;
            text-align: right;
        }
        h1 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .meta {
            font-size: 9px;
            color: #64748b;
            margin-bottom: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        th {
            background: #0f172a;
            color: #ffffff;
            padding: 8px 10px;
            font-size: 10px;
            border: 1px solid #0f172a;
            text-align: right;
        }
        td {
            padding: 7px 10px;
            border: 1px solid #e2e8f0;
            font-size: 10px;
            text-align: right;
        }
        tr:nth-child(even) td {
            background: #f8fafc;
        }
        .footer {
            margin-top: 20px;
            font-size: 9px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">
        تاريخ التصدير: {{ now()->format('Y/m/d H:i') }}
        @if(($filters['date_from'] ?? null) || ($filters['date_to'] ?? null))
            &nbsp;·&nbsp; الفترة: {{ $filters['date_from'] ?? '—' }} إلى {{ $filters['date_to'] ?? '—' }}
        @endif
    </div>

    <table>
        <thead>
            <tr>
                @foreach($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>
                            @if(is_numeric($cell) && !str_contains((string)$cell, '%') && !str_contains((string)$cell, '/'))
                                {{ number_format((float)$cell, 2) }}
                            @else
                                {{ $cell }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">Rental ERP · {{ now()->format('Y') }}</div>
</body>
</html>
