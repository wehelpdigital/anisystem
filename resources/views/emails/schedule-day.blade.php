<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $dateLabel }} — {{ $scheduleTitle }}</title>
</head>
<body style="margin:0;padding:0;background:#f5f6f8;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <div style="max-width:560px;margin:0 auto;padding:24px 16px;">
        <div style="background:#4a7c2a;color:#fff;border-radius:12px 12px 0 0;padding:18px 20px;">
            <div style="font-size:12px;letter-spacing:.05em;text-transform:uppercase;opacity:.85;">{{ $scheduleTitle }}</div>
            <div style="font-size:20px;font-weight:bold;margin-top:2px;">{{ $dateLabel }}</div>
        </div>
        <div style="background:#ffffff;border-radius:0 0 12px 12px;padding:20px;border:1px solid #e5e7eb;border-top:none;">
            <p style="margin:0 0 16px;font-size:15px;">Hi {{ $workerName }}, here's the plan:</p>

            @if(count($activities))
                @foreach($activities as $a)
                    <div style="border-left:3px solid #4a7c2a;background:#f8faf5;border-radius:8px;padding:12px 14px;margin-bottom:10px;">
                        <div style="font-weight:bold;font-size:15px;">{{ $a['title'] }}</div>
                        @if(!empty($a['tags']))
                            <div style="font-size:12px;color:#6b7280;margin-top:3px;">{{ $a['tags'] }}</div>
                        @endif
                        @if(!empty($a['description']))
                            <div style="font-size:13px;color:#374151;margin-top:6px;">{!! $a['description'] !!}</div>
                        @endif
                    </div>
                @endforeach
            @else
                <p style="margin:0;font-size:14px;color:#6b7280;">Nothing scheduled — enjoy the rest day.</p>
            @endif

            @if($publicUrl)
                <p style="margin:18px 0 0;font-size:13px;">
                    <a href="{{ $publicUrl }}" style="color:#4a7c2a;font-weight:bold;text-decoration:none;">View the full plan online →</a>
                </p>
            @endif
        </div>
        <p style="text-align:center;font-size:11px;color:#9ca3af;margin:16px 0 0;">Sent with AniSystem</p>
    </div>
</body>
</html>
