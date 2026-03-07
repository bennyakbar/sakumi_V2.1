# Production Readiness Checklist

Use this as a working checklist before and after go-live.

Legend:
- Status: `TODO`, `IN PROGRESS`, `DONE`, `BLOCKED`
- Priority windows:
  - `NOW` = 1-2 weeks (must-have before go-live)
  - `NEXT` = 2-6 weeks
  - `LATER` = 6+ weeks

## NOW (Must-Have)

| Status | Task | Action | Owner |
|---|---|---|---|
| TODO | Role enforcement | Finalize role mapping (`Admin TU`, `Staff`, `Kasir`, `Bendahara`, `Kepsek`) and assign production users | Super Admin + Admin TU |
| TODO | Permission regression tests | Ensure feature tests cover print/reprint/cancel/report authorization | Dev + QA |
| TODO | Receipt control activation | Verify cashier first-print only, reprint by Bendahara/Admin with mandatory reason | Dev + Bendahara |
| TODO | Migration readiness | Run all migrations in production, verify `receipts` and `receipt_print_logs` tables | DevOps |
| TODO | Audit logging baseline | Ensure critical events log actor, action, timestamp, reason/reference | Dev |
| TODO | Backup baseline | Configure daily backup and complete one restore drill before go-live | DevOps |
| TODO | Error monitoring | Enable centralized logs + alerts for 500s, auth failures, print/reprint failures | DevOps |

## NEXT (Strong Audit Posture)

| Status | Task | Action | Owner |
|---|---|---|---|
| TODO | Maker-checker flow | Add approval workflow for cancellation and high-value adjustments | Dev + Bendahara |
| TODO | Period close lock | Prevent backdated financial changes after period close | Dev + Finance |
| TODO | Daily reconciliation job | Auto-check transaction/settlement/report totals and raise exceptions | Dev + Bendahara |
| TODO | Evidence attachment | Require supporting evidence for cancel/void/exception requests | Dev + Operations |
| TODO | Security hardening | Tighten rate limits, session policy, and security headers | DevOps |
| TODO | CI quality gate | Enforce test/lint/migration smoke checks on every deployment | DevOps + Dev |

## LATER (Enterprise-Grade)

| Status | Task | Action | Owner |
|---|---|---|---|
| TODO | Immutable artifacts | Store versioned/signed receipt and invoice snapshots | Dev |
| TODO | Risk analytics | Add alerts for unusual reprint/void/permission-change patterns | Dev + Internal Audit |
| TODO | SoD automation | Add policy checks to prevent conflicting role assignments | Dev |
| TODO | Disaster recovery program | Define RTO/RPO and run scheduled DR simulations | DevOps + Management |
| TODO | Compliance export pack | Build one-click export for audit logs + approvals + reconciliations | Dev + Finance |

## This Week Plan

- [ ] Seed and verify production role assignments for receipt controls.
- [ ] Run migrations and perform print/reprint verification smoke tests.
- [ ] Configure monitoring + alert routing for high-risk events.
- [ ] Execute and document backup-restore rehearsal.
- [ ] Get SOP sign-off from school management.

## Go-Live Exit Criteria

- [ ] All `NOW` items are `DONE`.
- [ ] No blocker-level open incidents.
- [ ] Restore drill evidence documented.
- [ ] Authorization tests passing in CI.
- [ ] SOP and role matrix approved by management.

