I need you to design and implement a production-grade, dynamically generated quotation document using HTML and CSS that will be rendered as a PDF.

The PDF must be treated as a paged document, not as a normal web page. Use advanced print CSS and CSS Paged Media techniques throughout the implementation. The final result must remain visually consistent and structurally correct regardless of whether the quotation contains a small amount of data or many pages of dynamically generated content.

Core Requirements

Use a dedicated print stylesheet with @media print and configure the physical document using @page.

The document should:

* Use A4 paper dimensions unless the existing project specifies another format.
* Have carefully controlled print margins using physical units such as mm or pt.
* Remove browser-oriented UI, unnecessary margins, shadows, interactive elements, and screen-only components during PDF generation.
* Use box-sizing: border-box consistently.
* Preserve required brand colors and backgrounds using print-color-adjust: exact and -webkit-print-color-adjust: exact.

Advanced Pagination and Fragmentation

Use modern CSS fragmentation properties:

* break-before
* break-after
* break-inside

Where renderer compatibility requires it, also include the legacy equivalents:

* page-break-before
* page-break-after
* page-break-inside

Do not allow important logical blocks to split awkwardly across pages. Examples include:

* Individual quotation items where practical
* Section headings separated from their following content
* Subtotal and total blocks
* Tax and discount summaries
* Payment information
* Signature and approval sections
* Important notes

Use break-inside: avoid selectively. Do not apply it blindly to large containers that may be taller than one page, because that can cause blank spaces, overflow, or broken pagination.

Dynamic Multi-Page Tables

The quotation may contain a dynamic number of line items and must work correctly across multiple pages.

Use semantic table structure with:

* <table>
* <thead>
* <tbody>
* <tfoot> where appropriate

Ensure table column headers repeat automatically on subsequent pages using print-compatible table header behavior.

The implementation must handle:

* One line item
* Dozens or hundreds of line items
* Very long item names
* Long descriptions
* Long SKUs or reference numbers
* Large quantities and monetary values
* Optional columns
* Discounts and taxes

Prevent horizontal overflow and broken layouts. Use appropriate techniques such as:

* table-layout
* Explicit or proportional column widths
* overflow-wrap
* word-break only when necessary
* white-space rules for monetary values and other content that should remain together

Do not force an entire large table or <tbody> to remain on one page.

Page Break Quality

The PDF must avoid poor pagination such as:

* A section heading alone at the bottom of a page
* A totals block split between two pages
* A signature block partially split
* A single isolated text line at the top or bottom of a page
* Large unexplained blank areas
* Content overlapping headers or footers

Use orphans and widows where supported to improve paragraph fragmentation.

Where CSS alone cannot guarantee a good result, structure the HTML into logical printable components so the browser or PDF renderer has sensible fragmentation boundaries.

Headers, Footers, and Page Numbers

Implement professional multi-page document behavior where supported by the current PDF renderer.

The quotation should support:

* Repeated company or document header information
* Quotation number
* Customer or document reference
* Page number
* Total page count where supported
* Repeated footer information

Before implementing this, identify the actual PDF rendering engine used by the project. Do not assume all browsers and PDF engines support the same CSS Paged Media features.

Use the renderer’s supported mechanism for page numbers and repeated headers/footers rather than relying on unsupported CSS.

Renderer Compatibility

First inspect the project and identify exactly how HTML is converted to PDF.

Possible renderers include:

* Chromium browser printing
* Puppeteer
* Playwright
* wkhtmltopdf
* Prince
* WeasyPrint
* Another HTML-to-PDF engine

Base the implementation on the actual renderer’s capabilities.

Do not use a CSS feature merely because it exists in a specification. Verify that the project’s renderer supports it. If a feature is unsupported, implement the most reliable renderer-specific alternative.

Dynamic Content Stress Testing

Test the document using multiple realistic data scenarios:

* Minimal quotation with one item
* Normal quotation with several items
* Long quotation spanning multiple pages
* Extremely long item descriptions
* Long customer and company names
* Long addresses
* Missing optional fields
* Large monetary values
* Multiple taxes and discounts
* Long terms and conditions
* Signature or approval sections near page boundaries

The layout must degrade gracefully in every case.

Layout Rules

Avoid fragile print layouts based heavily on:

* Viewport units such as vh and vw
* Fixed heights for dynamic content
* Absolute positioning for flowing document content
* Screen-oriented sticky positioning
* Large containers with overflow: hidden

Use normal document flow wherever possible.

Use Flexbox and Grid only where the renderer handles them reliably for print. Do not depend on complex layout behavior without testing multi-page fragmentation.

Use physical units such as mm or pt for page-level geometry and appropriate relative units for internal typography and spacing.

Architecture

Separate concerns clearly:

1. Document data and business logic
2. Semantic HTML structure
3. Shared visual styles
4. Print-specific styles
5. PDF-renderer-specific configuration

Do not solve pagination problems by inserting arbitrary hard-coded page breaks based on item counts. Content height is variable, so pagination should be based on actual rendered layout.

Quality Standard

The final PDF should look like a professionally typeset business document rather than a screenshot of a web page.

Prioritize:

* Reliable pagination
* Readable typography
* Consistent spacing
* Clear visual hierarchy
* Accurate table alignment
* Stable dynamic-content behavior
* Renderer compatibility
* Maintainable CSS

Before changing the implementation, inspect the existing HTML, CSS, components, data structure, and PDF-generation code. Then explain the current renderer limitations and implement the strongest supported solution.

After implementation, report:

* Which advanced print CSS techniques were used
* Which renderer-specific features were used
* Which browser or renderer limitations remain
* Which dynamic-content test cases were verified
* Any cases that still require special handling