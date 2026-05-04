#!/usr/bin/env python3
"""
Fixes the missing JS cost-calculation variables in admin.php that cause
the End Session button to be silently unclickable for hourly sessions with
remaining time.
"""

import re

FILE = r'c:\xampp\htdocs\GamingSpotHub\admin.php'

with open(FILE, 'rb') as fh:
    raw = fh.read()

# The needle: the line that immediately precedes the missing block (and is still present)
# We'll insert the block right after the "Remaining time label" comment block.
NEEDLE = (
    b"            // \xe2\x94\x80\xe2\x94\x80 Consumed time & cost calculation "
    b"\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\r\n"
    b"            // Time Used row: show time-only cost (not extras)\r\n"
    b"            document.getElementById('endEarlyConsumedCost').textContent = '\xe2\x82\xb1' + timeCost.toFixed(2);"
)

REPLACEMENT = (
    b"            // \xe2\x94\x80\xe2\x94\x80 Consumed time & cost calculation "
    b"\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\xe2\x94\x80\r\n"
    b"            // Elapsed label for the \"Time Used (Xh YYm)\" row in the modal\r\n"
    b"            const elapsedMin   = Math.floor(elapsed / 60);\r\n"
    b"            const elapsedHrs   = Math.floor(elapsedMin / 60);\r\n"
    b"            const elapsedMRem  = elapsedMin % 60;\r\n"
    b"            const elapsedLabel = (elapsedHrs ? elapsedHrs + 'h ' : '') +\r\n"
    b"                                 String(elapsedMRem).padStart(2, '0') + 'm';\r\n"
    b"            const elapsedEl = document.getElementById('endEarlyElapsedStr');\r\n"
    b"            if (elapsedEl) elapsedEl.textContent = '(' + elapsedLabel + ')';\r\n"
    b"\r\n"
    b"            // Prorate cost for time actually used.\r\n"
    b"            // upfrontPaid covers plannedMinutes; reservationDownpayment is non-refundable.\r\n"
    b"            const ratePerMin   = plannedMinutes > 0\r\n"
    b"                                 ? (upfrontPaid - reservationDownpayment) / plannedMinutes\r\n"
    b"                                 : 0;\r\n"
    b"            const timeCost     = Math.max(0, Math.round(ratePerMin * elapsedMin * 100) / 100);\r\n"
    b"            const consumedCost = timeCost + extras;        // add extras (controller rental etc.)\r\n"
    b"            const nonRefundBase = reservationDownpayment;  // non-refundable portion\r\n"
    b"            const rawRefund    = upfrontPaid - consumedCost;\r\n"
    b"            const refundAmt    = Math.max(0, Math.round((rawRefund - nonRefundBase) * 100) / 100);\r\n"
    b"            const hasRefund    = refundAmt > 0;\r\n"
    b"\r\n"
    b"            // Time Used row: show time-only cost (not extras)\r\n"
    b"            document.getElementById('endEarlyConsumedCost').textContent = '\xe2\x82\xb1' + timeCost.toFixed(2);"
)

count = raw.count(NEEDLE)
print(f"Needle found {count} time(s)")

if count != 1:
    # Try a simpler needle just on the last line
    SIMPLE_NEEDLE = (
        b"            // Time Used row: show time-only cost (not extras)\r\n"
        b"            document.getElementById('endEarlyConsumedCost').textContent = '\xe2\x82\xb1' + timeCost.toFixed(2);"
    )
    count2 = raw.count(SIMPLE_NEEDLE)
    print(f"Simple needle found {count2} time(s)")
    if count2 == 1:
        SIMPLE_REPLACEMENT = (
            b"            // Elapsed label for the \"Time Used (Xh YYm)\" row in the modal\r\n"
            b"            const elapsedMin   = Math.floor(elapsed / 60);\r\n"
            b"            const elapsedHrs   = Math.floor(elapsedMin / 60);\r\n"
            b"            const elapsedMRem  = elapsedMin % 60;\r\n"
            b"            const elapsedLabel = (elapsedHrs ? elapsedHrs + 'h ' : '') +\r\n"
            b"                                 String(elapsedMRem).padStart(2, '0') + 'm';\r\n"
            b"            const elapsedEl = document.getElementById('endEarlyElapsedStr');\r\n"
            b"            if (elapsedEl) elapsedEl.textContent = '(' + elapsedLabel + ')';\r\n"
            b"\r\n"
            b"            // Prorate cost for time actually used.\r\n"
            b"            // upfrontPaid covers plannedMinutes; reservationDownpayment is non-refundable.\r\n"
            b"            const ratePerMin   = plannedMinutes > 0\r\n"
            b"                                 ? (upfrontPaid - reservationDownpayment) / plannedMinutes\r\n"
            b"                                 : 0;\r\n"
            b"            const timeCost     = Math.max(0, Math.round(ratePerMin * elapsedMin * 100) / 100);\r\n"
            b"            const consumedCost = timeCost + extras;        // add extras (controller rental etc.)\r\n"
            b"            const nonRefundBase = reservationDownpayment;  // non-refundable portion\r\n"
            b"            const rawRefund    = upfrontPaid - consumedCost;\r\n"
            b"            const refundAmt    = Math.max(0, Math.round((rawRefund - nonRefundBase) * 100) / 100);\r\n"
            b"            const hasRefund    = refundAmt > 0;\r\n"
            b"\r\n"
            b"            // Time Used row: show time-only cost (not extras)\r\n"
            b"            document.getElementById('endEarlyConsumedCost').textContent = '\xe2\x82\xb1' + timeCost.toFixed(2);"
        )
        new_raw = raw.replace(SIMPLE_NEEDLE, SIMPLE_REPLACEMENT, 1)
        with open(FILE, 'wb') as fh:
            fh.write(new_raw)
        print("Fix applied via simple needle!")
    else:
        print("ERROR: Could not locate target. Manual fix required.")
else:
    new_raw = raw.replace(NEEDLE, REPLACEMENT, 1)
    with open(FILE, 'wb') as fh:
        fh.write(new_raw)
    print("Fix applied successfully!")
