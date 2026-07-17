@props(['checks'])
<div class="mini-title">Wareneingangskontrolle</div>
<table style="margin-bottom:4px;">
    <tr>
        @foreach (array_chunk($checks, (int) ceil(count($checks) / 2)) as $col)
            <td style="width:50%; border:0; padding:0 8px 0 0;">
                @foreach ($col as $check)
                    <div style="padding:1.5px 0;">
                        <span class="{{ $check['ok'] ? 'ok' : 'bad' }}">{{ $check['ok'] ? '✓' : '✗' }}</span>
                        <span class="{{ $check['ok'] ? '' : 'bad' }}">{{ $check['label'] }}</span>
                    </div>
                @endforeach
            </td>
        @endforeach
    </tr>
</table>
