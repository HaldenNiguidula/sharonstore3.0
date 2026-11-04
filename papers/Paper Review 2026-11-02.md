# Capstone manuscript review

Reviewed 2 November 2026

The manuscript needs substantive revision before submission. The most urgent issues concern the study timeline, evaluation calculations, evidence supporting conclusions, and consistency between the described system and the implementation.

## Review scope

The primary document reviewed was **An Integrated Business Intelligence for Sales Forecasting and Inventory Management.docx**, modified 31 October 2026. Its paragraph text matches the corresponding **(Repaired).docx** version. Alternate drafts were inventoried; they should not all be treated as current. The review also consulted the BSIS Research Framework, the capstone template, research agenda, progress report text, and selected implementation files. This is a content and document-structure review, with selected reference verification. It is not a complete reference-authenticity audit, an independent validation of participant records, or a rendered page-layout certification.

## Priority corrections

1. **Reconcile the study timeline.** The cover says March 2026, while the abstract, acknowledgements, methodology, and discussion describe a September 16, 2026 fire and later evaluations. Confirm the actual submission month and year. Record the dates of preliminary interviews, approval, recruitment, testing, and evaluation. Describe the fire as reported by the manuscript until supported by an appropriate record. The methodology still describes live deployment at Sharon Store and retirement of its logbooks despite stating that the locale ceased operations. Explain which activities occurred before the fire, which occurred elsewhere, and which remained planned. Week 18 reports that replacement-locale approval was still being pursued during September 28 to October 2; clarify the relationship to the final recruitment and approvals.

2. **Align objectives and conclusions.** The Objectives section contains four specific objectives. Conclusions says it answers eight and introduces eight module-based conclusions. Keep the approved objectives, then map each to its method, result, and conclusion. Treat additional modules as supporting features unless the objectives were formally revised.

3. **Resolve the evaluation aggregation before changing any result.** Table 5 reports 4.66 for experts, but the equal average of its eight displayed characteristic means is 4.70. This does not establish that 4.66 is wrong: weighting by questionnaire items could produce a different result. State the formula, item counts, denominators, and any weights, then recalculate from original responses. The equal average of Table 6's five displayed means is 4.506, conventionally rounding to 4.51; its reported 4.50 may reflect unrounded or differently weighted data. Verify the underlying values.

   The reported composite 4.58 equals (4.66 + 4.50) / 2. That gives each group 50% weight despite group sizes of 3 and 52. Weighting those displayed group means by respondent counts gives approximately 4.5087, or 4.51. Neither is a verified replacement score: the groups used different instruments and pooling requires a defined rationale. Prefer reporting the group results separately unless a defensible composite method is specified. Remove language suggesting that 4.58 is automatically an average across 55 equally weighted respondents.

4. **Separate perceived quality from measured effectiveness.** Favorable questionnaire ratings do not establish forecast accuracy, faster checkout, reduced spoilage, elimination of reconciliation discrepancies, or guaranteed business continuity. The conclusions currently make these stronger claims. Add objective evidence where available: a dated forecasting dataset and held-out comparisons, timed checkout trials, inventory discrepancy measurements, and documented restore trials. Otherwise narrow the conclusions to implemented functions and respondents' reported assessments. State sample and testing limitations.

5. **Make the scope consistent.** Delimitations explicitly excludes receipt-printer integration, while the abstract, results, and conclusions claim thermal printing, including 58 mm and 80 mm support. The literature synthesis also says printers will not be needed. State whether printing is implemented, optional, tested, or excluded. Distinguish browser printing from direct printer integration. Apply the same description throughout.

6. **Correct implementation descriptions.** The backup generator in api/backup.php creates plain .sql files, rather than the gzip archives claimed in Results. Google Drive uploads use a configured Google Apps Script webhook. The checked-in deployment configuration contains no backup schedule, and the inspected backup flow supports upload after a backup action when enabled. Document any actual external scheduler before claiming unattended daily backups. Distinguish local retention from Drive retention and provide restore-test evidence. Claims of immutable logs and guaranteed continuity also need evidence beyond an ordinary database logging feature.

7. **Clarify the forecasting method.** State the forecast horizon, 3/6/12-month lookback choices, ascending chronological weights, handling of months without sales, unit rounding, and the inclusion of the current partial month in the inspected implementation. The paper's restock formula should match the nonnegative implementation: max(0, forecast demand minus current stock). Explain limitations such as partial-month bias and the absence of lead time or safety stock in that calculation. Use forecast-accuracy evidence before describing the predictions as accurate or optimal.

## Research framework and completeness

- **Abstract:** Approximately 260 whitespace-separated words; the supplied framework permits a maximum of 250. It lists eight keywords, while the framework permits four to seven. Shorten the abstract after resolving the result and scope issues.
- **Ethical considerations:** The framework lists this section, but no corresponding dedicated section appears in the current manuscript. Explain consent, voluntary participation, confidentiality, data handling, and permission for naming experts and their employers. Include what actually occurred, without inventing approvals.
- **Sampling:** Explain eligibility, recruitment locations and dates, tasks performed, and counts of owners, staff, and shoppers. A sample described as 86.54% aged 19–25 needs context before being generalized to grocery management. Restrict conclusions to the accessible sample. The claim that sample-size formulas universally require a fixed population is too broad and should be revised.
- **Literature:** The four literature/study groups contain five entries each, but German (2016) and Panganiban and Bermusa (2020) require attention under the framework's five-year recency guidance. Clarify any approved exception for foundational work. Agboola et al. (2022), German (2016), and the Ilocos Norte Healthcare Study (2022) lack matching reference-list entries in the extracted current manuscript.
- **Appendices:** Compare the current A–K list with the framework's requested frequency and percentage distributions, statistical-processing and proofreading certificates, originality certificate, evaluator and researcher resumes, and source-code appendix. Confirm applicable requirements with the adviser. The presence of a questionnaire alone does not establish the evaluation totals.
- **Missing table bodies:** The manuscript contains captions for Tables 1–4, but its only three native Word tables are the evaluation summaries corresponding to Tables 5–7. No drawing was found in the caption regions for the scale or hardware/software specifications. Supply the actual tables and verify their visibility in Word.
- **Missing output figures:** Results contains captions for Figures 3–8, but no embedded drawing/image was found in that section. Insert the actual screenshots with readable labels. Progress Report 18 says screenshots were captured, so reconcile that accomplishment with the compiled file.
- **Existing comment:** One stored comment asks that the materials be prepared and placed in the appendices. Resolve it after confirming the supporting materials are complete.

## References needing correction

**Confirmed DOI mismatch:** The entry attributed to Kumar and Sharma (2024), titled “Modern inventory control frameworks and waste reduction in retail supply chains,” uses DOI 10.1016/j.jretconser.2023.103582. The author's institutional research record identifies that DOI as Yusheng Lu and Yongrui Duan's “Strategic live streaming choices for vertically differentiated products.” The related literature paragraph cannot be supported by the entry as written. Retrieve the intended original source or remove the unsupported attribution; changing the title alone would not repair the argument.

Verification source: https://scholars.cityu.edu.hk/en/publications/strategic-live-streaming-choices-for-vertically-differentiated-pr/

**ISO edition:** Name ISO/IEC 25010:2011 explicitly when describing the eight-characteristic model used. ISO identifies that edition as withdrawn and lists newer standards. If the approved questionnaire used the 2011 model, retain that historical description and explain its selection rather than silently relabeling the completed evaluation as a newer edition.

Verification source: https://www.iso.org/standard/35733.html

Other references still need verification against their original publications. In particular, verify author names, titles, publication years, journal details, institutional report URLs, and claims attributed to sources. A failed web fetch alone is not evidence that a source is fabricated. Alphabetize the reference list, complete publication details, and apply APA 7 consistently; several entries currently use “et al.” in the reference list.

## Language and presentation

- Replace repeated absolute claims such as “guaranteeing,” “completely resolved,” “eliminates,” and “perfectly align” with statements supported by actual results.
- Use past tense for completed research and testing, present tense for system behavior, and future tense only for proposed work. Several methodology paragraphs still read as a proposal.
- Remove instructional template prose, particularly the opening methodology paragraph that explains what a methodology section typically includes.
- Standardize the approved system title, heading wording, and terminology. The literature synthesis is headed “Conclusion” although the contents names it “Synthesis and Relevance to the Current Study.”
- Confirm authors' official name spellings: progress reports use “Dalusong” and “Fredrick,” while the manuscript uses “Dalusung” and “Frederick.”
- Replace approval-sheet “Insert Name Here” placeholders only with confirmed names.
- Update the contents, list of tables, and list of figures after final layout. Their page numbers are literal text and were not verified by rendering.
- The ERD paragraph enumerates ten tables while the appendix note says eleven; reconcile the inventory, including the runtime session table if applicable. Confirm claims of batch-level expiry tracking against the actual data design.

## Formatting checks and limits

All three section definitions use 8.5 by 11 inch paper and the required margins: left 1.5 inches; right, top, and bottom 1 inch. Document defaults specify Times New Roman 12 point, double spacing, and justified paragraphs. Direct formatting includes 13, 11, and 9 point runs and some single-spaced paragraphs; check these exceptions against the school's rules.

The packaged renderer could not run because LibreOffice soffice.exe was unavailable in the supplied runtime. Final pagination, clipping, screenshot readability, headers, footers, and visual placement remain unverified. No original documents were edited.

## Recommended revision order

1. Confirm the actual timeline, approved objectives, locale arrangements, author spellings, and evaluation source records.
2. Recalculate and document the evaluation results; align claims with the evidence.
3. Correct scope and implementation descriptions, then repair citations and literature support.
4. Complete tables, screenshots, ethics, and required appendices.
5. Edit prose, refresh all page lists, and perform a complete visual check in Word before submission.
