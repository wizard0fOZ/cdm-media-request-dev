<?php
/**
 * Email Template: Needs More Info
 *
 * Variables available:
 * - $requestor_name (string)
 * - $reference_no (string)
 * - $event_name (string)
 * - $service_label (string) - e.g. "AV Support", "Poster/Media Design", "Photography"
 * - $question (string) - what info is needed
 */
?>
Dear <?= $requestor_name ?>,

We are reviewing your media request and need some additional information before we can proceed.

REFERENCE NUMBER: <?= $reference_no ?>


Request Details:
- Event: <?= $event_name ?>

- Service: <?= $service_label ?>


Information Needed:
<?= $question ?>


Please send the requested information to media@divinemercy.my so we can continue processing your request. Include your reference number in your reply.


If you have any questions, please contact us at:
media@divinemercy.my


God bless,
CDM Media Ministry
Church of Divine Mercy
Shah Alam, Malaysia
