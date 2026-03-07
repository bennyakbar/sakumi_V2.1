# Proposal: dashboard

## Summary
Implement dashboard analytics views built on validated report data contracts.

## Problem
Users need quick operational visibility without running full reports manually.

## Scope
- Summary cards
- Trend charts
- Dashboard-specific caching

## Out of Scope
- Changes to transaction posting logic
- Core report export internals

## Dependencies
- core-reports

## Success Criteria
- Dashboard metrics match report sources
- Load time is acceptable under normal usage
