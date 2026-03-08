# Proposal: core-reports

## Summary
Implement core financial reports with filters and export support.

## Problem
School stakeholders need daily/monthly/arrears visibility and exportable records.

## Scope
- Daily report
- Monthly report
- Arrears report
- PDF and Excel export

## Out of Scope
- Dashboard page widgets/charts composition
- Notification sending

## Dependencies
- transactions
- obligations-arrears
- receipts

## Success Criteria
- Report totals match source ledger
- Exports generate valid files
- Filters perform within acceptable response time
