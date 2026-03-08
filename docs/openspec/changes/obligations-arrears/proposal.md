# Proposal: obligations-arrears

## Summary
Implement monthly obligation generation and arrears aggregation with idempotency guarantees.

## Problem
School needs automatic fee obligations and accurate arrears visibility without duplicates.

## Scope
- Monthly obligations generation command
- Idempotent upsert with unique composite constraint
- Payment linkage updates from posted transactions
- Arrears aggregation queries

## Out of Scope
- WhatsApp reminder sending
- Report export rendering

## Dependencies
- master-data
- transactions

## Success Criteria
- Re-running generation does not create duplicates
- Arrears totals are correct by student/class/period
