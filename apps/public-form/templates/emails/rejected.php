<?php
/**
 * Email Template: Request Rejected
 *
 * Variables available:
 * - $requestor_name (string)
 * - $reference_no (string)
 * - $event_name (string)
 * - $rejection_reason (string)
 */
?>
Dear <?= $requestor_name ?>,

We regret to inform you that your media request could not be approved at this time.

REFERENCE NUMBER: <?= $reference_no ?>


Request Details:
- Event: <?= $event_name ?>


Reason:
<?= $rejection_reason ?>


If you believe this decision was made in error, or if you would like to discuss alternative arrangements, please feel free to contact us. You are also welcome to submit a new request with updated information.


If you have any questions, please contact us at:
media@divinemercy.my


God bless,
CDM Media Ministry
Church of Divine Mercy
Shah Alam, Malaysia
