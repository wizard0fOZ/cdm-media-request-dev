<?php
/**
 * Email Template: Request Confirmation
 *
 * Variables available:
 * - $requestor_name (string)
 * - $reference_no (string)
 * - $event_name (string)
 * - $event_dates (string)
 * - $services (array) - ['av', 'media', 'photo']
 */

// Format services list
$serviceLabels = array_map(function($s) {
    return match($s) {
        'av' => 'AV Support',
        'media' => 'Poster/Media Design',
        'photo' => 'Photography',
        default => ucfirst($s)
    };
}, $services);
$servicesText = implode(', ', $serviceLabels);
?>
Dear <?= $requestor_name ?>,

Thank you for submitting your media request. We have received your request and it is now pending review.

REFERENCE NUMBER: <?= $reference_no ?>


Request Summary:
- Event: <?= $event_name ?>

<?php if (!empty($event_dates)): ?>
- Date: <?= $event_dates ?>

<?php endif; ?>
- Services: <?= $servicesText ?>


What's Next:
Our team will review your request and respond within 3-5 working days. You will receive an email once your request has been processed.

Please keep your reference number for future correspondence.


If you have any questions, please contact us at:
media@divinemercy.my


God bless,
CDM Media Ministry
Church of Divine Mercy
Shah Alam, Malaysia
