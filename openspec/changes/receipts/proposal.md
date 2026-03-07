# Proposal: receipts

## Summary
Implement receipt PDF generation and reprint/cancellation behavior as after-commit side effects.

## Problem
Finance operations require formal printable proof of payment that does not risk financial write rollback.

## Scope
- Receipt template + PDF generation
- After-commit receipt creation workflow
- Reprint support
- Cancellation watermark behavior

## Out of Scope
- Transaction posting logic changes
- Reporting/dashboard

## Dependencies
- transactions

## Success Criteria
- Receipt generated for successful income transaction
- Reprint works
- Cancelled receipt clearly watermarked
