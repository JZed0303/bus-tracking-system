<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $report['title'] }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #111;
        }
        h1 {
            margin: 0 0 4px;
            font-size: 22px;
        }
        .meta {
            margin-bottom: 14px;
            color: #444;
            font-size: 12px;
        }
        .summary {
            margin-bottom: 14px;
            font-size: 12px;
        }
        .summary span {
            display: inline-block;
            margin-right: 14px;
            margin-bottom: 6px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        th, td {
            border: 1px solid #666;
            padding: 6px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #f4f4f4;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 12px;">
        <button onclick="window.print()">Print / Save as PDF</button>
        <button onclick="window.close()">Close</button>
    </div>

    <h1>{{ $report['title'] }}</h1>
    <div class="meta">
        Generated at: {{ now()->format('Y-m-d H:i:s') }}<br>
        Date range: {{ $filters['date_from']->toDateString() }} to {{ $filters['date_to']->toDateString() }}
    </div>

    <div class="summary">
        @foreach($report['summary'] as $item)
            <span><strong>{{ $item['label'] }}:</strong> {{ $item['value'] }}</span>
        @endforeach
    </div>

    <table>
        <thead>
            <tr>
                @foreach($report['columns'] as $column)
                    <th>{{ $column['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($report['rows'] as $row)
                <tr>
                    @foreach($report['columns'] as $column)
                        <td>{{ $row[$column['key']] ?? '' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($report['columns']) }}">No data found for the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if(!empty($autoPrint))
        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>
    @endif
</body>
</html>
