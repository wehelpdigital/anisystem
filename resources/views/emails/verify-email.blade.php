{{-- The confirmation mail. Inline styles only — mail clients strip
     stylesheets — and the button's link is repeated as bare text underneath
     for the clients that flatten buttons. --}}
<div style="margin:0;padding:24px 12px;background:#f3f4f6;font-family:'Segoe UI',Arial,sans-serif;">
    <div style="max-width:520px;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e5e7eb;">
        <div style="background:#2f5219;padding:22px 28px;">
            <span style="font-size:22px;font-weight:800;color:#ffffff;letter-spacing:.3px;">🌱 anee.io</span>
        </div>
        <div style="padding:28px;">
            <h1 style="margin:0 0 12px;font-size:20px;color:#14210c;">Confirm your email, {{ $firstName }}</h1>
            <p style="margin:0 0 18px;font-size:15px;line-height:1.6;color:#374151;">
                Salamat for signing up! One tap and your free anee.io account is open —
                your cropping schedules, the activities board, and Anee the AI technician
                are waiting on the other side.
            </p>
            <div style="text-align:center;margin:26px 0;">
                <a href="{{ $link }}"
                   style="display:inline-block;background:#f2c94c;color:#14210c;font-weight:800;font-size:16px;padding:13px 32px;border-radius:999px;text-decoration:none;">
                    Confirm my email
                </a>
            </div>
            <p style="margin:0 0 6px;font-size:12px;color:#6b7280;">If the button does nothing, copy this link into your browser:</p>
            <p style="margin:0 0 18px;font-size:12px;word-break:break-all;"><a href="{{ $link }}" style="color:#4a7c2a;">{{ $link }}</a></p>
            <p style="margin:0;font-size:12px;color:#9ca3af;">
                The link works for 3 days. If you didn't create an anee.io account, you can safely ignore this email.
            </p>
        </div>
        <div style="padding:16px 28px;background:#f9fafb;border-top:1px solid #f3f4f6;">
            <p style="margin:0;font-size:11px;color:#9ca3af;">© {{ date('Y') }} anee.io — helping Filipino farmers reach maximum yield and income.</p>
        </div>
    </div>
</div>
