<?php
/**
 * Email Template: Request Approved
 *
 * Variables available:
 * - $requestor_name (string)
 * - $reference_no (string)
 * - $event_name (string)
 * - $event_dates (string)
 */
?>
Dear <?= $requestor_name ?>,

Great news! Your media request has been approved.

REFERENCE NUMBER: <?= $reference_no ?>


Request Details:
- Event: <?= $event_name ?>

<?php if (!empty($event_dates)): ?>
- Date: <?= $event_dates ?>

<?php endif; ?>

Next Steps:
Our team will contact you for coordination and any additional details needed. Please ensure you are available for communication in the coming days.


If you have any questions, please contact us at:
media@divinemercy.my


God bless,
CDM Media Ministry
Church of Divine Mercy
Shah Alam, Malaysia
