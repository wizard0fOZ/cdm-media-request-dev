# Notification System - Implementation Outline

## Overview

This document outlines the implementation plan for notifications in the CDM Media Request system:

1. **Email notifications** - Sent to requestors for confirmation, approval, and rejection
2. **Telegram notifications** - Sent to admin group/channel when new requests are submitted

---

## Environment

| Environment | Email Method |
|-------------|--------------|
| Development | PHPMailer with SMTP (or MailHog for testing) |
| Production  | PHPMailer with Serverfreak cPanel SMTP |

### SMTP Configuration (Serverfreak/cPanel)

```
Host: mail.divinemercy.my
Port: 465 (SSL) or 587 (TLS)
Username: media@divinemercy.my (to be created)
Password: [set in cPanel]
From Name: CDM Media Ministry
```

---

## Files to Create

```
apps/public-form/
├── includes/
│   ├── mailer.php              # PHPMailer setup & helper functions
│   └── telegram.php            # Telegram Bot API helper
├── templates/
│   └── emails/
│       ├── confirmation.php    # Request submitted template
│       ├── approved.php        # Request approved template
│       └── rejected.php        # Request rejected template
├── composer.json               # PHPMailer dependency
└── .env.example                # Environment variables template
```

---

## Files to Modify

| File | Changes |
|------|---------|
| `public/api/submit_request.php` | Add email send after successful submission |
| `admin/actions/approve.php` | Add email send after approval |
| `admin/actions/reject.php` | Add rejection reason field + email send |
| `admin/view.php` | Add rejection reason input to reject modal |
| `includes/config.php` | Load email configuration from env |
| `.env` | Add SMTP credentials |

---

## Email Templates

### 1. Confirmation Email (on submission)

**To:** Requestor email
**Subject:** `Request Received - {reference_no}`

**Content:**
- Greeting with requestor name
- Reference number (prominent)
- Summary of request:
  - Event name
  - Event date(s)
  - Services requested (AV/Media/Photo)
- What to expect next
- Contact info for questions
- Footer with church details

---

### 2. Approval Email

**To:** Requestor email
**Subject:** `Request Approved - {reference_no}`

**Content:**
- Greeting with requestor name
- Confirmation that request is approved
- Reference number
- Event details summary
- Next steps (e.g., "Our team will contact you for coordination")
- Contact info
- Footer

---

### 3. Rejection Email

**To:** Requestor email
**Subject:** `Request Update - {reference_no}`

**Content:**
- Greeting with requestor name
- Notification that request could not be approved
- Reference number
- Rejection reason (provided by admin)
- Invitation to resubmit or contact for clarification
- Contact info
- Footer

---

## Database Changes

### Option A: Add rejection reason to existing table

```sql
ALTER TABLE media_requests
ADD COLUMN rejection_reason TEXT NULL AFTER request_status;
```

### Option B: Store in audit_logs (no schema change)

- Use `after_json` field in audit_logs to store rejection reason
- Query audit_logs when displaying rejection details

**Recommendation:** Option A - simpler to query and display

---

## Telegram Notifications (Admin Alerts)

### Purpose

Send instant notifications to the media team's Telegram group when a new request is submitted. This allows quick visibility without checking the admin panel constantly.

### Setup Requirements

1. **Create a Telegram Bot**
   - Message [@BotFather](https://t.me/BotFather) on Telegram
   - Send `/newbot` and follow prompts
   - Save the **Bot Token** (looks like `123456789:ABCdefGHIjklMNOpqrsTUVwxyz`)

2. **Get Chat ID**
   - Create a Telegram group for the media team
   - Add the bot to the group
   - Send a message in the group
   - Visit `https://api.telegram.org/bot<TOKEN>/getUpdates`
   - Find the `chat.id` (negative number for groups, e.g., `-1001234567890`)

### Files to Create

```
apps/public-form/
├── includes/
│   └── telegram.php            # Telegram Bot API helper
```

### Implementation

**`includes/telegram.php`**

```php
<?php
declare(strict_types=1);

/**
 * Send a message to Telegram
 */
function sendTelegramMessage(string $message): bool {
    $botToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';
    $chatId = $_ENV['TELEGRAM_CHAT_ID'] ?? '';

    if (empty($botToken) || empty($chatId)) {
        return false;
    }

    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10
    ]);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode === 200;
}

/**
 * Get emoji for service type
 */
function getServiceEmoji(string $type): string {
    return match($type) {
        'media' => '🎨',
        'av' => '🎛',
        'photo' => '📸',
        default => '📋'
    };
}

/**
 * Format the main request notification message
 */
function formatMainRequestMessage(array $request, array $services): string {
    // Build service type string with emojis
    $typeEmojis = [];
    $typeLabels = [];
    foreach ($services as $service) {
        $typeEmojis[] = getServiceEmoji($service);
        $typeLabels[] = match($service) {
            'media' => 'Poster',
            'av' => 'AV',
            'photo' => 'Photo',
            default => $service
        };
    }
    $typeString = implode('', $typeEmojis) . ' ' . implode(' + ', $typeLabels);

    $msg = "📥 New Media Request\n";
    $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
    $msg .= "Type: {$typeString}\n\n";

    $msg .= "👤 Requestor Info\n";
    $msg .= "• Name: {$request['requestor_name']}\n";
    $msg .= "• Contact: {$request['contact_no']}\n";
    $msg .= "• Ministry: {$request['ministry']}\n\n";

    $msg .= "📅 Event Details\n";
    $msg .= "• Title: {$request['event_name']}\n";
    if (!empty($request['event_description'])) {
        $desc = mb_strlen($request['event_description']) > 100
            ? mb_substr($request['event_description'], 0, 100) . '...'
            : $request['event_description'];
        $msg .= "• Description: {$desc}\n";
    }
    $msg .= "• Date(s): {$request['formatted_dates']}\n";
    $msg .= "• Time(s): {$request['formatted_times']}\n";
    if (!empty($request['event_location_note'])) {
        $msg .= "• Venue: {$request['event_location_note']}\n";
    }

    $msg .= "\n━━━━━━━━━━━━━━━━━━━━\n";
    $msg .= "🔗 <a href=\"{$request['admin_url']}\">View in Admin Panel</a>";

    return $msg;
}

/**
 * Format AV details message
 */
function formatAVDetailsMessage(array $request, array $avDetails, array $equipment): string {
    $msg = "🎛 AV Support Details\n";
    $msg .= "━━━━━━━━━━━━━━━━━━━━\n";

    if (!empty($request['event_location_note'])) {
        $msg .= "• Venue: {$request['event_location_note']}\n\n";
    }

    if (!empty($equipment)) {
        $msg .= "🔧 Equipment Required\n\n";
        foreach ($equipment as $room => $items) {
            $msg .= "{$room}:\n";
            foreach ($items as $item) {
                $msg .= "  • {$item}\n";
            }
            $msg .= "\n";
        }
    }

    if (!empty($avDetails['note'])) {
        $msg .= "📝 Additional Requests:\n";
        $msg .= "{$avDetails['note']}\n\n";
    }

    $msg .= "🎭 Rehearsal:\n";
    if (!empty($avDetails['rehearsal_date'])) {
        $msg .= "{$avDetails['rehearsal_date']} {$avDetails['rehearsal_start_time']} - {$avDetails['rehearsal_end_time']}\n";
    } else {
        $msg .= "Not required\n";
    }

    $msg .= "\n━━━━━━━━━━━━━━━━━━━━\n";
    $msg .= "🔗 <a href=\"{$request['admin_url']}\">View in Admin Panel</a>";

    return $msg;
}

/**
 * Format Photo details message
 */
function formatPhotoDetailsMessage(array $request, array $photoDetails): string {
    $msg = "📸 Photography Details\n";
    $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
    $msg .= "• Date Needed: {$photoDetails['needed_date']}\n";
    $msg .= "• Time: {$photoDetails['start_time']} - {$photoDetails['end_time']}\n";

    if (!empty($photoDetails['note'])) {
        $msg .= "\n📝 Notes:\n";
        $msg .= "{$photoDetails['note']}\n";
    }

    $msg .= "\n━━━━━━━━━━━━━━━━━━━━\n";
    $msg .= "🔗 <a href=\"{$request['admin_url']}\">View in Admin Panel</a>";

    return $msg;
}

/**
 * Format Media/Poster details message
 */
function formatMediaDetailsMessage(array $request, array $mediaDetails, array $platforms): string {
    $msg = "🎨 Poster/Media Details\n";
    $msg .= "━━━━━━━━━━━━━━━━━━━━\n";

    if (!empty($platforms)) {
        $msg .= "• Platforms: " . implode(', ', $platforms) . "\n";
    }
    if (!empty($mediaDetails['promo_start_date'])) {
        $msg .= "• Promo Period: {$mediaDetails['promo_start_date']} - {$mediaDetails['promo_end_date']}\n";
    }

    if (!empty($mediaDetails['description'])) {
        $desc = mb_strlen($mediaDetails['description']) > 200
            ? mb_substr($mediaDetails['description'], 0, 200) . '...'
            : $mediaDetails['description'];
        $msg .= "\n📝 Details:\n{$desc}\n";
    }

    if (!empty($mediaDetails['caption_details'])) {
        $msg .= "\n✏️ Caption:\n{$mediaDetails['caption_details']}\n";
    }

    $msg .= "\n━━━━━━━━━━━━━━━━━━━━\n";
    $msg .= "🔗 <a href=\"{$request['admin_url']}\">View in Admin Panel</a>";

    return $msg;
}

/**
 * Send all notification messages for a new request
 */
function sendNewRequestNotification(array $request, array $services, array $details): void {
    // Always send main message
    $mainMsg = formatMainRequestMessage($request, $services);
    sendTelegramMessage($mainMsg);

    // Send service-specific messages
    if (in_array('av', $services) && !empty($details['av'])) {
        $avMsg = formatAVDetailsMessage($request, $details['av'], $details['equipment'] ?? []);
        sendTelegramMessage($avMsg);
    }

    if (in_array('media', $services) && !empty($details['media'])) {
        $mediaMsg = formatMediaDetailsMessage($request, $details['media'], $details['platforms'] ?? []);
        sendTelegramMessage($mediaMsg);
    }

    if (in_array('photo', $services) && !empty($details['photo'])) {
        $photoMsg = formatPhotoDetailsMessage($request, $details['photo']);
        sendTelegramMessage($photoMsg);
    }
}
```

### Telegram Message Format

**Message 1: Main Request Summary**

```
📥 New Media Request
━━━━━━━━━━━━━━━━━━━━
Type: 🎨🎛 Poster + AV

👤 Requestor Info
• Name: John Tan
• Contact: 012-3456789
• Ministry: Catechist Ministry

📅 Event Details
• Title: The Sower & The Seed
• Description: An essential formation for catechists
• Date(s): 7th March, 25th April & 9th May 2026
• Time(s): 9am to 4pm
• Venue: St. Faustina Hall

🎨 Poster Details
• Details: We will have 100 catechists as participants...

━━━━━━━━━━━━━━━━━━━━
🔗 View in Admin Panel
```

**Message 2: AV Support Details (if AV requested)**

```
🎛 AV Support Details
━━━━━━━━━━━━━━━━━━━━
• Venue: St. Faustina Hall

🔧 Equipment Required

St. Faustina Hall:
  • Projector (through HDMI)
  • Audio (through 3.5mm audio jack)
  • Wireless Mic 1
  • Wireless Mic 2
  • Wired Mic 1
  • Wired Mic 2
  • Mic Stand 1
  • Mic Stand 2

📝 Additional Requests:
Could we have an AV person on stand by during the event?

🎭 Rehearsal:
Not required

━━━━━━━━━━━━━━━━━━━━
🔗 View in Admin Panel
```

**Message 3: Photography Details (if Photo requested)**

```
📸 Photography Details
━━━━━━━━━━━━━━━━━━━━
• Date Needed: 15 Feb 2026
• Time: 9:00 AM - 12:00 PM

📝 Notes:
Group photo of all participants needed

━━━━━━━━━━━━━━━━━━━━
🔗 View in Admin Panel
```

### Service Type Emoji Mapping

| Service | Emoji | Combined Example |
|---------|-------|------------------|
| Poster/Media | 🎨 | 🎨 Poster |
| AV Support | 🎛 | 🎛 AV |
| Photography | 📸 | 📸 Photo |
| Poster + AV | 🎨🎛 | Type: 🎨🎛 Poster + AV |
| All three | 🎨🎛📸 | Type: 🎨🎛📸 Poster + AV + Photo |

### Environment Variables (Telegram)

```env
# Telegram Bot Configuration
TELEGRAM_ENABLED=true
TELEGRAM_BOT_TOKEN=123456789:ABCdefGHIjklMNOpqrsTUVwxyz
TELEGRAM_CHAT_ID=-1001234567890
```

### Message Strategy

**Multiple messages per request:**
- 1 main summary message (always sent)
- 1 message per service type requested (AV, Media, Photo)
- Example: Poster + AV request = 3 messages total

**Why multiple messages?**
- Keeps each message focused and readable
- Easier to reference specific details later
- Avoids hitting Telegram's 4096 character limit
- Team can react/reply to specific service messages

### Trigger Point

- **Location:** `public/api/submit_request.php`
- **When:** After successful database insert, before returning response
- **Failure handling:** Log error but don't block submission

### Optional: Additional Telegram Alerts

| Event | Message |
|-------|---------|
| New request | Summary with details (primary use case) |
| Urgent/Late request | Extra emphasis with warning emoji |
| Daily digest | Summary of pending requests (future) |

---

## Implementation Steps

### Phase 1: Setup (Foundation)

1. [ ] Create `composer.json` with PHPMailer dependency
2. [ ] Run `composer install`
3. [ ] Add SMTP + Telegram variables to `.env` and `.env.example`
4. [ ] Create `includes/mailer.php` with:
   - `getMailer()` - returns configured PHPMailer instance
   - `sendEmail($to, $subject, $body)` - wrapper function
   - `loadEmailTemplate($template, $data)` - template renderer
5. [ ] Create `includes/telegram.php` with:
   - `sendTelegramNotification($message)` - send message to group
   - `formatNewRequestMessage($request)` - format request summary

### Phase 2: Telegram Bot Setup

6. [ ] Create Telegram bot via @BotFather
7. [ ] Create media team Telegram group
8. [ ] Add bot to group and get chat ID
9. [ ] Test bot can send messages to group

### Phase 3: Email Templates

10. [ ] Create `templates/emails/` directory
11. [ ] Create `confirmation.php` template
12. [ ] Create `approved.php` template
13. [ ] Create `rejected.php` template

### Phase 4: Integration

14. [ ] Modify `submit_request.php`:
    - Send confirmation email to requestor
    - Send Telegram notification to admin group
15. [ ] Modify `approve.php` - send approval email
16. [ ] Modify `reject.php` - send rejection email
17. [ ] Add rejection reason field to `reject.php` and `view.php`

### Phase 5: Database (if Option A)

18. [ ] Add `rejection_reason` column to `media_requests`
19. [ ] Update `reject.php` to save rejection reason

### Phase 6: Testing

20. [ ] Test Telegram notification on form submission
21. [ ] Test confirmation email on form submission
22. [ ] Test approval email from admin panel
23. [ ] Test rejection email with reason from admin panel
24. [ ] Verify emails render correctly
25. [ ] Test error handling (disabled notifications, invalid credentials)

---

## Environment Variables

```env
# Email Configuration
SMTP_HOST=mail.divinemercy.my
SMTP_PORT=465
SMTP_SECURE=ssl
SMTP_USER=media@divinemercy.my
SMTP_PASS=your_password_here
SMTP_FROM_EMAIL=media@divinemercy.my
SMTP_FROM_NAME="CDM Media Ministry"

# Development override (optional)
MAIL_ENABLED=true
MAIL_DEBUG=0

# Telegram Bot Configuration
TELEGRAM_ENABLED=true
TELEGRAM_BOT_TOKEN=your_bot_token_here
TELEGRAM_CHAT_ID=your_chat_id_here
```

---

## Error Handling

- Email failures should NOT block the main action (submit/approve/reject)
- Log email errors to a file or database
- Show warning to admin if email fails (but still complete the action)
- Consider retry mechanism for failed emails (future enhancement)

---

## Email Template Design

### Style Approach

- Simple, clean HTML email
- Inline CSS (for email client compatibility)
- Church branding colors (from existing theme)
- Mobile-responsive layout
- Plain text fallback

### Branding Elements

- Church logo (optional - hosted URL)
- Primary color: `#1e3a5f` (dark blue from admin theme)
- Accent color: `#3b82f6` (blue)
- Font: System fonts (Arial, sans-serif)

---

## Security Considerations

- Store SMTP password in `.env` (not in code)
- Add `.env` to `.gitignore`
- Sanitize all user data in email templates
- Use TLS/SSL for SMTP connection
- Rate limiting (future) - prevent email flooding

---

## Future Enhancements

1. **Status update emails** - Notify when request moves to in_progress/completed
2. **Email queue** - Background processing for better performance
3. **Notification logs** - Track sent emails/Telegram messages in database
4. **Resend option** - Allow admin to resend notification emails
5. **Email preferences** - Let requestors opt-out of certain emails
6. **Telegram commands** - Bot commands to check pending requests, quick approve
7. **Daily digest** - Telegram summary of all pending requests
8. **n8n integration** - Move to workflow-based notifications when ready

---

## Questions to Decide

1. **Rejection reason required?** - Should admins be required to provide a reason when rejecting?

2. **Email design** - Plain/simple or styled with church branding?

3. **CC/BCC** - Should any emails be copied to a central inbox (e.g., media@divinemercy.my)?

4. **Telegram group or channel?** - Group allows discussion, channel is broadcast-only

5. **Telegram for approvals/rejections too?** - Or just new submissions?

6. **Include admin panel link in Telegram?** - Direct link to view the request

---

## Estimated Scope

| Phase | Description |
|-------|-------------|
| Phase 1 | Setup - PHPMailer, Telegram helper, config |
| Phase 2 | Telegram Bot - Create bot, setup group, get credentials |
| Phase 3 | Email Templates - 3 email templates |
| Phase 4 | Integration - Modify submit/approve/reject files |
| Phase 5 | Database - 1 column addition (optional) |
| Phase 6 | Testing - All notification channels |

---

## Appendix: Sample Email Preview

### Confirmation Email Sample

```
Subject: Request Received - MR-2026-0042

Dear John Tan,

Thank you for submitting your media request. We have received
your request and it is now pending review.

REFERENCE NUMBER: MR-2026-0042

Request Summary:
- Event: Youth Fellowship Night
- Date: 15 February 2026
- Services: AV Support, Photography

What's Next:
Our team will review your request and respond within 3-5
working days. You will receive an email once your request
has been processed.

If you have any questions, please contact us at:
media@divinemercy.my

God bless,
CDM Media Ministry
Church of Divine Mercy
```

---

*Document created: 2026-02-01*
*Updated: 2026-02-01 - Added Telegram notifications*
