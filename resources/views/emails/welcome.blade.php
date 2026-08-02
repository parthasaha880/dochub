@extends('emails.layout', ['title' => 'Welcome to EDAMS'])

@section('content')
    <p style="margin:0 0 8px;font-size:13px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:#0ea5e9;">
        Welcome aboard
    </p>
    <h1 style="margin:0 0 16px;font-size:24px;line-height:1.3;color:#0e2f45;font-weight:700;">
        Hello{{ ! empty($name) ? ', '.$name : '' }}
    </h1>
    <p style="margin:0 0 18px;font-size:15px;line-height:1.65;color:#475569;">
        Your EDAMS account is ready. You now have secure access to enterprise document archiving,
        approvals, search, and records management.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;margin:0 0 22px;">
        <tr>
            <td style="padding:18px 20px;">
                <p style="margin:0 0 10px;font-size:12px;font-weight:600;letter-spacing:0.06em;text-transform:uppercase;color:#64748b;">
                    Your sign-in details
                </p>
                <p style="margin:0 0 6px;font-size:14px;color:#334155;">
                    <strong style="color:#0e2f45;">Email:</strong> {{ $email }}
                </p>
                @if(! empty($temporaryPassword))
                    <p style="margin:0;font-size:14px;color:#334155;">
                        <strong style="color:#0e2f45;">Temporary password:</strong>
                        <span style="display:inline-block;margin-left:4px;padding:4px 10px;background:#0e2f45;color:#ffffff;border-radius:6px;font-family:Consolas,Monaco,monospace;font-size:13px;letter-spacing:0.04em;">
                            {{ $temporaryPassword }}
                        </span>
                    </p>
                @endif
            </td>
        </tr>
    </table>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
        <tr>
            <td style="border-radius:10px;background:#154360;">
                <a href="{{ $loginUrl }}" style="display:inline-block;padding:12px 22px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:600;">
                    Sign in to EDAMS →
                </a>
            </td>
        </tr>
    </table>

    <div style="padding:14px 16px;background:#ecfeff;border-left:4px solid #06b6d4;border-radius:0 8px 8px 0;margin:0 0 8px;">
        <p style="margin:0;font-size:13px;line-height:1.55;color:#0e7490;">
            For your security, change your password after the first sign-in and never share your credentials.
        </p>
    </div>
@endsection
