# GamingSpotHub — Refund System Overview

## How the Current System Works

The system has **two distinct refund types**, handled by separate code paths.

---

## Type 1 — Session Refund (Early End)

**Trigger:** Shopkeeper clicks "End Early" on an active session the customer paid upfront for.

**Flow:**
```
Admin clicks Early End
  → modal: openRefundModal(sessionId, refundAmount, ...)
  → POST to ajax/refund.php  (action = early_end)
  → endSession() runs — computes actual elapsed cost
  → If paid > elapsed cost → recordTransaction() with negative amount (refund)
  → gaming_sessions.status = 'ended'
```

**Database effect:**
```sql
-- Positive = payment, Negative = refund
INSERT INTO transactions (session_id, amount, ...) VALUES (?, -50.00, ...);
```

**Refund amount = upfront paid − actual elapsed cost**
- Example: customer paid ₱160 for 2 hrs, left after 1 hr → refund ₱80
- ₱0-paid sessions → refund = ₱0, session ends cleanly with no transaction

**Where it lives:** `ajax/refund.php`

---

## Type 2 — Reservation Refund (Downpayment Return)

**Trigger:** Shopkeeper clicks "Refund" button on a cancelled reservation in the Reservations tab.

**Flow:**
```
Admin clicks Refund button on cancelled reservation
  → openRefundModal(null, downpayment_amount, ..., reservationId)
  → Same modal, different mode (5th param = reservationId)
  → POST to ajax/refund.php (action = process_refund)
  → recordTransaction() with session_id = NULL (nullable column)
  → UPDATE reservations SET refund_issued = 1
  → UPDATE reservation_cancellations SET refund_issued = 1
```

**Database effect:**
```sql
INSERT INTO transactions (session_id, user_id, amount, ...) VALUES (NULL, ?, -200.00, ...);
UPDATE reservations SET refund_issued = 1 WHERE reservation_id = ?;
UPDATE reservation_cancellations SET refund_issued = 1 WHERE reservation_id = ?;
```

**Customer sees** (on dashboard.php):
- 🟢 **Refunded** badge if `refund_issued = 1`  
- 🟡 **Refund pending** badge if `refund_issued = 0`

---

## Key Architectural Decisions

| Decision | Rationale |
|---|---|
| Refunds = negative `transactions.amount` | Single ledger — no separate refunds table needed |
| `session_id` is nullable | Allows reservation-only refunds with no linked session |
| `ajax/refund.php` is the only entry point | Prevents bypassing billing engine via direct POST |
| Direct `issue_refund` POST to `admin.php` is blocked | Returns error, forces use of AJAX modal |
| `refund_issued` on both `reservations` + `reservation_cancellations` | Audit trail stays in sync |

---

## What Happens to the Money (Manual Process)

> **The system only records that a refund was issued — it does NOT process actual money transfers.**

Cash refunds → shopkeeper physically hands cash back → marks as refunded in the system.  
GCash refunds → shopkeeper initiates transfer manually → marks as refunded.

The system is **a ledger**, not a payment gateway.

---

## Refund Policy (from terms.php + faqs.php)

| Scenario | Refund Policy |
|---|---|
| Cancel reservation **before** start time | ✅ 100% downpayment refunded |
| Cancel reservation **after** start time | ⚠️ Inconvenience fee deducted, remainder refunded |
| Admin-cancelled reservation | ✅ Full downpayment refunded (admin's fault) |
| End session early (paid upfront) | ✅ Proportional refund of unused time |
| Unlimited session ended early | ❌ No refund — flat rate, already played |
| Open time session | N/A — pay at end only |
| Tournament registration | ❌ Non-refundable after tournament starts |

---

## Improvement Ideas

### 1. Refund Reason Field
Currently refunds have no mandatory reason. Add a `reason` dropdown (equipment issue, customer complaint, admin error) to the refund modal for auditability.

### 2. Refund Approval Workflow (Owner-Only)
Right now any shopkeeper can issue any refund. Consider:
- Shopkeeper **requests** a refund → status = `pending_approval`
- Owner **approves** → refund is marked issued
- Prevents fraud (shopkeeper issuing unauthorized refunds)

### 3. Refund Limits / Caps
Add a configurable `max_refund_without_approval` in `system_settings`. Refunds above that threshold require owner approval.

### 4. Refund Report Card
The Reports tab has session/revenue stats but no dedicated refund KPI card. Add:
- Total refunded (₱) this month
- Refund rate (% of sessions)
- Top refund reasons

### 5. Customer Notification on Refund Issued
When `refund_issued` flips to `1`, trigger a notification email via PHPMailer to the customer.

### 6. Reservation Cancellation Inconvenience Fee in Admin UI
The inconvenience fee is deducted manually right now. The admin refund modal could auto-calculate it (if `cancelled_after_start = true`, deduct the `inconvenience_fee` setting) so the shopkeeper doesn't have to do the math.
