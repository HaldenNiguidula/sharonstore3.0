# System diagram sources

The 13 Mermaid definitions correspond, in display order, to the 11 sections in `system_diagrams.html`. Section 7 contains the overall, cashier and admin flowcharts. Each HTML source drawer contains the same definition as its rendered diagram; keep both synchronized when editing.

The physical schema is based on `sql/sharonstore.sql`, with `tbl_sessions` and nullable associations supported by `includes/db.php`. Dotted ERD links indicate application associations rather than foreign keys enforced by the supplied SQL dump. Suppliers and sessions have no relational link to inventory and users respectively.

The paper-specific SVG files provide compact, editable versions of Appendix G (DFD), Appendix I (key-focused ERD) and Appendix J (flowchart). The paper ERD summarizes fields; the HTML ERD lists all schema fields. Both current Word papers and the matching images in `papers/extracted_diagrams` use the revised paper figures. Archived and backup papers retain their historical diagrams.

Research participant counts follow the current papers: 52 retail participants and 3 IT experts. They are documentation values, not a claim independently verified by the application code.
