# Pending Items Tracker

This document tracks the remaining product, operational, and documentation work for the Bible Reading Tracker.

## How To Use This Tracker

- Move items between priority bands as the product direction changes.
- Split large items into implementation tickets once work begins.
- Mark items with dates, owners, or links to PRs/issues as the team workflow matures.

## Current Priority Order

### P1: High-Value Product And Operations Work

#### 1. Guided Horizontal Migration Workflow
- Status: Completed (2026-04-22)
- Priority: P1
- Why it matters: Same-level movement is possible today by changing `parent_id`, but there is no guided workflow for safely relocating an entire team, batch, platoon, or squad.
- Desired outcome:
  - pick a source group
  - pick a same-level destination parent
  - preview impact on members, leaders, and descendants
  - confirm migration in a dedicated flow
- Notes:
  - this should remain limited to horizontal movement
  - it should not allow invalid vertical-level restructuring

#### 2. True Sibling Merge Workflow
- Status: Completed (2026-04-22)
- Priority: P1
- Why it matters: Teams and other sibling groups can be manually consolidated today, but there is no first-class merge operation.
- Desired outcome:
  - select source and target sibling groups
  - move members and child branches as needed
  - handle leader reassignment explicitly
  - retire, archive, or delete the emptied source group cleanly
- Notes:
  - merge should be limited to the same hierarchy level
  - merge should include a review/confirmation step

#### 3. Leader-Scoped Detailed Record Drilldowns
- Status: Completed (2026-04-22)
- Priority: P1
- Why it matters: Leaders can already monitor and report on their tree, but there is still room for deeper descendant-level record views in a more leader-native workflow.
- Desired outcome:
  - richer member detail pages for leaders
  - descendant participation history drilldowns
  - clearer movement between monitoring, reporting, and detailed records

### P2: Workflow And Administration Polish

#### 4. Bulk Promote/Demote From User Directory
- Status: Completed (2026-04-22)
- Priority: P2
- Why it matters: Promotion and demotion now exist on the hierarchy screen, but not yet from the broader user operations flow.
- Desired outcome:
  - promote or demote selected users from the directory
  - enforce hierarchy-role alignment and vacancy safety checks

#### 5. Vacancy Resolution UX
- Status: Completed (2026-04-22)
- Priority: P2
- Why it matters: Vacancy alerts now exist, but resolution still requires navigating manually into hierarchy screens.
- Desired outcome:
  - direct links from vacancy alerts to the affected hierarchy
  - one-step “assign or promote leader” flow from vacancy context

#### 6. Balance Insights To Action
- Status: Completed (2026-04-22)
- Priority: P2
- Why it matters: The hierarchy screen can now highlight uneven sibling teams, but those insights do not yet prefill a balancing action.
- Desired outcome:
  - launch the bulk team distribution flow directly from a balance insight
  - preselect the relevant teams
  - optionally suggest a recommended move count

### P3: Plan And Participant Experience Refinement

#### 7. Recruitment Presentation Polish
- Status: Completed (2026-04-22)
- Priority: P3
- Why it matters: Recruitable plans are visible, but the member onboarding and recruitment discovery flow can still feel more intentional.
- Desired outcome:
  - clearer active recruitment presentation
  - better guidance after registration or invite acceptance
  - cleaner distinction between current opportunities and history

#### 8. Cohort Recommendation Logic
- Status: Completed (2026-04-22)
- Priority: P3
- Why it matters: When multiple NT and OT cohorts are available, the UI does not yet strongly recommend which one a participant should choose.
- Desired outcome:
  - highlight a recommended cohort
  - show why it is recommended
  - preserve flexibility for users to pick another eligible active cohort

#### 9. Participation History Refinement
- Status: Completed (2026-04-22)
- Priority: P3
- Why it matters: Repeat participation is supported, but cycle history can still be presented more clearly.
- Desired outcome:
  - better cycle comparisons
  - clearer participation timeline
  - easier access for both members and leaders

### P3: Messaging, Notification, And Governance Expansion

#### 10. Notification Preference Depth
- Status: Completed (2026-04-22)
- Priority: P3
- Why it matters: Automation notifications respect inbox/email broadly, but there is no per-category preference control.
- Desired outcome:
  - category-specific preferences for reminders, digests, and vacancy alerts
  - clear admin override behavior where appropriate

#### 11. Message Centre Expansion
- Status: Completed (2026-04-22)
- Priority: P3
- Why it matters: The message center covers the core v1 workflow, but archive, trash, and search are still absent.
- Desired outcome:
  - archive
  - trash or soft-delete
  - search and filtering improvements

#### 12. Audit Coverage Expansion
- Status: Completed (2026-04-22)
- Priority: P3
- Why it matters: High-value actions are logged now, but full operational visibility will require broader coverage.
- Desired outcome:
  - extend audit logging to more admin mutations
  - add export or summarized audit views if needed

### P4: Performance And Stability Work

#### 13. Query Optimization Pass
- Status: Completed (2026-04-22)
- Priority: P4
- Why it matters: The app is now functionally broad, and the heaviest dashboards/reports/hierarchy flows would benefit from a deliberate performance pass.
- Desired outcome:
  - reduce repeated query work
  - improve eager loading strategy
  - keep large seeded datasets responsive

#### 14. Large-Volume Workflow Review
- Status: Completed (2026-04-22)
- Priority: P4
- Why it matters: The seeders now support high user counts, but the team has not yet done a deliberate operational UX review at that scale.
- Desired outcome:
  - test admin workflows with 1000+ users
  - review pagination, bulk actions, and hierarchy handling under load

## Documentation Backlog

### 15. Comprehensive User Manual With Workflow Diagrams
- Status: Completed (2026-04-22)
- Priority: P1
- Why it matters: The app has grown into a multi-role operational platform. A proper manual is now needed for onboarding admins, leaders, and members without relying on tribal knowledge.
- Deliverable scope:
  - introduction to the system purpose
  - role-based guide for admins, leaders, and members
  - plan creation and lifecycle management
  - enrollment paths:
    - public invite links
    - registration-first flow
    - direct join of active recruitments
  - training workflow
  - daily reading workflow
  - catch-up and read-ahead workflow
  - hierarchy management workflow
  - leader monitoring and reporting workflow
  - messaging workflow
  - notifications and automation workflow
  - participation history workflow
  - troubleshooting and FAQ
- Diagram expectations:
  - enrollment workflow diagram
  - training-to-reading progression diagram
  - hierarchy visibility and reporting scope diagram
  - leader escalation and messaging direction diagram
  - plan lifecycle and automation diagram
- Recommended format:
  - Markdown manual in `docs/`
  - Mermaid diagrams embedded where helpful
  - sectioned so it can later be split into role-based mini guides
- Deliverable:
  - `docs/user-manual.md`

## Suggested Execution Order

1. Comprehensive user manual with workflow diagrams
2. Guided horizontal migration workflow
3. True sibling merge workflow
4. Leader-scoped detailed drilldowns
5. Vacancy resolution UX
6. Balance insights to action
7. Bulk promote/demote from user directory
8. Recruitment and participation-history polish
9. Notification preference depth and message center expansion
10. Audit expansion, performance pass, and large-volume workflow review
