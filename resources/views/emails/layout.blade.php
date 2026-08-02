{{-- EDAMS branded email shell --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $title ?? config('app.name', 'EDAMS') }}</title>
</head>
<body style="margin:0;padding:0;background:#eef5fb;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;-webkit-font-smoothing:antialiased;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef5fb;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 8px 30px rgba(14,47,69,0.10);">
                    {{-- Header --}}
                    <tr>
                        <td style="background:linear-gradient(135deg,#0e2f45 0%,#154360 55%,#1b4f72 100%);padding:28px 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <table role="presentation" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="width:40px;height:40px;background:rgba(255,255,255,0.15);border-radius:10px;text-align:center;vertical-align:middle;color:#ffffff;font-weight:700;font-size:14px;letter-spacing:0.5px;">
                                                    EH
                                                </td>
                                                <td style="padding-left:12px;">
                                                    <div style="color:#ffffff;font-size:20px;font-weight:700;letter-spacing:0.4px;line-height:1.2;">EDAMS</div>
                                                    <div style="color:rgba(255,255,255,0.75);font-size:12px;margin-top:2px;">Enterprise Document Archiving</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Accent bar --}}
                    <tr>
                        <td style="height:4px;background:linear-gradient(90deg,#06b6d4,#22c55e,#f59e0b);"></td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:36px 32px 28px;color:#334155;">
                            @yield('content')
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:0 32px 28px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #e2e8f0;">
                                <tr>
                                    <td style="padding-top:20px;text-align:center;">
                                        <p style="margin:0 0 6px;font-size:12px;color:#64748b;">
                                            Softcell Solution Limited · Secure records. Trusted access.
                                        </p>
                                        <p style="margin:0;font-size:11px;color:#94a3b8;">
                                            This is an automated message from {{ config('app.name', 'EDAMS') }}. Please do not reply.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <p style="margin:18px 0 0;font-size:11px;color:#94a3b8;text-align:center;">
                    © {{ date('Y') }} Softcell Solution Limited. All rights reserved.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
