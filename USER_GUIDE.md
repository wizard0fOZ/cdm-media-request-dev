# CDM Media Request System - User Guide

## 1. Introduction

Welcome to the CDM Media Request System! This guide is designed for the Media Core team to understand and beta test the new system. This platform streamlines the process of submitting and managing requests for media services, including posters/videos, AV support, and photography.

**System URLs:**

*   **Public Form:** [https://media.divinemercy.my/](https://media.divinemercy.my/)
*   **Admin Panel:** [https://media.divinemercy.my/admin](https://media.divinemercy.my/admin)

---

## 2. Submitting a Media Request (Public Form)

This is the process any user or ministry member will follow to submit a new request.

### 2.1. Accessing the Form

Navigate to [https://media.divinemercy.my/](https://media.divinemercy.my/). The homepage provides an overview of the services offered and important guidelines.

*   Click **"Submit a Request"** to begin.
*   Click **"View Guidelines"** to jump to the detailed guidelines section on the homepage.

### 2.2. The Request Form (Multi-Step)

The form is divided into six steps. Progress is saved automatically in your browser, so you can resume a draft if you accidentally close the tab. Click **"Start Fresh"** to clear any saved data and begin a new request.

**Step 1: Approvals**
*   This is a pre-requisite check. Users must confirm they have obtained the necessary approvals (e.g., from the parish priest, ministry head) before proceeding. A "No" answer will prevent submission.

**Step 2: Requestor Details**
*   **Requestor Name:** Your full name.
*   **Ministry/Organization:** The ministry you represent (optional).
*   **Contact No. & Email:** Essential for communication and notifications.

**Step 3: Event Details**
*   **Event Name:** The official name of the event.
*   **Schedule Type:**
    *   **Custom List:** For one-off events or events with irregular dates. Add each date and time.
    *   **Recurring:** For events that repeat on a regular basis (e.g., weekly, monthly).
*   **Lead Time Check:** The system automatically calculates if the request is submitted less than 14 days before the event start date. Late requests are flagged for admin review.

**Step 4: Services**
*   Select one or more services required:
    *   **AV Support:** For audio-visual equipment and technical assistance. You can select the required rooms and specific equipment.
    *   **Poster/Video:** For promotional materials. Specify the content, platforms (e.g., bulletin, social media), and promotion dates.
    *   **Photography:** For event photography coverage.

**Step 5: References**
*   Optionally provide a URL link (e.g., to a Google Drive folder with assets, or a reference design) and any related notes.

**Step 6: Review & Submit**
*   A summary of all the information you've entered is displayed.
*   Review everything carefully.
*   You must check the **"I confirm that all information is accurate"** box.
*   Click **"Submit Request"**.

### 2.3. After Submission

*   The user is redirected to a **Thank You** page.
*   A unique **Reference Number** (e.g., `CDM-ABC123`) is displayed.
*   A confirmation email is sent to the requestor with the same reference number and a summary of the request.

---

## 3. Admin Panel

The Admin Panel is the central hub for the Media Core team to manage all incoming requests.

### 3.1. Login

1.  Navigate to [https://media.divinemercy.my/admin](https://media.divinemercy.my/admin).
2.  You will be prompted to enter a password to access the dashboard.

*(Note: For the beta test, a shared password will be provided.)*

### 3.2. Dashboard (`/admin/index.php`)

The dashboard provides a complete overview of all requests.

*   **Statistics:** At the top, you'll see a quick count of Total, Pending, Approved, and Rejected requests.
*   **Filters:** You can filter the request list by:
    *   **Search:** By keyword (matches Reference #, Event Name, or Requester).
    *   **Status:** All, Pending, Approved, Rejected.
    *   **Service:** All, AV, Media, Photo.
*   **Request List:**
    *   Each row summarizes a request, showing reference number, event name, requester, submission date, services, and status.
    *   **Late Flag:** A yellow "Late" badge appears next to the reference number if the request was submitted less than 14 days before the event.
    *   Click **"View"** to see the full details of a request.

### 3.3. Viewing a Request (`/admin/view.php`)

This page displays all information for a single request, organized into clear sections:

*   Requestor Information
*   Event Details & Schedule
*   Services Requested (with specific details for AV, Media, and Photo)
*   References

### 3.4. Managing Requests (Approving/Rejecting)

For requests with a **"Pending"** status, an **Actions** section will appear at the bottom of the `view.php` page.

**To Approve a Request:**

1.  Click the green **"Approve Request"** button.
2.  A confirmation prompt will appear. Click "OK".
3.  The request status changes to "Approved".
4.  An approval email is automatically sent to the requestor.

**To Reject a Request:**

1.  Click the red **"Reject Request"** button.
2.  A modal window will pop up.
3.  **You must provide a reason for the rejection.** This reason will be included in the email sent to the requestor.
4.  Click **"Confirm Rejection"**.
5.  The request status changes to "Rejected".
6.  A rejection email, including the reason you provided, is automatically sent to the requestor.

### 3.5. Email Notifications

The system automatically sends the following emails:

*   **Submission Confirmation:** To the requestor upon successful form submission.
*   **Request Approved:** To the requestor when an admin approves their request.
*   **Request Rejected:** To the requestor when an admin rejects their request (includes the rejection reason).

This completes the user guide for the beta testing phase. Please report any bugs, issues, or suggestions for improvement.
