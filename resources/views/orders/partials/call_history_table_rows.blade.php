@forelse ($calls as $call)
    <tr>
        <td>{{ $call->user->name ?? ($call->sip_extension ?? 'N/A') }}</td>
        <td>{{ $call->start_time ? $call->start_time->format('d/m/Y H:i') : ($call->call_time ? $call->call_time->format('d/m/Y H:i') : 'N/A') }}</td>
        <td>{{ $call->duration_seconds ?? ($call->call_duration ?? '0') }}s</td>
        <td>{{ $call->notes ?? '-' }}</td>
        <td>
            @if($call->recording_url)
                <a href="{{ $call->recording_url }}" target="_blank">Nghe</a>
            @else
                -
            @endif
        </td>
    </tr>
@empty
    <tr><td colspan="5" class="text-center">Chưa có cuộc gọi nào.</td></tr>
@endforelse
