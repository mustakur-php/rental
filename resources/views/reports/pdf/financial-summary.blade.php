<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>التقرير المالي</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; direction: rtl; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; font-size: 12px; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>التقرير المالي</h1>
    <table>
        <tr>
            <th>البند</th>
            <th>القيمة</th>
        </tr>
        <tr>
            <td>إجمالي المستحق</td>
            <td>{{ number_format($income['required'], 2) }}</td>
        </tr>
        <tr>
            <td>إجمالي المدفوع</td>
            <td>{{ number_format($income['paid'], 2) }}</td>
        </tr>
        <tr>
            <td>المتبقي</td>
            <td>{{ number_format($income['remaining'], 2) }}</td>
        </tr>
        <tr>
            <td>نسبة التحصيل</td>
            <td>{{ $income['collection_rate'] }}%</td>
        </tr>
    </table>
</body>
</html>
