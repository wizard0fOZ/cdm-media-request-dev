<?php
declare(strict_types=1);

/**
 * Check if Telegram notifications are enabled
 */
function isTelegramEnabled(): bool {
    return ($_ENV['TELEGRAM_ENABLED'] ?? 'false') === 'true';
}

/**
 * Send a message to Telegram group/channel
 *
 * @param string $message The message to send (supports HTML formatting)
 * @return bool True if sent successfully
 */
function sendTelegramMessage(string $message): bool {
    if (!isTelegramEnabled()) {
        error_log("Telegram notification skipped (disabled)");
        return false;
    }

    $botToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';
    $chatId = $_ENV['TELEGRAM_CHAT_ID'] ?? '';

    if (empty($botToken) || empty($chatId)) {
        error_log("Telegram not configured: missing bot token or chat ID");
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
    $error = curl_error($ch);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("Telegram send failed: HTTP $httpCode, error: $error, response: $result");
        return false;
    }

    return true;
}

/**
 * Get emoji for service type
 */
function getServiceEmoji(string $type): string {
    return match($type) {
        'media' => "\xF0\x9F\x8E\xA8", // paint palette
        'av' => "\xF0\x9F\x8E\x9B", // control knobs
        'photo' => "\xF0\x9F\x93\xB8", // camera with flash
        default => "\xF0\x9F\x93\x8B" // clipboard
    };
}

/**
 * Get label for service type
 */
function getServiceLabel(string $type): string {
    return match($type) {
        'media' => 'Poster',
        'av' => 'AV',
        'photo' => 'Photo',
        default => $type
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
        $typeLabels[] = getServiceLabel($service);
    }
    $typeString = implode('', $typeEmojis) . ' ' . implode(' + ', $typeLabels);

    $msg = "\xF0\x9F\x93\xA5 <b>New Media Request</b>\n";
    $msg .= str_repeat("\xE2\x94\x80", 20) . "\n";
    $msg .= "Type: {$typeString}\n\n";

    $msg .= "\xF0\x9F\x91\xA4 <b>Requestor Info</b>\n";
    $msg .= "\xE2\x80\xA2 Name: " . htmlspecialchars($request['requestor_name']) . "\n";
    $msg .= "\xE2\x80\xA2 Contact: " . htmlspecialchars($request['contact_no']) . "\n";
    if (!empty($request['ministry'])) {
        $msg .= "\xE2\x80\xA2 Ministry: " . htmlspecialchars($request['ministry']) . "\n";
    }
    $msg .= "\n";

    $msg .= "\xF0\x9F\x93\x85 <b>Event Details</b>\n";
    $msg .= "\xE2\x80\xA2 Title: " . htmlspecialchars($request['event_name']) . "\n";

    if (!empty($request['event_description'])) {
        $desc = $request['event_description'];
        if (mb_strlen($desc) > 100) {
            $desc = mb_substr($desc, 0, 100) . '...';
        }
        $msg .= "\xE2\x80\xA2 Description: " . htmlspecialchars($desc) . "\n";
    }

    if (!empty($request['event_dates'])) {
        $msg .= "\xE2\x80\xA2 Date(s): " . htmlspecialchars($request['event_dates']) . "\n";
    }

    if (!empty($request['event_times'])) {
        $msg .= "\xE2\x80\xA2 Time(s): " . htmlspecialchars($request['event_times']) . "\n";
    }

    if (!empty($request['event_location_note'])) {
        $msg .= "\xE2\x80\xA2 Venue: " . htmlspecialchars($request['event_location_note']) . "\n";
    }

    $msg .= "\n" . str_repeat("\xE2\x94\x80", 20) . "\n";

    // Admin panel link
    $appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/');
    $adminUrl = $appUrl . '/admin/view.php?id=' . $request['id'];
    $msg .= "\xF0\x9F\x94\x97 <a href=\"{$adminUrl}\">View in Admin Panel</a>";

    return $msg;
}

/**
 * Format AV details message
 */
function formatAVDetailsMessage(array $request, array $avDetails, array $equipment = []): string {
    $msg = "\xF0\x9F\x8E\x9B <b>AV Support Details</b>\n";
    $msg .= str_repeat("\xE2\x94\x80", 20) . "\n";

    if (!empty($request['event_location_note'])) {
        $msg .= "\xE2\x80\xA2 Venue: " . htmlspecialchars($request['event_location_note']) . "\n\n";
    }

    if (!empty($equipment)) {
        $msg .= "\xF0\x9F\x94\xA7 <b>Equipment Required</b>\n\n";
        foreach ($equipment as $room => $items) {
            $msg .= "<b>" . htmlspecialchars($room) . ":</b>\n";
            foreach ($items as $item) {
                $msg .= "  \xE2\x80\xA2 " . htmlspecialchars($item) . "\n";
            }
            $msg .= "\n";
        }
    }

    if (!empty($avDetails['note'])) {
        $msg .= "\xF0\x9F\x93\x9D <b>Additional Requests:</b>\n";
        $msg .= htmlspecialchars($avDetails['note']) . "\n\n";
    }

    $msg .= "\xF0\x9F\x8E\xAD <b>Rehearsal:</b>\n";
    if (!empty($avDetails['rehearsal_date'])) {
        $rehearsalInfo = $avDetails['rehearsal_date'];
        if (!empty($avDetails['rehearsal_start_time'])) {
            $rehearsalInfo .= ' ' . $avDetails['rehearsal_start_time'];
        }
        if (!empty($avDetails['rehearsal_end_time'])) {
            $rehearsalInfo .= ' - ' . $avDetails['rehearsal_end_time'];
        }
        $msg .= htmlspecialchars($rehearsalInfo) . "\n";
    } else {
        $msg .= "Not required\n";
    }

    $msg .= "\n" . str_repeat("\xE2\x94\x80", 20) . "\n";

    $appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/');
    $adminUrl = $appUrl . '/admin/view.php?id=' . $request['id'];
    $msg .= "\xF0\x9F\x94\x97 <a href=\"{$adminUrl}\">View in Admin Panel</a>";

    return $msg;
}

/**
 * Format Photo details message
 */
function formatPhotoDetailsMessage(array $request, array $photoDetails): string {
    $msg = "\xF0\x9F\x93\xB8 <b>Photography Details</b>\n";
    $msg .= str_repeat("\xE2\x94\x80", 20) . "\n";

    if (!empty($photoDetails['needed_date'])) {
        $msg .= "\xE2\x80\xA2 Date Needed: " . htmlspecialchars($photoDetails['needed_date']) . "\n";
    }

    if (!empty($photoDetails['start_time']) || !empty($photoDetails['end_time'])) {
        $time = ($photoDetails['start_time'] ?? '') . ' - ' . ($photoDetails['end_time'] ?? '');
        $msg .= "\xE2\x80\xA2 Time: " . htmlspecialchars(trim($time, ' -')) . "\n";
    }

    if (!empty($photoDetails['note'])) {
        $msg .= "\n\xF0\x9F\x93\x9D <b>Notes:</b>\n";
        $msg .= htmlspecialchars($photoDetails['note']) . "\n";
    }

    $msg .= "\n" . str_repeat("\xE2\x94\x80", 20) . "\n";

    $appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/');
    $adminUrl = $appUrl . '/admin/view.php?id=' . $request['id'];
    $msg .= "\xF0\x9F\x94\x97 <a href=\"{$adminUrl}\">View in Admin Panel</a>";

    return $msg;
}

/**
 * Format Media/Poster details message
 */
function formatMediaDetailsMessage(array $request, array $mediaDetails, array $platforms = []): string {
    $msg = "\xF0\x9F\x8E\xA8 <b>Poster/Media Details</b>\n";
    $msg .= str_repeat("\xE2\x94\x80", 20) . "\n";

    if (!empty($platforms)) {
        $platformLabels = array_map(fn($p) => ucfirst($p), $platforms);
        $msg .= "\xE2\x80\xA2 Platforms: " . htmlspecialchars(implode(', ', $platformLabels)) . "\n";
    }

    if (!empty($mediaDetails['promo_start_date'])) {
        $promoRange = $mediaDetails['promo_start_date'];
        if (!empty($mediaDetails['promo_end_date'])) {
            $promoRange .= ' - ' . $mediaDetails['promo_end_date'];
        }
        $msg .= "\xE2\x80\xA2 Promo Period: " . htmlspecialchars($promoRange) . "\n";
    }

    if (!empty($mediaDetails['description'])) {
        $desc = $mediaDetails['description'];
        if (mb_strlen($desc) > 200) {
            $desc = mb_substr($desc, 0, 200) . '...';
        }
        $msg .= "\n\xF0\x9F\x93\x9D <b>Details:</b>\n" . htmlspecialchars($desc) . "\n";
    }

    if (!empty($mediaDetails['caption_details'])) {
        $msg .= "\n\xE2\x9C\x8F <b>Caption:</b>\n" . htmlspecialchars($mediaDetails['caption_details']) . "\n";
    }

    $msg .= "\n" . str_repeat("\xE2\x94\x80", 20) . "\n";

    $appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/');
    $adminUrl = $appUrl . '/admin/view.php?id=' . $request['id'];
    $msg .= "\xF0\x9F\x94\x97 <a href=\"{$adminUrl}\">View in Admin Panel</a>";

    return $msg;
}

/**
 * Send all notification messages for a new request
 *
 * @param array $request Main request data including id, requestor info, event info
 * @param array $services Array of service types ['av', 'media', 'photo']
 * @param array $details Service-specific details ['av' => [...], 'media' => [...], 'photo' => [...]]
 */
function sendNewRequestNotification(array $request, array $services, array $details = []): void {
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
