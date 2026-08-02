@extends('emails.layout', ['title' => 'Email change verification'])

@section('content')
    <p style="margin:0 0 8px;font-size:13px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:#f59e0b;">
        Security verification
    </p>
    <h1 style="margin:0 0 16px;font-size:24px;line-height:1.3;color:#0e2f45;font-weight:700;">
        Confirm your email change
    </h1>
    <p style="margin:0 0 10px;font-size:15px;line-height:1.65;color:#475569;">
        Hello{{ ! empty($name) ? ', '.$name : '' }},
    </p>
    <p style="margin:0 0 22px;font-size:15px;line-height:1.65;color:#475569;">
        We received a request to change your EDAMS account email to
        <strong style="color:#0e2f45;">{{ $newEmail }}</strong>.
        Use the one-time code below to confirm. This code was sent to your
        <strong>current</strong> email address for security.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 22px;">
        <tr>
            <td align="center" style="background:linear-gradient(180deg,#f8fafc,#eef5fb);border:1px dashed #94a3b8;border-radius:14px;padding:26px 16px;">
                <p style="margin:0 0 10px;font-size:12px;font-weight:600;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;">
                    Your OTP code
                </p>
                <p style="margin:0;font-size:36px;font-weight:700;letter-spacing:0.35em;color:#0e2f45;font-family:Consolas,Monaco,'Courier New',monospace;">
                    {{ $otpCode }}
                </p>
                <p style="margin:14px 0 0;font-size:13px;color:#64748b;">
                    Expires in <strong style="color:#b45309;">{{ $expiresInMinutes }} minutes</strong>
                </p>
            </td>
        </tr>
    </table>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 22px;">
        <tr>
            <td style="border-radius:10px;background:#154360;">
                <a href="{{ $profileUrl }}" style="display:inline-block;padding:12px 22px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:600;">
                    Open profile →
                </a>
            </td>
        </tr>
    </table>

    <div style="padding:14px 16px;background:#fef2f2;border-left:4px solid #ef4444;border-radius:0 8px 8px 0;">
        <p style="margin:0;font-size:13px;line-height:1.55;color:#b91c1c;">
            If you did not request this change, ignore this email. Your account email will stay unchanged.
        </p>
    </div>
@endsection
