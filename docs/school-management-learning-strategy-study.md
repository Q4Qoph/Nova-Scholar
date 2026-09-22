# Nova Scholar: school management with connected e-learning

Research date: 21 September 2026. Status: desk study complete; strategy, packages, prices and delivery phases are proposals for owner review. No application functionality was changed. Read with the [original competitive study](competitive-market-research.md), [school, parent and educator study](school-parent-educator-market-research.md), and [research plan](plans/school-management-learning-study.md).

Subsequent owner decision on 21 September 2026: school administration plus e-learning is now the accepted direction, with AI deprioritized. The [new workflow study](school-platform-workflow-study.md) and [active implementation plan](plans/school-platform-implementation.md) supersede this report's implementation recommendations and unresolved strategic approval. The report below remains the original dated analysis; its prices/costs are still hypothetical.

## 1. Recommendation

**This is a coherent business direction worth validating: sell useful administration and learning workflows to schools, then offer affordable independent learning to families. It is a substantial change of business and product scope, not a small addition to the current study assistant.**

The proposed promise is: “Run the school, keep families informed, and turn classroom learning needs into useful practice.” Schools pay for institutional operations and included e-learning for their students. Families whose schools are not customers can buy independent learning access at a small subscription. A school subscription should already cover the assignments, submissions and reports that its teachers require; charging families again for those basic functions would undermine the school purchase.

Recommend beginning with reachable private day primary schools of roughly 150–600 learners, validating one administration problem and one Grade 5 mathematics learning workflow. This is a recruitment hypothesis, not evidence that private schools are richer, easier buyers or more digitally ready. Include boarding and mixed schools in discovery, then pilot a boarding extension after the common platform works. If the owner's strongest credible partner is a boarding school, use that access, but budget and test boarding operations explicitly before live dependence.

For the first product, prioritize learner/guardian records, attendance, fee statements and reconciliation, parent communication, and teacher-assigned practice. Defer a complete accounting/payroll suite, clinic system, transport tracking, open tutor marketplace and all-grade lesson production. A focused management product can still serve day and boarding schools through a common core and an optional boarding module.

The investment test is whether schools will pay enough for operations and included learning **without relying on separate family subscriptions**. Independent learning should have its own positive economics. A school contract creates access for its eligible learners, not automatic consent, device availability or active learning. Customer-school learners are not also counted as paying independent subscribers.

### Confirmed subscription audience

The owner clarified on 21 September 2026 that the small subscription is for **students at non-subscribing schools**. This report therefore recommends two purchase routes: school-funded operations plus learning, and family-funded learning without a school contract. A second compulsory learner charge at subscribing schools is not part of the proposal. Transition and overlapping-payment cases still need explicit treatment when a school joins or a learner transfers.

## 2. How this changes the conclusions of the first two studies

| Direction | Buyer and repeat use | Main advantage | Main burden | Current judgment |
| --- | --- | --- | --- | --- |
| Adult tertiary study assistant | Student; revision and course practice | Closest to the existing application; fastest reuse | Free alternatives, individual acquisition, seasonal purchases | Still the shortest route to testing current code with its intended audience |
| Parent-funded Grade 5 learning | Guardian; home practice and progress | Focused content and a clear household problem | Content quality, child safeguards, parent acquisition | A narrow learning offer remains useful inside the new strategy |
| School management plus learning | School buys operations and student learning; families at other schools buy learning separately | Recurring staff workflows and institutional distribution | Sales, migration, fees accuracy, support, tenancy and child-data responsibilities | Promising broader direction, conditional on school demand and delivery capacity |

The earlier advice to defer school management made sense for a consumer learning launch. The owner is now asking whether school management itself should become the core product. This study evaluates that alternative; it does not treat the earlier scope recommendation as a permanent prohibition. Conversely, management software is not justified solely because it could market an AI tutor.

What remains useful from the earlier studies: narrow initial content, educator review, low-data delivery, explicit learning outcomes, bounded AI costs, separate payer/learner roles, and observed purchases rather than survey enthusiasm. What changes: buyer interviews, procurement, onboarding, daily staff use, school retention and operational reliability become central measures.

## 3. Evidence and project baseline

Method: reviewed both studies and the project requirements, roadmap, phase plans, architecture, data model, decisions and implementation records. Inspected the current module/migration inventory and searched for school, guardian, boarding and payment entities. This is a capability inventory, not a code audit, live demonstration or fresh test run. Existing unrelated work is present in the working tree and was preserved.

Public-source research used official vendor pages, KICD, Kenya Education Cloud, Safaricom and ODPC. Vendor descriptions establish advertised functionality, not adoption, security, reliability or learning efficacy. Undated pages are retrieval-date snapshots. No sales calls, school visits, interviews, accounts, purchases, private learner records or provider integrations were used.

| Project evidence | Potential reuse | Missing for this direction |
| --- | --- | --- |
| P1 identity and individual profiles | Authentication and session foundations | School memberships, guardian relationships, child access and staff responsibilities |
| P2 plans, subscription periods and usage accounting | Concepts for paid periods, allowances and usage budgets | School contracts, seats, sponsor-specific allowances, family billing and overlapping grants |
| P3 private document ingestion work | Private file handling and queued processing | Teacher publishing, class permissions, content licences and reliable evaluated ingestion |
| P4 tutor internal alpha | Conversations, provider boundary and queued generation | Child-appropriate behavior, reviewed content grounding, citations and classroom access rules |
| P5 quizzes, attempts and card reviews | Practice and deterministic objective scoring foundations | Teacher assignments, curriculum mapping, official grade separation and reviewed question banks |
| P6 payments proposed; no school operations modules identified | Existing billing design can inform future work | Live payments, school fee subledger, attendance, report cards, boarding and school support tooling |

The [data model](data-model.md) introductory status still calls attempts/reviews planned, while later [P5 records](plans/p5-quizzes-flashcards.md), the implementation log and current files show those alpha entities exist. This study uses the later evidence and does not interpret it as production verification. Existing P0–P5 gates remain open. The adult Student KES 299 and Pro KES 699 prices remain accepted baseline decisions; proposed school/family prices below do not replace them.

## 4. Competitive reality

The combined proposition is already present in the market. Nova Scholar should compete on demonstrated workflow quality, onboarding and service, rather than claim that combining school administration and e-learning is new.

| Offering | Published evidence checked | Implication for Nova Scholar |
| --- | --- | --- |
| Zeraki | Separate analytics, finance and learning products; finance describes receipts, balances, reporting, reminders and bursar training; learning describes lessons, quizzes, school assignments and progress. Current price PDF could not be retrieved | Direct strategic comparator. Test whether schools actually need a replacement or a focused complementary workflow. [Portfolio](https://www.zeraki.app/), [finance](https://www.zeraki.app/zeraki-finance), [learning](https://www.zeraki.app/zeraki-learning) |
| LaiEduHub | Advertises academic, finance and digital-learning suites, with the full package listed at KES 30 per active learner per term or KES 95 annually; messaging credits are separate | Strong price pressure on basic portal functionality. At 400 learners the displayed annual formula yields KES 38,000. Its modular FAQ and pricing cards differ, and 3 × 30 is 90, not 95: obtain a written quote, do not normalize these into one equivalent price. [Pricing](https://www.laieduhub.com/pricing) |
| Shulebora | Indexed official FAQ advertises distinct day/boarding fees, matron tools, evening attendance and boarding gate passes. Indexed homepage lists primary pricing at KES 999/month plus KES 4,000 onboarding | Boarding is an established category, not a novelty. These are indexed claims only: direct pages timed out; prices, module availability and service quality remain unconfirmed. [FAQ](https://shulebora.app/faq), [homepage](https://shulebora.app/) |
| Shulevora | Opened official site advertises admissions, finance, attendance, report cards, parent/teacher portals, boarding/transport and LMS | A broad feature list will be hard to differentiate. The study did not exercise these modules or verify deployment/customer claims. [Product](https://shulevora.co.ke/) |
| Kenya Education Cloud | Public platform provides grade-organized resources, interactive learning, teacher/parent resources and an OER category | A resource library alone has strong substitutes. Inspect the licence for each resource before redistribution; public availability does not make every item reusable. [Official platform](https://lms.kec.ac.ke/) |
| Existing registers, spreadsheets, accounting tools and messaging | Alternatives to inspect with each school; prevalence and costs were not measured | Switching must save enough work to justify migration, training and subscription cost. A working spreadsheet can be a harder competitor than an unused app |

ShuleSoft was also considered, but its site was inaccessible in this session and yielded insufficient usable official evidence; no current feature or pricing conclusion is drawn. Other search results were excluded where they added only unverified marketing breadth.

Proposed competitive test: use synthetic data to demonstrate an admission, part-payment, corrected receipt, absent learner, teacher assignment and parent report in Nova Scholar and the school's current workflow. Compare task time, errors, support required, export quality and total quoted cost. Do not adopt one vendor's description of another vendor as evidence.

### Where a defensible advantage might develop

The strongest hypothesis is a reliable connection between classroom work and follow-up: a teacher reviews a topic result, assigns appropriate practice, sees completed independent attempts, and sends a clear next step to the guardian. Administration supplies authorized rosters and communication; learning supplies a reason beyond fee reminders for families to engage. This relationship needs testing; it is not proof that competitors lack it.

Additional potential strengths are straightforward migration, understandable statements, responsive local support, usable shared-device sessions and exportable records. Each requires operational investment. Neither M-Pesa nor “AI-powered” alone establishes differentiation.

## 5. Administration: common core, day schools and boarding schools

All features in this section are proposed. Priority describes sequencing within the new direction, not existing functionality.

| Area | Common school workflow | Day-school emphasis | Boarding/mixed-school addition | Sequence |
| --- | --- | --- | --- | --- |
| Admissions and records | Enrol learner, link authorized guardians, allocate grade/stream, transfer or archive | Emergency contact and approved collection details | Day/boarder status with effective dates; residence eligibility | Core |
| School calendar | Academic year, actual term dates, class groups, promotions | Daily opening/closing and class schedule | Reporting/return dates, supervised study sessions | Core; manual timetable first |
| Attendance | Present/absent/late/excused with corrections and actor/time | Arrival/departure and unresolved absences | Separate classroom and dormitory roll calls; reconciliation of disagreements | Core plus boarding extension |
| Fees | Term charges, discounts/bursaries, receipts, allocations, balances and corrections | Tuition, meals or transport charges where offered | Boarding charges, mid-term status changes and deposits where used | Core fee subledger; not full accounting |
| Academic reporting | Teacher mark/evidence entry, review, publication and corrections | Same controlled reporting process | Same; boarding status is not an academic grading rule | Selected cohort first |
| Parent communication | Notices, statements, reports, delivery status and preference handling | Absence and collection notices | Approved leave/return notices and contact arrangements | Core with capped messaging |
| Residence | Not required for day-only learners | Hide unused screens | House/dorm/bed capacity, effective-dated allocations and responsible staff | Boarding extension |
| Leave and gate events | Authorized departure/return records where applicable | Approved pickup and early departure | Request, approval, release, expected return, actual return and overdue escalation | Boarding extension; supervised pilot |
| Staff work | Scoped staff assignments and school roles | Teacher and administrative duties | Matron/patron/house staff duties and shift handover | Core roles; boarding duties later |
| Welfare, meals and assets | Limited necessary operational records | Meal counts, lost items, transport rosters if needed | Catering counts, restricted welfare incidents and residence stock | Later; minimize sensitive records |
| Extended administration | Library, inventory, purchasing, payroll, full accounts | School-specific | School-specific | Separate later modules, only with demand |

Do not encode a school as permanently either “day” or “boarding.” One school can have both, and a learner can change residence status during a term. Preserve previous allocations and fee treatment. A departure approval is not evidence that the learner physically left; a sent message is not proof a guardian received or acknowledged it.

### Three representative operational journeys

**Day school:** teacher records an absence → designated staff checks whether it is excused or a late arrival → authorized guardian is contacted → correction is recorded if needed. Do not turn an unverified missing register entry into an automatic emergency assertion.

**Boarding school:** authorized staff approves leave → gate staff records actual release to the approved person → responsible staff sees expected return → actual return closes the event → overdue return triggers the school's human escalation procedure. Internet failure must have a rehearsed paper/phone fallback; back-entry records both actual event time and entry time. This is proposed operational continuity, not a promise of an implemented offline app.

**Fee administration:** parent pays the school → transaction is verified and matched → bursar allocates to the right learner and charges → receipt and statement update → any reversal/correction leaves an audit trail. Wrong admission numbers, siblings, part-payments, overpayments, opening arrears and refunds must be first-class cases.

The person buying software, the person doing daily entry and the person receiving its reports are often different. Interview school owner/principal, bursar, class teacher, guardian and boarding lead separately. For public schools, establish the actual authorized procurement route and budget owner before promising a sales timetable; this study has not audited procurement requirements.

## 6. E-learning and subscriptions: who receives what?

Recommended product names are working labels, not approved branding: **Nova Scholar School** for operations and classroom learning; **Nova Scholar Learn** for independent learning; optional **Boarding** capability for the institutional product. One account can have several authorized contexts, but records and grants must remain distinct.

| Learner situation | Included access | Optional purchase | Access that payment must never grant |
| --- | --- | --- | --- |
| Enrolled at a subscribing school | School-published resources, required assignments/submissions, reviewed learning catalogue, assigned practice and released reports within the school contract | No separate learner subscription in the initial offer; school funds the disclosed learning allowance | Other classes' private work, school finance administration, another school's material |
| At a non-subscribing school | A bounded public introduction, if trial policy is approved | Independent reviewed learning pack/subscription through an authorized guardian for a minor | A school portal or verified membership merely by typing a school name |
| School has sponsored enhanced learning seats | Defined catalogue and allowance for assigned learners | School can expand its package under a separate quote; no assumed family upsell | Duplicate payment for the same covered period and benefits |
| Learner transfers or school contract ends | Personal account/history according to agreed retention; eligible exports | Continue independent learning without requiring the new school to buy | Automatic transfer of former school's restricted materials or staff permissions |
| Personal subscription expires | School-funded assignments continue while school membership/contract remains valid | Manual renewal of personal premium benefits | Cancellation of school access because an optional family plan lapsed |

Basic required schoolwork should not depend on a discretionary parent upgrade. Likewise, a school's unpaid software invoice needs a defined contractual grace/export policy; avoid abruptly disabling safety-relevant boarding records. The exact period and reduced-service behavior must be agreed before sale. Arrears owed to a school and a family's personal Nova subscription are separate facts and should not automatically control each other.

Suggested entitlement policy: authorize the resource first, then find a grant that covers that resource, feature and date. Use the school grant for assigned work; use a personal grant for independent premium work, unless a valid sponsorship covers it. Do not simply add every plan allowance or apply the largest quota globally. Explain which allowance will be used, prevent duplicate consumption, and disclose overlapping cover at checkout. Resolve existing personal subscriptions at school onboarding through a chosen credit, pause or next-renewal policy; no policy is implemented here.

The same adult can be a teacher in one school and guardian in another. Paying for a learner is not sufficient evidence of guardianship. Student admission numbers are unique within the relevant school, not reliable global identity. Link accounts through verified invitations/relationships and a reviewed correction process.

### What learning actually means

The classroom workflow is teacher selects/reviews a lesson → assigns to a class → learner attempts → feedback → teacher reviews exceptions → guardian sees an appropriate summary. Independent learning needs a usable reviewed catalogue even when no teacher uploads anything. Consequently, the external subscription cannot be launched merely by opening school registration to everyone.

Retain Grade 5 mathematics as a first content hypothesis, consistent with the second study. Schools may roster other grades, but clearly disclose the limited learning catalogue and reporting scope. KICD publishes Grade 5 designs including mathematics and a separate Grade 10 design catalogue; those are starting references for qualified reviewers, not proof that Nova's content is aligned. This study has not audited the individual designs. [Grade 5 catalogue](https://kicd.ac.ke/cbc-materials/curriculum-designs/grade-five-designs/), [Grade 10 catalogue](https://kicd.ac.ke/cbc-materials/curriculum-designs/grade-ten/).

Commission original or properly licensed lessons and question banks. Record curriculum version, outcome, author, reviewer, answer rationale and revision date. Separate school-private materials, Nova-licensed catalogue content and personal uploads; teacher employment or a school upload is not automatic permission to resell a worksheet to external learners.

For children, begin with short reviewed explanations and bounded hints tied to approved lessons. Teacher approval precedes publication of AI drafts. Practice results must remain distinguishable from official teacher-approved grades; do not automatically change report cards or make placement/discipline decisions from AI output. Use new questions for reassessment rather than treating memorized retries as mastery.

### Access differs between day and boarding learners

Test actual school device policies, lab availability, data and power. Do not assume a boarder has a personal phone or is allowed to use one. Boarding learning may work through timetabled lab sessions, supervised shared devices and teacher-distributed activities. Day learners may use guardian phones in the evening; some have no dependable access. Shared sessions need explicit learner selection, private results and reliable logout.

Illustrative capacity constraint: 20 lab devices × 3 supervised sessions/week = 60 individual session slots/week. For 300 learners that is 0.2 slots per learner, before downtime. An enrolment licence therefore does not imply weekly individual participation. Measure feasible access before selling usage-based outcomes. Printed alternatives or secure offline functionality would need their own scoped design; the current BRD excludes offline learning.

## 7. Pricing, affordability and economics

These are experiments in KES, not approved prices, supplier quotations, tax advice or a revenue forecast. No competitor price validates Nova's willingness to pay. Quote the billing interval, learner band, content coverage, messaging allowance, AI cap, onboarding work and support explicitly.

### Proposed offers to test

| Offer | Initial price hypothesis | Boundary |
| --- | --- | --- |
| School core | Test KES 3,000 versus 5,000/month for the same up-to-300-learner offer | Common operations plus included classroom workflow; disclosed reviewed catalogue; school value must stand alone |
| Boarding extension | Test an additional KES 2,000/month for up to 150 active boarders | Rosters, residence allocation, roll calls and approved leave/return; not clinic, catering accounts or 24-hour vendor staffing |
| Onboarding | Illustrative KES 10,000 fixed quote after a data audit | Specified import, training and opening-balance sign-off; complex cleanup priced separately |
| Independent Learn | Test KES 99 versus 199 per 30 days for the same one-learner, one-grade/subject reviewed practice offer | Disclose catalogue coverage and bounded automated help; no unlimited AI or human tuition |
| Students at customer schools | Included under the school contract; no separate student price proposed | Display school-funded cover; handle any pre-existing independent subscription fairly |

For bigger schools, seek learner bands or a minimum plus per-learner formula after measuring workload. Avoid arbitrary custom features for every contract. For school billing, test a clear term invoice against a monthly invoice: at an agreed four-month service period, KES 5,000/month becomes KES 20,000 for that period. Do not claim every school term lasts exactly four months; contracts should specify dates and holiday access.

KES 99 may be affordable for some families and uneconomic for the product, or still unaffordable for others. Test disclosed prices after a usable demonstration and later with actual purchases. School-funded seats can reduce family cost. Advertising to children or selling their information is not a proposed subsidy. Keep any scholarship allocation budgeted and transparent.

### A worked business scenario

Every input below is assumed. Monthly service-equivalent amounts help comparison; they are neither cash receipts nor accounting revenue recognition. Tax, refunds and provider charges must be established separately before launch.

| Input | Calculation | Monthly amount |
| --- | --- | --- |
| 10 school core contracts | 10 × KES 5,000 | KES 50,000 |
| 4 boarding extensions | 4 × KES 2,000 | KES 8,000 |
| 200 independently purchased learning subscriptions | 200 × KES 149 illustrative midpoint | KES 29,800 |
| Gross recurring service value | 50,000 + 8,000 + 29,800 | **KES 87,800** |
| School-specific delivery/support reserve | 10 × assumed KES 1,500 | KES 15,000 |
| Extra boarding support reserve | 4 × assumed KES 500 | KES 2,000 |
| Learning variable delivery reserve | 200 × assumed KES 45 | KES 9,000 |
| Residual before fixed costs and unmodelled deductions | 87,800 − 26,000 | **KES 61,800** |

The school reserve must cover school-specific support, messaging, hosting and included student learning delivery; the independent learner reserve covers that separate cohort's AI/payment/support usage. Neither is measured. KES 45 is not a verified provider bill. Replace the reserves with actual cost categories, and include payment fees, reversals, bad debt and applicable taxes without double-counting. Fixed engineering, sales, educator content production/review, legal work, base hosting, administration and owner compensation are excluded. Onboarding revenue and its one-time delivery cost are also excluded from recurring totals.

Included e-learning is a crucial sensitivity: if just 100 school learners each incurred KES 45 of delivery cost monthly, that school's learning alone would cost KES 4,500, exceeding the entire assumed KES 1,500 school reserve and consuming most of a KES 5,000 contract. Do not give every school learner the same compute-heavy allowance as an independent subscriber without repricing. Reusable reviewed practice and deterministic marking can be included broadly; price and meter expensive generated assistance at the school level with clear per-learner fairness. Mandatory assignments must remain usable when a tutor-generation allowance is exhausted. Measure both enrolled seats and active school learners before choosing a contract price.

At an assumed KES 150,000 monthly fixed operating cost, even this scenario falls short by KES 88,200 before those additional deductions. More revenue lines do not automatically make a small SaaS business profitable.

At the KES 5,000 core price and KES 1,500 delivery reserve, school contribution before omitted deductions is KES 3,500. Covering KES 150,000 of fixed costs from core school contracts alone requires at least `ceil(150,000 / 3,500) = 43` schools; actual need will be higher if costs/tax reduce the margin. At KES 3,000 with the same reserve, the arithmetic becomes 100 schools. This is a sensitivity illustration, not a sales target validated by demand.

### Sensitivity and cash risks

| Change to the worked scenario | Residual before fixed costs and omitted deductions | What it tests |
| --- | --- | --- |
| No personal learning sales | KES 41,000: 58,000 school value − 17,000 reserves | Whether school business stands without cross-selling |
| Learning falls from 200 to 80 paying subscriptions | KES 49,320: 58,000 + 11,920 − 17,000 − 3,600 | Term/holiday variation and renewal |
| School support reserve rises to KES 3,000 per school | KES 46,800 | Training, data quality and support escalation |
| Learning variable cost rises to KES 90 per subscriber | KES 52,800 | Heavy AI use and costly support |

For a single learner at KES 149 and KES 45 assumed variable cost, the pre-deduction residual is KES 104. Three paid months yield KES 312 to recover acquisition and fixed costs; twelve months cannot be assumed. For a school, KES 15,000 acquisition plus KES 8,000 onboarding labour less KES 10,000 collected setup fee leaves KES 13,000 to recover. At KES 3,500 monthly contribution that is about 3.7 months, before omitted costs and collection delays. Track sales travel, demos and training as real labour, including the founder's time.

School collections may arrive by term while payroll and infrastructure are monthly. Keep a separate cash forecast with invoice dates, actual collections, receivables, setup costs and renewal risk. Model consumer revenue across term and holiday cohorts separately. Do not promise automatic M-Pesa renewals without an approved supported payment arrangement.

### Estimate the reachable market from named relationships

Example only: 30 reachable qualifying schools → 12 substantive demos → 5 scoped pilots → 3 paying renewals. At KES 5,000, three contracts produce KES 15,000 monthly core service value. If each has 300 learners, the resulting 900 school records are neither 900 active learners nor 900 paid family subscriptions. A hypothetical 10% personal conversion means 90 purchases, not 900, and must be measured separately.

Do not reuse the earlier national enrolment figures as a count of buyer schools, connected households or a forecast. No independently verified national customer share, school purchasing budget or new school-count estimate is claimed in this study.

## 8. Payments, privacy and operating responsibility

### Three money flows

| Flow | Payer → recipient | Nova's record and responsibility |
| --- | --- | --- |
| School software contract | School → Nova Scholar | Institutional invoice, payment, term and product grants |
| Personal learning subscription | Guardian/adult learner → Nova Scholar | Personal order, verified payment, refund/expiry and personal grant |
| Tuition/boarding fees | Guardian/sponsor → school | School-owned charges, receipts, allocations and reconciliation; not Nova sales revenue |

Recommend school fees settle directly to the school's authorized merchant/bank arrangement. Do not pool tuition in Nova's operating account in the initial design. A separate custody/settlement model would need provider and legal review. Safaricom's official Daraja portal provides a payment integration route; this does not establish that every school's merchant arrangement, bank or intended callback flow is supported. Confirm access, current verification controls, fees and reconciliation with each provider before implementation. [Safaricom Daraja](https://developer.safaricom.co.ke/).

Proposed controls: verify payment independently of browser success; store unique provider references with their merchant context; handle retries, duplicate/missing events and reversals; match amounts and recipient; keep an unmatched-payment queue. A typed receipt code or screenshot is not verification. A split payment across siblings must not duplicate cash received. Imported opening balances need school sign-off. Restrict who can issue credits, change allocations and approve corrections; retain audit evidence.

A fee subledger is not a complete statutory accounting product. Export reconciled information to the school's chosen accounting process. Avoid claims about compliant payroll, tax reports or government submission until those modules and applicable requirements are specifically verified.

### Child data and institutional boundaries

ODPC's education guidance addresses institution data-handling obligations and rights; its 2025 children's guidance distinguishes controllers/processors and emphasizes children's interests and parental/guardian permission. Full PDFs timed out here; the official indexed material was consulted. Determine the applicable lawful bases, guardian arrangements, registration, DPIA, retention and international transfer requirements through a scoped assessment before real learner data is used. This is not legal clearance. [Education guidance](https://www.odpc.go.ke/wp-content/uploads/2024/02/ODPC-Guidance-Note-for-the-Education-Sector.pdf), [2025 children's guidance](https://www.odpc.go.ke/wp-content/uploads/2025/11/ODPC-%E2%80%93-Guidance-Note-for-Processing-Childrens-Data.pdf).

Proposed governance: document whether Nova acts on the school's instructions for operational records and which responsibilities arise for its own direct learning service. Do not assume one contract makes the school responsible for every subsequent use. Separate compulsory administrative purposes from optional direct subscriptions and marketing.

Recommended access policy: teachers see assigned classes and relevant learning work; bursars see authorized finance records; boarding staff see necessary residence/leave information; guardians see verified linked children and released reports. School payment does not grant blanket access to private personal chats. Support access is time-limited, justified and audited. Do not send fees, welfare, health or discipline records to the learning model merely because they share a learner identity.

Prepare age-appropriate explanations, guardian/child research permissions, provider handling review, correction/export procedures and incident response. Exclude public child profiles, open adult–child messaging, behavioral advertising, biometrics and continuous location tracking from the first release. These are proposed product boundaries, not a claim that every item is a statutory prohibition. Keep health/discipline data out until a specific necessary workflow and restricted handling are designed. Any holiday tuition offer would also retain the second study's unresolved legal review; this report proposes no such launch.

### Operational service is part of the product

Each school needs an accountable onboarding contact, data import rules, staff training, an agreed support window, export format and exit process. Schedule cutovers around the school's operations, not a convenient developer date. Reconcile a trial import, verify counts and balances, get school sign-off, retain a recoverable source copy under agreed handling, and confirm who owns corrections.

Use a short parallel trial with the current system, then a controlled cutover to avoid perpetual duplicate entry. A restore exercise must cover files and database records together. Boarding emergency response remains with trained school staff; Nova should not advertise 24/7 emergency coverage without staffing and a contract that support it.

## 9. Conceptual architecture and migration implications

This section is a proposed design direction, not a Laravel implementation prescription or committed schema. The current architecture can remain one modular application; a separate app for each school is not required to test this business.

| Boundary | Conceptual records | Important invariant |
| --- | --- | --- |
| Identity and relationships | Accounts, learner profiles, guardian links, school memberships, role assignments | Identity, payer, guardian and school membership are distinct; role is scoped to context |
| School academics | Academic years, terms, enrolments, classes, teacher assignments, attendance, approved reports | Membership and term history survive promotion or transfer |
| School finance | Fee schedules, charges, receipts, allocations, adjustments, reconciliations | Tuition money never enters Nova subscription revenue totals |
| Residence | Houses/dorms/beds, allocation periods, roll calls, leave and return events | No overlapping active bed allocations; physical events differ from approvals |
| Learning | Curriculum/outcomes, content versions, licences, assignments, submissions, attempts | A school-private source is not available to independent subscribers |
| Commercial access | School contracts, sponsored seats, personal subscriptions, feature grants, usage sponsor | One authorized consumption settles to the correct budget without double billing |
| Audit and operations | Import batches, publication approvals, message deliveries, support access and exports | Sensitive changes and failures can be traced to actor, context and time |

A platform learner identity may connect several historical school memberships, but each school's records remain isolated. Global content sharing needs explicit publication/licensing; adding a `school_id` to individual records is not sufficient. Scope pages, file access, exports, queued jobs, reports, retrieval and caches consistently. A removed teacher must lose access even if an old job or invitation still exists.

Do not migrate existing individual users into an invented school. Preserve personal records and current subscription periods, then link to real verified memberships when justified. A learner transfer should terminate the old access scope according to policy while preserving necessary school history and authorized personal work. Match duplicate records through a review process rather than names alone.

If the owner selects this direction, revise D04 release scope, D05 school/family packaging, D11 child-data policy and the affected requirements/architecture/data model together before implementation. Extend the P2/P6 design for institutional and personal grants; add separate school finance requirements instead of stretching subscription tables into a school ledger. Reassess P8 admin permissions and P9 availability/recovery for institutional use. P3–P5 can contribute learning foundations but do not remove the new prerequisites.

## 10. Proposed validation and delivery sequence

The stages below are conditional and are not calendar or budget commitments. Staffing and discovery evidence must precede estimates. Do not launch every model from the two earlier studies alongside this school route; use them as alternatives when the school case fails.

| Stage | Deliverable | Evidence required before proceeding |
| --- | --- | --- |
| S0: school discovery | 8 school interviews: suggested mix 4 day, 2 boarding, 2 mixed; include public/private contrasts where access permits | Recent problems, decision-maker, existing spend, data format, procurement route and actual device availability documented |
| S1: commercial/workflow prototype | Synthetic-data demo, written package, import sample and scoped onboarding quote | At least 3 schools identify a budget owner and accept a specific pilot scope/price in writing; this is interest, not revenue |
| S2: foundation and safe pilot preparation | Approved requirements, school/guardian access, privacy arrangements, fee controls, recovery and payment prerequisites | Isolation, authorised access, import reconciliation and failed/duplicate payment cases pass; no real child onboarding beforehand |
| S3: common school core plus one learning loop | 2 day-school pilots; selected operational scope and Grade 5 maths | Run through a real reporting/billing cycle and assess staff time, reconciliation, teacher use and school renewal |
| S4: boarding extension | 1 boarding/mixed partner, resident allocation and leave/roll-call pilot | Staff-led outage rehearsal, capacity checks, role boundaries, overdue-return escalation and handover work correctly |
| S5: independent Learn | Reviewed catalogue, independent guardian onboarding and paid offer at non-customer schools | Own demand and positive delivery margin; no reliance on private school materials or school purchase |
| S6: expansion | Additional schools, grades/subjects and justified modules | Retention, content quality, support capacity and cash sustainability demonstrated |

Investigate the individual subscription during S0/S1 with adults; its public launch follows child/account/content readiness. A committed boarding partner could bring S4 capability into the first pilot, but would increase first-release scope. That tradeoff requires a revised plan and realistic support capacity, not silent omission of residence safety workflows.

### Interview and demonstration questions

- Principal/owner: show the last report or fee problem that took too long. What does it cost now? Who signs and pays for software? What would make you refuse to switch?
- Bursar: demonstrate a part-payment, payment for siblings, incorrect reference, reversal and opening arrears using redacted/synthetic examples. Who resolves discrepancies?
- Teacher: how is an assignment distributed, checked and followed up? Which administrative entry repeats work? Can a practice recommendation actually fit the timetable?
- Boarding lead: show how roll-call differences, leave approvals, late returns and handovers are handled. What happens during an outage? Which records must be available immediately?
- Guardian: demonstrate current access to statements and schoolwork, device sharing, existing learning spending and concerns about optional fees. Test comprehension of included versus paid features.
- Staff/IT contact: what can be exported, which devices work, who manages passwords, and who will maintain records after the initial training?

Suggested additional adults: 12 guardians split between customer-school prospects and other schools, plus 6 teachers for content/workflow review. Samples are exploratory; consented research involving children requires a separate ready process. Do not publish contacts or raw private records in project documentation.

### Provisional pilot decision gates

Agree thresholds before running the pilot; these are management proposals, not industry benchmarks. Record denominators, exclusions, assistance, school type and school-calendar period.

| Measure | Proposed gate | Failure response |
| --- | --- | --- |
| School value | At least 30% reduction in time for two selected recurring tasks against observed baseline, with no increase in material errors | Simplify workflow or change the problem being solved |
| Financial integrity | Every pilot receipt/adjustment reconciles to the agreed authoritative records; no unexplained balance difference | Stop finance cutover; correct and re-verify |
| Staff adoption | At least 80% of expected register/assignment actions completed in each of the last two pilot weeks; report each school separately | Identify access/training burden before adding features |
| School paid demand | At least 2 of 3 institutional pilots pay the disclosed continuation price after their pilot cycle | Revisit product, buyer and price; free use is not validation |
| Learning access | At least 70% of invited eligible learners can complete an initial supervised/independent session; distinguish assisted access | Address devices and school scheduling before measuring learning retention |
| Learning return | At least 50% of all enrolled learning-pilot participants complete meaningful practice in week 4 | Review relevance, timetable and usability; report missing participants |
| Quality | All published pilot lessons/keys reviewed; zero unresolved critical content or safeguarding defects | Withdraw affected content and repair review process |
| Independent paid demand | At least 6 of 20 eligible families offered a ready, clearly priced product purchase; track second-period renewal separately | Treat as exploratory signal; do not extrapolate national demand |
| Economics and support | Positive measured contribution for school and personal offers separately; routine support reaches an agreed feasible ceiling | Raise price, narrow service or improve onboarding; do not hide founder labour |
| Isolation and boarding controls | Zero known cross-school/guardian access failures; every scripted leave/return/outage case resolves to staff-owned action | Block live use of the affected workflow |

Test learning with reviewed unseen questions and a delayed check, record assistance and attrition, and avoid causal improvement claims from a small uncontrolled pilot. A learner's login count is not proof of learning. A school's signed letter is not collected recurring revenue.

## 11. Main risks and decisions

| Risk | Why it could defeat the direction | Proposed response |
| --- | --- | --- |
| Scope expands into a complete ERP | Years of modules before a customer gets dependable value | Sell and deliver one explicit operational package first |
| Low prices with high service costs | Onboarding and support consume the contract margin | Cost imports/training, define limits and measure support minutes |
| Incumbent works adequately | Migration cost exceeds the improvement | Require demonstrated pain; consider a learning companion instead |
| School learners are charged twice | Overlapping subscriptions harm trust when a school joins | Include student learning in the school contract; disclose cover and resolve existing independent subscriptions |
| Learners cannot access devices | Institution licence fails to produce active learning | Audit lab/household access and schedule capacity before sale |
| Inaccurate fees or attendance | Staff lose confidence and operational harm follows | Parallel verification, approved corrections and clear source of truth |
| Child/private school data leaks | Trust and lawful operation are compromised | Scoped permissions, minimized processing, provider review and tested boundaries |
| Learning catalogue is too thin | Independent families have nothing useful to buy | Launch one complete reviewed topic sequence and state coverage clearly |
| Boarding assumes continuous connectivity | A screen failure disrupts leave or roll call | Human procedures, rehearsed fallback and controlled back-entry |
| Contract concentration and seasonality | One lost school or holiday slump materially affects cash | Separate cohorts, renewals and cash forecasts; avoid speculative hiring |

The next decision is whether to fund school discovery and one bounded prototype. Select reachable partners, name a school-operations adviser and educator reviewer, and set a capped discovery budget. Then decide the initial school band, learning audience, package boundaries and delivery/support team from evidence.

My preferred direction, if those prerequisites are available, is **school-paid administration and included classroom learning, followed by an independently useful affordable Learn subscription**. Begin with day schools to reduce initial operational burden, while designing and validating boarding requirements early. If schools will not pay for the common core, do not subsidize it using assumed future parent purchases; return to a narrower learning offer or a complementary integration.

## 12. Evidence limits and source follow-up

| Source | What was actually accessible on 21 September 2026 | Follow-up before a commitment |
| --- | --- | --- |
| Zeraki portfolio, finance and learning pages | Full official pages opened; linked finance price PDF failed | Written quote, demonstration and current content/contract coverage |
| LaiEduHub pricing | Full official page opened | Clarify differing modular prices and term/annual amounts; validate service/support |
| Shulebora | Official indexed homepage/FAQ; direct fetches timed out | Confirm prices, boarding workflows and contract terms directly |
| Shulevora | Official product page opened | Hands-on workflow and reliability checks; no adoption claim accepted |
| Kenya Education Cloud and KICD | Official platform/indexed catalogue and opened curriculum catalogue pages | Inspect selected lessons/designs and resource licences with an educator |
| Safaricom | Official developer portal opened | Merchant-specific onboarding, supported flows, verification, pricing and reconciliation |
| ODPC education and 2025 children's guidance | Official indexed passages; direct PDFs timed out | Obtain full current documents and service-specific privacy assessment |
| ShuleSoft | No usable full official retrieval | Omitted from substantive comparison pending access |

No current school market-share estimate, representative willingness-to-pay study, school sales-cycle benchmark, legal approval or production-readiness claim is made. Earlier studies retain their own research dates and source limitations; their historical statistics and competitor prices are not silently refreshed here. Prices, funnels, cost reserves and thresholds in this study are explicitly assumed. Actual documentation checks are recorded in the [implementation log](implementation-log.md).
