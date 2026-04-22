<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $messageRecipient->rendered_subject }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #0f172a; line-height: 1.6; padding: 24px; background: #f8fafc;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 20px; padding: 32px; border: 1px solid #e2e8f0;">
        <p style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.2em; color: #64748b;">{{ config('app.name') }}</p>
        <h1 style="font-size: 24px; margin: 12px 0 24px;">{{ $messageRecipient->rendered_subject }}</h1>
        <div style="white-space: pre-line; font-size: 15px; color: #334155;">{{ $messageRecipient->rendered_body }}</div>
        <p style="margin-top: 32px;">
            <a href="{{ route('messages.show', $messageRecipient->message->thread_root_id ?: $messageRecipient->message_id) }}" style="display: inline-block; background: #0f172a; color: #ffffff; text-decoration: none; padding: 12px 20px; border-radius: 9999px; font-weight: 600;">
                Open in Message Centre
            </a>
        </p>
    </div>
</body>
</html>
