# Master Implementation Plan - RKAP Gap Closure

**Generated**: 2026-09-21  
**Source**: `agents/Codebase-Planning-Gap.md`  
**Total Estimated Effort**: 46-67 days

---

## Overview

This master plan consolidates all 6 phases for closing the gaps between the current codebase and the RKAP business requirements defined in "ARKITEKTUR DAN ALUR BISNIS RKAP.md".

---

## Phase Summary

| Phase | Title | Priority | Timeline | Days | Key Deliverables |
|-------|-------|----------|----------|------|------------------|
| 1 | Critical Business Rules | 🔴 High | Week 1-2 | 5-7 | DB constraints, model validation, approval matrix fix |
| 2 | Missing Entities & Relationships | 🔴 High | Week 2-3 | 3-5 | risk_treatments, budget_opex, work_schedules, companies |
| 3 | Form Features & UX | 🟡 Medium | Week 3-5 | 10-15 | Form 1-6 enhancements, import/export, auto-populate |
| 4 | Dashboard & Reporting | 🟡 Medium | Week 5-7 | 10-15 | 11 missing widgets, drill-down, reporting engine |
| 5 | Integration & Technical | 🟢 Low | Week 7-10 | 15-20 | API layer, 4 integrations, queues, Redis, email |
| 6 | Security & Compliance | 🔴 High | Week 2-3 (||) | 3-5 | RBAC, versioning, soft deletes, audit trail |

**Total**: 46-67 days

---

## Dependency Graph

```
Phase 1 (Critical Rules)
    │
    ├──→ Phase 2 (Entities) ←──┐
    │                          │
    ├──→ Phase 6 (Security)    │ (parallel)
    │                          │
    └──→ Phase 3 (Forms) ──────┘
           │
           └──→ Phase 4 (Dashboards)
                  │
                  └──→ Phase 5 (Integration)
```

**Critical Path**: Phase 1 → Phase 2 → Phase 3 → Phase 4 → Phase 5  
**Parallel Track**: Phase 6 (can start after Phase 1)

---

## File Structure

```
agents/gap-planning-1/
├── 00-master-plan.md                    # This file
├── 01-phase-1-critical-business-rules.md
├── 02-phase-2-missing-entities.md
├── 03-phase-3-form-features.md
├── 04-phase-4-dashboard-reporting.md
├── 05-phase-5-integration-technical.md
└── 06-phase-6-security-compliance.md
```

---

## Quick Start Checklist

### Week 1 (Phase 1 Start)
- [ ] Create check constraint migrations
- [ ] Create unique constraint migrations  
- [ ] Create FK constraint migrations
- [ ] Add WorkProgram model observer (Rating A+ validation)
- [ ] Add RiskIdentification observer (Strategy+Program validation)
- [ ] Add WorkProgram budget validation before approval
- [ ] Fix ApprovalService RKAP matrix order
- [ ] Add RiskIdentification approval workflow

### Week 2 (Phase 1 Complete + Phase 2 Start + Phase 6 Start)
- [ ] Run all Phase 1 migrations
- [ ] Write Phase 1 tests
- [ ] Create risk_treatments migration + model
- [ ] Create budget_opex migration + model + service
- [ ] Create work_schedules migration + data migration
- [ ] Create companies migration + update related models
- [ ] Create RbacSeeder with all roles/permissions
- [ ] Create Policy classes for all models
- [ ] Add SoftDeletes migrations for all Erkap models

### Week 3 (Phase 2 Complete + Phase 6 Complete)
- [ ] Run Phase 2 migrations + data migration
- [ ] Create BudgetOpexConsolidationService
- [ ] Update relationships across models
- [ ] Run RbacSeeder
- [ ] Register policies in AuthServiceProvider
- [ ] Create rkap_versions migration + service
- [ ] Integrate versioning with ApprovalService
- [ ] Add AuditObserver + register on all models

### Week 4-5 (Phase 3 - Forms)
- [ ] Form1ImportExportService + controllers
- [ ] WorkScheduleController + auto-populate
- [ ] Form 3 subtotals (FE + BE)
- [ ] Form 4/5 BudgetCapex enhancements
- [ ] Form 6 business process + status enum + risk appetite

### Week 6-7 (Phase 4 - Dashboards & Reporting)
- [ ] All 11 missing dashboard widgets
- [ ] Drill-down controller + service + UI
- [ ] ReportGenerator + 5 report templates
- [ ] Consolidated Excel export
- [ ] Scheduled reports command
- [ ] Chart.js + frappe-gantt + cytoscape.js integration

### Week 8-10 (Phase 5 - Integration & Technical)
- [ ] API layer (routes, resources, controllers)
- [ ] Accounting integration service + jobs
- [ ] HR integration service + jobs
- [ ] Procurement integration service + jobs
- [ ] Asset integration service + jobs
- [ ] Queue configuration + Supervisor setup
- [ ] Redis caching for dashboards
- [ ] Email notifications for approvals
- [ ] File storage abstraction

---

## Risk Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| Data migration complexity (WorkProgram → WorkSchedule) | High | Write comprehensive migration script, test on staging copy first |
| Approval matrix change breaks existing workflows | High | Test thoroughly, deploy with feature flag, monitor |
| Chart library bundle size | Medium | Use tree-shaking, load charts lazily |
| Integration API changes by external systems | Medium | Version APIs, use adapter pattern, circuit breakers |
| Performance on large datasets (dashboards) | Medium | Redis caching, query optimization, pagination |
| RBAC permission conflicts | High | Comprehensive test matrix, staging UAT with real users |

---

## Success Criteria

### Phase 1-2 (Foundation)
- [ ] All business rules enforced at DB + model level
- [ ] No API bypass possible for critical rules
- [ ] All ERD entities exist with correct relationships
- [ ] Approval flows match business document exactly

### Phase 3 (Forms)
- [ ] Form 1 Excel import/export round-trip 100% accurate
- [ ] Form 2 auto-populates from Form 1
- [ ] Form 3 shows all 3 subtotal levels reactively
- [ ] Form 5 shows payment distribution matrix
- [ ] Form 6 has business process + risk appetite linkage

### Phase 4 (Dashboards/Reports)
- [ ] All 11 missing widgets render correctly
- [ ] Drill-down reaches transaction level
- [ ] 5 report types generate PDF + Excel
- [ ] Consolidated workbook has all 8 sheets
- [ ] Scheduled reports deliver via email

### Phase 5 (Technical)
- [ ] API covers all RKAP modules with standardized responses
- [ ] 4 integrations sync data daily without manual intervention
- [ ] Queue workers process jobs with <1% failure rate
- [ ] Dashboard loads <200ms (cached)
- [ ] Approval notifications deliver <30s

### Phase 6 (Security)
- [ ] All 8 roles have correct permissions
- [ ] cost_owner sees only own division data
- [ ] RKAP versioning captures full snapshot on each approval
- [ ] All Erkap models have soft deletes + audit fields
- [ ] Audit trail shows who changed what when

---

## Resource Requirements

| Role | Phase 1-2 | Phase 3 | Phase 4 | Phase 5 | Phase 6 |
|------|-----------|---------|---------|---------|---------|
| Backend Developer (Laravel) | 2 | 2 | 1 | 2 | 1 |
| Frontend Developer (Blade/JS) | 0 | 1 | 2 | 1 | 0 |
| DevOps | 0 | 0 | 0 | 1 | 0 |
| QA/Tester | 1 | 1 | 1 | 1 | 1 |

---

## Next Steps

1. **Review** this plan with stakeholders
2. **Prioritize** - Confirm Phase 1-2-6 as immediate priority
3. **Assign** - Allocate developers to phases
4. **Setup** - Create feature branches for each phase
5. **Kickoff** - Start Phase 1 implementation

---

## References

- **Gap Analysis**: `agents/Codebase-Planning-Gap.md`
- **Business Requirements**: `agents/ARKITEKTUR DAN ALUR BISNIS RKAP.md`
- **Current Codebase**: Laravel 11, MySQL, Blade, jQuery/DataTables
- **Target Architecture**: Section 5.1-5.2 of business doc