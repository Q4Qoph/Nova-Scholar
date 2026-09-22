# Nova Scholar: competitive landscape and Kenya market research

Research date: **17 September 2026**. Status: **desk research complete; recommendations proposed for owner review**. Audience: product owner and implementation team. Suggested reading time: 20–25 minutes.

This report informs the next implementation decision. It does not approve changes to scope, prices, dependencies, providers, or deployment. The product name used throughout is **Nova Scholar**, following the project requirements.

Current direction update, 21 September 2026: the owner has selected school administration plus e-learning and deferred AI priority. The adult-first comparison below remains historical research; the [active school plan](plans/school-platform-implementation.md) now governs delivery.

Subsequent study (21 September 2026): [school management with connected e-learning](school-management-learning-strategy-study.md) evaluates the owner's alternative of making school administration a core product. Read its comparison alongside this dated consumer-market recommendation; no implementation pivot has been approved by either report.

## 1. Recommendation for the owner

Updated after the [school, parent and educator study](school-parent-educator-market-research.md): compare two narrow offers before choosing the initial build focus. Adult tertiary is the fastest reuse of the current product; parent-funded Grade 5 mathematics is the recommended school-market hypothesis. Neither is validated market fit. Do not launch both complete products together.

For the adult offer, build around one promise:

> Turn your course notes into explanations you can check, practice that finds your weak topics, and a clear plan for your next revision session.

If the adult offer wins the comparison, focus first on **adult undergraduate students in a small number of Kenyan campuses and course families**. Include a separate TVET discovery group, then decide whether its learning and assessment needs justify a dedicated offering. Design for a phone, limited data, Kenyan shilling prices, and M-Pesa payment. Choose the family offer instead only after demonstrating parent demand, sustainable educator/content costs, and readiness for lawful, safe child participation. The sections below retain the detailed adult strategy; the companion study supplies school segmentation, parent/educator workflows, safeguards and the head-to-head validation plan.

The current feature list is a useful foundation, but it is not enough to differentiate the product. Google already offers source-based flashcards and quizzes; Quizlet combines practice and personalized study; StudyFetch joins tutoring with course materials and scheduling; RemNote connects notes, practice, and review. These are direct competitive pressures, even if their Kenyan adoption is unknown. [Google product announcement](https://blog.google/innovation-and-ai/models-and-research/google-labs/notebooklm-app-quizzes-flashcards/), [Quizlet study guide](https://help.quizlet.com/hc/en-au/articles/360030841732-Studying-on-Quizlet), [StudyFetch](https://www.studyfetch.com/), [RemNote](https://www.remnote.com/pricing).

My recommendation is to prioritize five outcomes:

1. **Trust:** explanations and generated questions point to the relevant part of the student's notes; unsupported answers are identified.
2. **Course relevance:** organize work around units/modules, topics, assessment dates, and supplied learning outcomes.
3. **A repeatable study routine:** diagnose → explain → practise → revisit mistakes → review later.
4. **Practical access:** fast mobile pages, low data use, recoverable uploads, and understandable M-Pesa purchases.
5. **Proof of value:** demonstrate that students return, learn, and pay before expanding the feature catalogue.

These are a positioning hypothesis, not evidence that Nova Scholar already has a defensible advantage. Competitors can copy individual features; an advantage would need to develop through reliable execution, useful course-specific evaluation material, trusted campus relationships, and measured student outcomes.

## 2. Method, evidence, and limits

This is public-source research, not a hands-on product benchmark or a representative student survey. Sources include official product/help pages, the Commission for University Education (CUE), Kenya National Bureau of Statistics (KNBS), Communications Authority of Kenya (CA), Central Bank of Kenya (CBK), TVET Authority, and Office of the Data Protection Commissioner (ODPC).

Interpret the report using these distinctions:

- **Published fact:** a source describes a feature or reports a statistic. Vendor claims establish advertised functionality, not independent proof of learning improvement.
- **Analysis:** an implication drawn from those facts and Nova Scholar's goals.
- **Hypothesis/proposal:** an idea to test, including launch segment, price, channel, and success thresholds.
- **Project evidence:** existing requirements, phase plans, and implementation records. Internal-alpha status does not mean production acceptance.

Product pages were reviewed on the research date; undated pages are snapshots. Statistical observation periods are stated separately. Pricing can depend on region, billing interval, taxes, promotions, and account status. No paid checkout, private account, live purchase, or student interview was performed. No claim is made about competitors' Kenyan market shares, model accuracy, or actual campus usage.

Some full documents were inaccessible: CUE and KNBS PDF retrieval failed, although indexed passages from their official documents were available. Their figures below are explicitly labelled accordingly. Quizlet's marketing page blocked retrieval, but its help article opened. EasyElimu's help required JavaScript and its subscription page timed out; it is a follow-up candidate, not a fully assessed competitor. Exact Kenyan checkout prices remain unverified unless specified.

## 3. What competing software solves

The final column is our analysis. “Not verified” means the research did not establish the feature or commercial detail; it does not mean the product lacks it.

### 3.1 Direct international competitors and substitutes

| Product | Student problem addressed | Published capabilities examined | Commercial/access evidence | Implication for Nova Scholar |
| --- | --- | --- | --- | --- |
| Google NotebookLM / current Google notebook offering | Understanding and revising a collection of source material | Source selection, source-based quizzes and flashcards; configurable topic, difficulty and quantity; mobile study | Exact Kenyan limits and checkout not tested | Upload-to-chat or upload-to-quiz is already an established workflow. Benchmark relevance and traceability against it. [Google](https://blog.google/innovation-and-ai/models-and-research/google-labs/notebooklm-app-quizzes-flashcards/) |
| Quizlet | Remembering material and preparing for assessments | Flashcards, adaptive Learn sessions, progress, generated study guides and practice tests, question types and time limits | Limited free use; paid subscriptions unlock more practice. Kenyan price not verified | Make feedback and repeat practice central, not just generation. [Quizlet help](https://help.quizlet.com/hc/en-au/articles/360030841732-Studying-on-Quizlet) |
| RemNote | Connecting notes with retention and exam preparation | PDF annotation, flashcards, spaced repetition, exam scheduler, AI quizzes/chat/grading; offline mode advertised | Published default annual-billing display: Pro USD 8/month, USD 96/year; Pro with AI USD 18/month, USD 216/year. Free tier and AI credits | A connected learning record is stronger than separate tools. Show clear usage limits and preserve access to existing study work. [Pricing](https://www.remnote.com/pricing), [subscription FAQ](https://help.remnote.com/en/articles/6084981-remnote-pro-frequently-asked-questions) |
| Anki | Forgetting learned material | Recall-based review scheduling, multimedia cards, synchronization, shared decks and extensions | Desktop and AnkiDroid are free; iOS app is paid; exact iOS price not checked | Card generation alone is weak differentiation. Useful review scheduling and easy card correction matter. [Official Anki site](https://apps.ankiweb.net/) |
| StudyFetch | Turning course materials into a complete study workflow | Notes, lecture recording, AI tutor, flashcards, quizzes, study plan/calendar, practice tests, audio and video | Free-start option advertised; paid Kenyan checkout not verified | Closest broad workflow competitor. Avoid trying to match its entire media feature set before validating one strong experience. [StudyFetch](https://www.studyfetch.com/) |
| Studocu | Finding resources linked to a university and course | The South Eastern Kenya University page lists courses and lecture materials and exposes quiz/mock-exam/summary entry points | Registration shown; premium Kenyan terms not verified | Kenyan course organization is already present internationally. Content provenance, relevance and quality need attention. [Studocu Kenya example](https://www.studocu.com/row/institution/south-eastern-kenya-university/6235) |
| ChatGPT | Getting explanations, exploring questions, and working with information | Official overview presents conversational learning, file analysis and research synthesis | Account-specific access and Kenyan price not audited | Students may use a general assistant as their baseline alternative. Nova Scholar must make the course-to-practice workflow easier to repeat. [Official overview](https://learn.chatgpt.com/) |

RemNote's FAQ also describes education/location discounts and preservation of existing work after downgrade. Its international list prices therefore cannot establish what every Kenyan student actually pays. Do not advertise Nova Scholar as “cheaper than all competitors,” especially when free alternatives exist. [RemNote FAQ](https://help.remnote.com/en/articles/6084981-remnote-pro-frequently-asked-questions).

### 3.2 Kenyan and institutional alternatives

| Product/substitute | Published offering or observed role | Relevance and recommendation |
| --- | --- | --- |
| Kenyaplex | Current homepage advertises Kenyan curriculum resources, AI voice/visual tutoring, question-photo help, M-Pesa purchases, downloads and WhatsApp support; homepage focus includes school levels | Both AI and M-Pesa already exist locally. Study its purchase/support simplicity; differentiate around tertiary course workflows. Features were read, not exercised. [Kenyaplex](https://www.kenyaplex.com/) |
| Zeraki | Learning progress tools alongside school analytics and fee-management products | Adjacent school-focused competitor and a lesson in institutional distribution. Its school-management breadth is not a reason for Nova Scholar to build an ERP. [Zeraki](https://www.zeraki.app/) |
| MwalimuPLUS | Child accounts, revision, tuition and child reports; school package advertises device/data/content access | Adjacent basic-education offering. Local education products already package access and support, not just software. The page contains legacy school terminology, so current package availability needs confirmation. [MwalimuPLUS](https://www.mwalimuplus.com/) |
| University learning management systems | Kibabii University's public learning portal identifies Moodle | Integrate with students' existing study process; test manual course-material import first. One portal is not proof of nationwide Moodle usage or a permission to retrieve course content. [Kibabii portal](https://onlinelearning.kibu.ac.ke/) |
| Class groups, shared PDFs, printed notes, classmates and YouTube | Candidate substitutes to investigate in interviews; their prevalence was not measured here | Ask students to demonstrate their actual workflow. Nova Scholar competes with its cost, convenience and trust, not only with paid apps. |

**Competitive conclusion:** There is no evidence of an empty Kenyan market for AI education. A narrower opening may exist for dependable, affordable tertiary revision that joins course material, practice, and follow-up. That opening must be demonstrated through comparative student trials.

## 4. Kenya's target market: size, structure, and access

### 4.1 Statistics that matter—and what they do not prove

| Evidence | Observation period and source | Implication and limitation |
| --- | --- | --- |
| CUE reports **628,541 degree-programme enrolments**, including **469,688 in public chartered universities** and **144,007 in private chartered universities** | 2024 observations in University Statistics 2024/2025; official indexed report passage, full PDF retrieval unsuccessful. [CUE report](https://www.cue.or.ke/index.php?Itemid=491&download=285%3Auniversity-statistics-2024-2025&id=18%3Auniversities-data-0-3&option=com_phocadownload&view=category) | Meaningful university segment, concentrated in public institutions. These are enrolments, not reachable users or customers. Avoid using internally inconsistent historical totals in the report to calculate growth. |
| KNBS reports **825,484 TVET enrolments**, up **17.3%** | 2025 observations in the 2026 Economic Survey; official indexed passage, full PDF retrieval unsuccessful. [KNBS survey](https://www.knbs.or.ke/wp-content/uploads/2026/04/2026-Economic-Survey.pdf) | TVET warrants separate discovery. Do not add it to a different-year university figure and call the result the 2026 addressable market. |
| CA/KNBS reports **58.6% internet use among ages 18–34**, with over **80% owning a mobile phone** | 2023/24 Kenya Housing Survey, published in CA's 12 August 2025 summary. [CA/KNBS summary](https://ca.go.ke/index.php/urban-rural-digital-divide-hinders-ict-uptake-joint-ca-and-knbs-survey-shows) | Phone ownership is not smartphone ownership, and these are national youth figures, not university-student adoption. Interview for device, data and electricity constraints. |
| CA records **50,175,502 smartphones**, **52,852,505 mobile broadband subscriptions**, and **53,368,939 mobile-money subscriptions** | January–March 2026 supply-side report, summary table, PDF page 7. [CA Q3 report](https://www.ca.go.ke/sites/default/files/2026-06/%20Sector%20Statistics%20Report%20Q3%202025-2026.pdf) | Supports mobile delivery and mobile-money relevance. Device/subscription counts are not unique people; they cannot be used as student market size. |
| Formal financial access reached **84.8%**; mobile money is identified as a key driver | 2024 FinAccess findings, CBK announcement 13 December 2024; indexed official page, direct fetch failed. [CBK announcement](https://www.centralbank.go.ke/2024/12/13/10960/) | Payment access supports testing M-Pesa. It does not establish spare income, ability to afford KES 299, or willingness to subscribe. |

The CA household summary reports substantial geographic and income-related differences in digital access. Recommendation: launch with reachable campus communities, include at least one setting outside Nairobi, and measure performance on the devices participants actually use. Do not infer that every Nairobi student has good connectivity or every rural student lacks it. [CA/KNBS summary](https://ca.go.ke/index.php/urban-rural-digital-divide-hinders-ict-uptake-joint-ca-and-knbs-survey-shows).

### 4.2 Segment the market by learning task

The needs and priorities below are hypotheses grounded in the product goal and sector structure, to be checked with participants.

| Segment | Learning task to investigate | Buyer and distribution hypothesis | Recommendation |
| --- | --- | --- | --- |
| Public-university undergraduates | Organize lecture notes; prepare for continuous assessments and exams; understand difficult topics | Student pays; class representatives, societies and lecturers help discovery | Primary pilot segment; recruit through existing access to campuses |
| Private-university undergraduates | Similar course needs, with potentially different support and purchasing patterns | Student or sponsor; direct and campus channels | Include as a comparison group; do not assume higher willingness to pay |
| TVET diploma/certificate learners | Unit/competency practice, technical explanations, theory and practical preparation | Student, trainer or training centre | Separate cohort and product vocabulary; validate before copying university flows |
| Professional-course and working learners | Revision around work and assessment windows | Learner or employer | Later targeted experiment once a course family shows strong retention |
| Postgraduates | Research reading, source comparison and writing support | Learner or institution | Later; source research has different quality needs and strong existing alternatives |
| Secondary/basic education | Curriculum learning involving children, teachers and parents | Often parent or school | Compare a narrow Grade 5 maths offer with tertiary; no minor onboarding until separate scope/safety approval. See the companion school study |

TVETA publishes standards for competency-based education, training and assessment, assessment centres, and assessment tools. This supports a different product approach for TVET: organize around competencies and practice evidence where appropriate. An AI quiz score must not be presented as official certification or proof of practical competence. [TVETA standards](https://www.tveta.go.ke/tvet-standards/).

**Recommended first course families:** test business/education modules and introductory computing theory where participants can supply mostly text-based material. This is a feasibility choice, not a claim that these have the highest demand. Include a small math/diagram-heavy sample to identify limits; do not claim broad engineering or health-science coverage before evaluating it.

**Recommended initial geography:** select two or three campuses where the owner can actually recruit, including a campus outside Nairobi if feasible. Relationships and participant access should determine the shortlist; a famous university name is not a distribution agreement.

### 4.3 Market structure and who controls access

Students may be users and buyers; parents or sponsors can pay without needing access to private study records. Class representatives and societies can introduce the product. Lecturers can help validate examples and authorize course material. Institutions can become later buyers but bring procurement, support and governance requirements. Payment and connectivity providers enable access; their reach does not substitute for campus distribution.

Course definitions also differ. A university unit code, a TVET competency, and a professional examination paper should not be treated as interchangeable. Proposed organization: institution → programme/qualification → unit/module → topic, with semester or assessment date where relevant. Allow manual entry initially; verify mappings before advertising official alignment. Do not build a nationwide catalogue before establishing which courses students use.

### 4.4 Size the first opportunity from reachable cohorts

National enrolment provides context, not a sales forecast. Establish a serviceable market from actual campuses, relevant courses, age eligibility, device access, permission to recruit, and available support capacity.

Illustrative pilot funnel, **all assumptions**:

- 3 reachable campuses × 4 course groups × 50 eligible students = 600 potentially reachable students.
- 40% sign up = 240 signups.
- 50% of signups complete the first study loop = 120 activated students.
- 15% of activated students buy = 18 paying students.
- At KES 299, that is **KES 5,382 in gross monthly receipts**, before costs, refunds and any applicable taxes.

These inputs must be replaced with observed counts. They show why a small pilot should be judged as learning about demand, not immediate profitability. The project's 5,000-user/20%-conversion goals remain ambitions; research has not validated them.

## 5. Potential gaps and how to test them

| Gap hypothesis | Supporting evidence/logic | Confidence | Test that could disprove it |
| --- | --- | --- | --- |
| Students need help specific to their unit and lecturer materials | Course-organized resources already exist on Studocu; broad tools establish demand for material-based workflows, not demand for Nova Scholar | Medium for relevance, low for willingness to switch | Ask students to compare the same unit task in their preferred tool and Nova Scholar; measure preference and completion |
| A connected revision loop is more useful than separate generated outputs | Quizlet, RemNote and StudyFetch emphasize practice and follow-up; current Nova Scholar modules are a foundation for this | Medium | Compare repeat use of a connected loop with chat/generation alone |
| Low-data mobile use is consequential | CA evidence shows access disparities, but does not quantify campus inconvenience | Medium | Observe study sessions on actual phones; log transferred bytes, upload failures and task completion |
| Local payment and predictable prices reduce friction | Mobile money is widespread; Kenyaplex already advertises M-Pesa | High for payment relevance, low for conversion uplift | Track purchase completion, pending-payment confusion and support requests once billing is ready |
| Scanned or photographed notes are a material barrier | Photo-help/image-to-text appears in competing offerings; Kenyan tertiary document mix is unmeasured | Low until material audit | With consent, classify sample files; prioritize OCR only if enough important material is unusable without it |
| English/Kiswahili explanation options improve understanding | Plausible localization opportunity; no representative preference evidence collected | Low | Offer paired explanations, retain formal subject terms, and compare comprehension and preference |
| Reliable citations earn enough trust to influence retention | Source-based tools create a competitive baseline | Medium | Have students open sources and check answers; measure unsupported claims and subsequent reuse |

No claim is made that competitors lack these capabilities. The question is whether Nova Scholar can deliver the combination better for a defined cohort.

## 6. Product priorities for a competitive first release

### 6.1 The core student journey

Create a course workspace → add notes/outcomes → ask a question and inspect its source → take a short diagnostic → review mistakes with explanations → save useful cards → return to a focused review session.

Example: a student adds notes for one management unit and an assessment date. Nova Scholar identifies the topics covered by those notes, asks a short practice set, explains missed answers with source references, and suggests a manageable next session. If a requested topic is absent, it says so and lets the student add material or deliberately switch to general explanation.

“Covered in your uploaded notes” and “covered by your whole course” must be separate statements. A partial upload cannot establish full syllabus coverage. Likewise, apparent mastery of a small generated question set is not a prediction of an examination grade.

### 6.2 Prioritized feature decisions

Effort is relative planning judgment, not an implementation estimate.

| Priority | Feature/outcome | Why it matters | Existing roadmap relationship | Effort / acceptance idea |
| --- | --- | --- | --- | --- |
| Now | Reliable extraction, private downloads, source locations, deletion and quota enforcement | Everything based on notes depends on correct ingestion | Complete P3/FR4 | High; supported fixtures ingest correctly; ownership and deletion tests pass |
| Now | Course/module workspaces and topic labels | Gives the product continuity across chat, documents and practice | Proposed extension across P3–P5 | Medium; the student finds every output for one unit without re-uploading |
| Now | Source-linked answers and questions, with insufficient-evidence handling | Makes checking possible | Complete P4/FR6 and P5 grounded generation | High; reviewed claims and answer keys point to supporting passages |
| Now | Explain → attempt → feedback → retry mistakes | Turns outputs into a learning task | Complete P5 and a small proposed P7 slice | Medium/high; mistakes generate a useful next activity without revealing answer keys early |
| Now | Fast mobile experience, visible progress and retryable failures | Avoids losing users before they receive value | NFR6 throughout | Medium; observed low-end-phone tasks complete without desktop help |
| Before paid pilot | Clear KES plan limits, M-Pesa purchase/reconciliation, help and receipts | Makes a paid promise usable | P2/P6 | High; duplicate, late and missing callbacks do not create incorrect access |
| Before paid pilot | Actual AI/storage cost tracking and budgets | Establishes whether KES 299/699 can support usage | P0/P2/P4–P6 | Medium/high; cost per activated and paying learner is measured |
| Next, if retention evidence supports it | Due-card queue, weak-topic summaries and assessment-date revision plan | Supplies a reason to return | Proposed acceleration of limited P7 and deferred P5 review scheduling | Medium; repeat practice occurs without repeated generation |
| Conditional | Printed-text OCR and image upload | May unlock unusable learning material | Changes D09/D16 and current file scope | High; material audit first, then measured extraction accuracy/cost |
| Conditional | Saved study packs/offline review | May help unstable connectivity | Changes the BRD exclusion of initial offline learning | Medium/high; explicit scope decision and secure shared-device behavior required |
| Conditional | Optional Kiswahili explanations | Could improve accessibility for some learners | Proposed P4 extension | Medium; bilingual subject review and student comprehension checks |
| Later | Lecturer-reviewed course packs and controlled cohort sharing | Could improve relevance and distribution | New content/permissions work; later institution direction | High; establish rights, reviewer responsibility, revocation and update process |

Keep voice tutors, generated videos, native apps, public note marketplaces, multiplayer rooms and a full school-management system out of the initial expansion. This is a focus recommendation based on implementation cost and the unanswered demand questions, not a statement that those features have no value.

### 6.3 What could become difficult to copy

The strongest potential assets are permissioned course material, a locally reviewed evaluation set, trusted recruitment/support relationships, and a reliable record of which practice helps learners improve. Accumulating private student documents without rights or consent is not a legitimate content strategy.

Use a model adapter and evaluate providers on the same source-grounding, question-validity, English/Kiswahili, latency and cost cases. The best model for writing this report is not automatically the right model for every student request. Keep low-cost deterministic scoring for objective questions; use AI where it adds measurable value. Production provider selection remains open.

## 7. Commercial recommendations

The approved catalogue is **Student KES 299/month** and **Pro KES 699/month**. Retain these as test anchors; this research does not validate either price. Suggested packaging and experiments below are proposals.

### 7.1 Let students experience the value before purchase

Test a bounded, meaningful introduction: one course, a small upload allowance, and enough questions/practice to complete the full study loop. A single unanswered teaser cannot test the value proposition. Control compute exposure and abuse. Free/trial access changes the current unresolved policy and needs an explicit decision.

Student should include the complete core learning experience within clear limits. Pro can offer higher usage and additional course capacity once demand is observed. Avoid making source traceability or basic answer quality a premium-only promise. Consider retaining read access and ordinary review of existing work after paid generation expires, with a clear retention/storage policy.

Test monthly prepayment against a short revision pass in interviews and later a controlled purchase experiment. A pass could fit assessment-driven use, but also increase payment/support overhead and reduce monthly conversion. Do not introduce annual prepayment or automatic renewal assumptions merely to improve cash flow.

### 7.2 Measure economics before increasing allowances

Use this operating calculation:

`Contribution per paying learner = collected revenue net of applicable tax/refunds/payment fees − AI − extraction/OCR − storage/egress − variable support − allocated free-use subsidy`

Allocate fixed hosting, staff and acquisition spending separately to judge overall sustainability. Test typical users and heavy users; an average can hide loss-making usage. Record input/output tokens, retrieval/embedding work and generation failures. Repeated practice on existing questions should not require a fresh paid model call.

Illustrative management target, **not a forecast or approved margin**: a 70% contribution target on KES 299 leaves KES 89.70 for combined variable costs before considering how taxes reduce net revenue. For KES 699 it leaves KES 209.70. If observed usage cannot fit the actual net-revenue budget, reduce costs or revise packaging before scaling.

At 1,000 paying users, an assumed 80% Student / 20% Pro mix produces KES 379,000 gross monthly receipts. Twelve unchanged months would produce KES 4,548,000 gross receipts; academic seasonality, churn and inactive months make that an assumption, not an annual forecast. No supplier price, payment fee or tax rate has been invented in this calculation.

### 7.3 Distribution strategy

Start with demonstrations using permissioned material from the target unit. Recruit through class representatives, societies and willing lecturers. Give representatives clear scripts about learning support and privacy; reward useful activation or retention rather than raw registrations. Seek approval for campus/group outreach and avoid bulk unsolicited messages.

Test WhatsApp as an invitation/support route because it is a plausible low-friction channel and appears in local vendors' support flows; prevalence among the target students still needs measurement. A web link and consent-based support can be tested before funding a WhatsApp bot. Do not ingest private group conversations.

Delay broad paid advertising until activation, repeat usage and cost are understood. For later institutional sales, sell an evidenced learning outcome and manageable support burden. Keep student data access separate from whoever pays.

## 8. Trust, content, and institutional acceptance

ODPC's education-sector guidance covers purpose limitation, minimization, retention, data-subject rights, processor engagement, impact assessments, registration and child-data considerations. Before a real-user pilot, assess how these apply to Nova Scholar and its AI/storage suppliers; this report is not a completed legal assessment. [ODPC guidance, December 2023](https://www.odpc.go.ke/wp-content/uploads/2024/02/ODPC-Guidance-Note-for-the-Education-Sector.pdf).

Proposed product decisions:

- Keep uploads private by default; explain whether content leaves Kenya and which providers process it.
- Define deletion/export, retention, support access and complaint handling before collecting pilot data.
- Request only the information needed for learning and billing; a study tool should not need national-ID or HELB details just to personalize revision.
- Label generated practice and feedback; reserve “lecturer reviewed” for work actually reviewed by an identified, authorized reviewer.
- Build learning support around explanations, hints and attempts. Do not promise official marks, exam predictions, or assessment completion on the student's behalf.
- Obtain rights before distributing lecturer notes, past papers or a shared course pack. Permission to upload privately does not automatically allow public redistribution.

The currently approved implementation remains adult-only. The companion study proposes testing a school offer, but moving into school-age participation, institution-wide reporting or public sharing requires a separate scope, safeguarding and data-governance review. Parent interviews and synthetic-data demonstrations can precede child onboarding.

## 9. Validation plan before committing to expansion

Sequencing update: first use the companion study's smaller family-versus-tertiary discovery and prototype comparison. The adult-specific programme below is the proposed deeper study if tertiary is selected; it is not an additional automatic recruitment commitment.

### 9.1 Discovery: 24 learners and 6 educators/support staff

Proposed recruitment: 12 public-university undergraduates, 6 private-university undergraduates, and 6 TVET learners, spread across at least three settings if access allows. Include varied devices, commuting patterns, gender and connectivity; all student participants should be adults. This is exploratory sampling, not statistically representative national research.

Ask for concrete recent behavior:

1. Show how you prepared for your last assessment, from receiving notes to checking understanding.
2. Which tools did you actually use, and what did you pay for in the last month?
3. Where did you get stuck: finding content, understanding it, remembering it, or deciding what to study?
4. Demonstrate a permitted sample file. Is it text, scan, handwritten, diagram-heavy or incomplete?
5. What makes an explanation trustworthy? How do you detect a wrong answer?
6. What happened the last time data, battery, a shared device or payment interrupted studying?
7. Compare a short Nova Scholar concept demo with the preferred existing tool. Which would you choose for the next assessment, and why?

Ask price questions after the task, using KES 299/699 and an optional short-pass concept. Prefer observed purchase behavior in a later pilot over “would you pay?” responses. Collect consent and anonymized notes; do not commit participant files or transcripts to this repository.

### 9.2 Material and product benchmark

Use permissioned or openly licensed documents. Proposed starting set: 30 files across PDF/DOCX/TXT, plus an audit sample of scanned/photographed material; include tables, long notes, missing pages and difficult examples. Create 100 educator-reviewed tasks spanning direct source questions, cross-section questions, unsupported requests, prompt injection, question generation and answer checking.

Compare Nova Scholar with Google NotebookLM and whichever tool participants actually use, using equivalent source sets and recording plan limits. Randomize task order where practical. Rate answer correctness, whether the citation really supports the claim, question/answer-key consistency, time to useful practice, mobile usability and total task cost. Record failures as well as successful demonstrations.

Do not describe vendor-generated quizzes as independent evidence of learning. For outcome testing, use comparable unseen questions reviewed by an educator; avoid reusing the exact practice questions as the post-test.

### 9.3 Proposed four-week pilot and decision gates

After the core workflow and privacy/payment prerequisites are ready, invite 60–100 adults. These thresholds are provisional management choices for discussion, not industry benchmarks or completed results.

| Measure | Definition | Initial decision threshold |
| --- | --- | --- |
| Activation | Share of eligible signups who add a usable source and complete a five-question practice with feedback within 48 hours | Aim for at least 50%; investigate failed steps before increasing acquisition |
| Week-two retained study | Share of activated students completing a meaningful practice/review session during days 8–14 | Aim for at least 35%; report actual counts and campus/course cohorts |
| Grounded answer quality | Educator-reviewed supported answers out of answerable benchmark cases | Aim for at least 90%; separately measure citation correctness |
| Unsupported-question behavior | Unsupported cases correctly identified as lacking evidence | Aim for at least 90%; avoid blending this with answerable-case accuracy |
| Critical failures | Cross-user leakage, fabricated source locations, or incorrect paid entitlement grants | Zero acceptable in release tests; small tests cannot prove absence |
| Willingness to pay | Completed purchases divided by activated eligible students offered the same plan | Treat 10–15% as an exploratory signal, not a national forecast; investigate reasons for non-purchase |
| Learning signal | Change on educator-reviewed unseen questions; delayed check after seven days where feasible | Directionally positive before stronger claims; report attrition and sample sizes |
| Unit economics | Net revenue minus measured variable cost, including free-use allocation | Positive contribution for the tested offer; inspect heavy-use cost separately |

Also record data transferred per study loop, supported-file ingestion success, median/p95 task latency, pending-payment duration and support minutes per learner. Choose operational thresholds after the baseline measurement. Segment exam weeks from ordinary weeks; a launch near exams can exaggerate continuing demand.

If most students prefer their existing free tool after using both, revisit positioning and workflow. If the main blocker is unusable scanned material, prioritize an OCR feasibility slice. If practice is useful but repeat usage is poor, test review scheduling before adding media features. If willingness to pay is weak, test a different cohort or sponsor arrangement before raising spending.

## 10. Implications for the current project roadmap

The repository already contains identity, entitlements, document ingestion foundations, tutor conversations and P5 quiz/card alpha work. P3 remains incomplete, and P4/P5 have deferred quality and production work. This research has not rerun application verification; the earlier project inspection reported 55 passing tests/163 assertions. That historical result is not evidence of real-provider quality or market fit. See [implementation log](implementation-log.md), [P3 plan](plans/p3-document-library.md), [P4 plan](plans/p4-ai-tutor.md), and [P5 plan](plans/p5-quizzes-flashcards.md).

Recommended sequence, pending owner review:

1. Review both studies and compare the adult offer with parent-funded Grade 5 maths through bounded discovery. Select one focus. If schools win, approve revised requirements and a child-service plan before implementation; the remaining sequence describes the adult route.
2. Finish the P3/P4 trustworthy source workflow, informed by the material audit. Retrieval quality and inspectable citations are the student outcomes; evaluate the retrieval approach rather than treating vector storage itself as a selling point.
3. Connect P5 practice to those same course sources, validate answer keys, and offer useful feedback. Agree short-answer rubrics before claiming automatic grading.
4. Bring forward only the small P7 part needed for “what should I revise next?” if discovery supports it. Full timetable, notification and analytics scope can follow.
5. Complete P2 cost/concurrency work and P6 sandbox billing before a paid pilot. Billing policy/design can proceed alongside learning-workflow improvements; accepting real payments requires the documented launch gate.
6. Expand courses, distribution and institutional capabilities only after pilot evidence supports them.

Changes requiring explicit roadmap/decision updates if accepted: bounded free access; course workspace model; earlier review scheduling; OCR/images; offline study packs; bilingual explanation options; shared lecturer-approved content; alternative billing periods. Offline learning is currently excluded and OCR is deferred. Neither is silently added by this report.

## 11. Model choice for this research

**Recommendation: GPT-6 Astra with web research and a high reasoning setting, if selectable.** This is a judgment about source synthesis and multi-step analysis; it is not a measured comparison of every available model. Official OpenAI guidance describes GPT-6 Astra as its strongest model for demanding work including browsing and professional tasks. [Official model guidance](https://developers.openai.com/api/docs/guides/latest-model).

The active session identifies itself as GPT-6. No session-model or reasoning-setting control is exposed to this assistant, so no switch or setting change is claimed. The research proceeded in the active session with available web tools. This recommendation does not change Nova Scholar's development Groq configuration or select its production AI provider.

## 12. Decisions to take after reading

- Approve comparison of adult tertiary revision with parent-funded Grade 5 mathematics, then select one initial market using evidence.
- Identify reachable adult and parent cohorts and qualified educator reviewers; use the companion study's preliminary comparison before a larger campus pilot.
- Decide whether to test a bounded free introduction and KES 299/699 as initial offers.
- Decide whether to bring course organization and a small review queue ahead of the full planner.
- Let the material audit determine whether to revisit OCR and offline exclusions.
- Assign a pilot budget, student recruitment owner and educator reviewer before running the pilot.

## Source register and follow-up gaps

All sources below were consulted or attempted on 17 September 2026. Links are provided at the relevant claims above; this register records the main limitations.

| Source group | Source/date or period | Use and remaining verification |
| --- | --- | --- |
| Google | Official NotebookLM mobile update, 6 November 2025 | Feature evidence; current Kenya limits and account experience need testing |
| Quizlet | Official help article, undated/current retrieval | Practice modes and free/paid boundaries; marketing URL returned 403 |
| RemNote | Pricing and subscription FAQ, current retrieval | Annual list-price snapshot and access policies; Kenya checkout/discounts not tested |
| Anki | Official homepage, current retrieval | Review scheduling and platform access; no app installed |
| StudyFetch | Official homepage, current retrieval | Advertised workflow; effectiveness claims not treated as independent evidence |
| Studocu | South Eastern Kenya University page, current retrieval | Presence of Kenyan course resources; accuracy, rights and local adoption not established |
| Kenyan vendors | Kenyaplex, Zeraki and MwalimuPLUS official pages | Published features, purchase/support patterns and market focus; no hands-on validation |
| CUE | University Statistics 2024/2025, 2024 enrolments | Official indexed text used; repeated full-download attempts failed. Recheck original tables before investor or public market-size claims |
| KNBS | Economic Survey 2026, 2025 TVET observations | Official indexed passage used; full PDF failed. No university/TVET total constructed |
| CA | January–March 2026 sector report; August 2025 household-survey summary | Supply counts separated from individual access and survey periods |
| CBK | December 2024 FinAccess announcement | Official indexed text used; financial access does not measure student disposable income |
| TVETA | Standards page, current retrieval | Assessment structure; qualification-specific mappings need educator verification |
| ODPC | Education-sector guidance, December 2023 | Relevant governance topics; business-specific legal review remains outstanding |
| OpenAI | Official model guidance and product overview, current retrieval | Research-model recommendation and general-assistant competition; account settings not changed |

Highest-value missing evidence: actual Kenyan tertiary use of competitors; current local checkout terms; course-level willingness to pay; scanned/handwritten material frequency; preferred explanation language; repeated use outside exam weeks; and measured learning improvement. These should be the next research activities before large feature expansion.
