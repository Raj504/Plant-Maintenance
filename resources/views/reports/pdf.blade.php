<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>{{ $title }}</title>
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #222; }
h1 { font-size: 15px; margin: 0; } .meta { color: #666; margin-bottom: 8px; }
table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid #bbb; padding: 3px 4px; } th { background: #eef2f7; text-align: left; }
td.num { text-align: right; } tfoot td { font-weight: bold; background: #f7f7f7; }
.footer { position: fixed; bottom: 0; font-size: 8px; color: #888; }
</style></head>
<body>
<h1>{{ setting('pm.company_name') }} — {{ $title }}</h1>
<div class="meta">Period {{ show_date($filters['from']) }} to {{ show_date($filters['to']) }} · Generated {{ now()->format('d M Y H:i') }} by {{ auth()->user()->name }}</div>
<table>
<thead><tr>@foreach($columns as $label)<th>{{ $label }}</th>@endforeach</tr></thead>
<tbody>
@forelse($rows as $row)<tr>@foreach($columns as $key => $label)@php $v = $row[$key] ?? ''; @endphp<td class="{{ is_numeric($v) ? 'num' : '' }}">{{ is_float($v) || (is_numeric($v) && str_contains((string) $v, '.')) ? number_format((float) $v, 2) : $v }}</td>@endforeach</tr>
@empty<tr><td colspan="{{ count($columns) }}">No data</td></tr>@endforelse
</tbody>
@if($totals)<tfoot>@foreach($totals as $row)<tr>@foreach($columns as $key => $label)@php $v = $row[$key] ?? ''; @endphp<td class="{{ is_numeric($v) ? 'num' : '' }}">{{ is_float($v) || (is_numeric($v) && str_contains((string) $v, '.')) ? number_format((float) $v, 2) : $v }}</td>@endforeach</tr>@endforeach</tfoot>@endif
</table>
<div class="footer">Plant Maintenance & Operations Management System</div>
</body></html>
