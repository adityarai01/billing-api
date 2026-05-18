# CLAUDE.md — Complete Invoice Print System for Billing/POS Software

## Project Context

Create a complete Invoice Print System for my multi-business Billing/POS Software.

My billing/sales module is already planned with these tables:

- sales
- sale_items
- sale_payments
- customers
- organizations
- organization_settings
- product_variants
- product_batches

Create invoice print system like Zoho/Odoo style.

Target businesses:
1. Medical Shop
2. Cloth Store
3. Kirana Store
4. Grocery Store

Use:
- Laravel backend
- React frontend
- TypeScript
- Tailwind CSS
- Shadcn UI
- Lucide React icons
- react-to-print for browser printing
- Laravel Blade templates
- DomPDF for PDF generation
- Queue Jobs for heavy PDF generation
- Redis cache if needed for invoice preview/details

---

# Main Goal

Create an invoice printing system with:

1. Invoice preview API
2. Thermal invoice 80mm design
3. Thermal invoice 58mm design
4. A4 GST invoice design
5. Simple retail invoice design
6. React invoice preview page
7. Print button
8. Download PDF button
9. Auto print after sale
10. WhatsApp/Email invoice-ready structure
11. Organization-wise invoice settings
12. Medical invoice batch and expiry display
13. GST invoice with HSN, CGST, SGST, IGST
14. Item-wise discount and invoice-wise discount display
15. Cancelled invoice watermark
16. Professional Zoho/Odoo style template system

Core concept:

Invoice Data + Invoice Template = Print / PDF / Download / WhatsApp / Email

---

# Backend Folder Structure

Create this backend structure:

app/
├── Http/
│   └── Controllers/
│       └── SaleInvoiceController.php
│
├── Services/
│   └── SaleInvoiceService.php
│
├── Jobs/
│   ├── GenerateSaleInvoicePdfJob.php
│   └── SendSaleInvoiceJob.php
│
└── Enums/
    ├── InvoiceTemplateTypeEnum.php
    └── InvoicePaperSizeEnum.php

Create Blade templates:

resources/views/invoices/
├── thermal-80mm.blade.php
├── thermal-58mm.blade.php
├── a4-gst.blade.php
└── simple-retail.blade.php

Create frontend structure:

src/pages/invoices/
└── InvoicePreviewPage.tsx

src/components/invoice/
├── ThermalInvoice80mm.tsx
├── ThermalInvoice58mm.tsx
├── A4GSTInvoice.tsx
├── SimpleRetailInvoice.tsx
├── InvoiceActions.tsx
└── InvoiceTemplateSelector.tsx

---

# Invoice Settings

Use organization_settings or create a dedicated invoice settings section.

Required setting fields:

- invoice_prefix
- invoice_template
- thermal_paper_size
- print_after_sale
- show_logo_on_invoice
- show_gst_on_invoice
- show_discount_on_invoice
- show_hsn_on_invoice
- show_batch_on_invoice
- show_expiry_on_invoice
- show_terms_on_invoice
- show_signature_on_invoice
- terms_conditions
- invoice_footer_message

Invoice template values:

1 = Thermal 80mm
2 = Thermal 58mm
3 = A4 GST
4 = Simple Retail

Paper size values:

1 = 80mm
2 = 58mm
3 = A4

Invoice setting rules:

- Medical shop should show batch and expiry by default.
- Cloth store should show variant attributes like color/size/material.
- Kirana/Grocery should show batch/expiry only if batch tracking is enabled.
- GST fields should show only if GST is enabled.
- Discount fields should show only if discount is enabled or discount amount > 0.
- A4 GST invoice should show full tax breakup.
- Thermal invoice should stay compact.

---

# Required Enums

Create InvoiceTemplateTypeEnum and InvoicePaperSizeEnum in app/Enums.

InvoiceTemplateTypeEnum values:
- Thermal80mm = 1
- Thermal58mm = 2
- A4GST = 3
- SimpleRetail = 4

InvoicePaperSizeEnum values:
- Thermal80mm = 1
- Thermal58mm = 2
- A4 = 3

Each enum must have:
- label() method
- options() method returning value-label array

---

# Backend API Routes

Create these API routes:

GET  /sales/invoice/{saleId}
GET  /sales/invoice/{saleId}/preview
GET  /sales/invoice/{saleId}/thermal-80mm
GET  /sales/invoice/{saleId}/thermal-58mm
GET  /sales/invoice/{saleId}/a4-gst
GET  /sales/invoice/{saleId}/simple
GET  /sales/invoice/{saleId}/pdf
POST /sales/invoice/{saleId}/generate-pdf
POST /sales/invoice/{saleId}/send-whatsapp
POST /sales/invoice/{saleId}/send-email
GET  /invoice-settings
POST /invoice-settings/update

---

# SaleInvoiceController

Create SaleInvoiceController with these methods:

- preview($saleId): Return JSON invoice data
- thermal80mm($saleId): Return thermal 80mm blade view
- thermal58mm($saleId): Return thermal 58mm blade view
- a4Gst($saleId): Return A4 GST blade view
- simple($saleId): Return simple retail blade view
- pdf($saleId): Return PDF stream/download
- generatePdf($saleId): Dispatch GenerateSaleInvoicePdfJob
- sendWhatsapp($saleId): Dispatch send invoice job or return ready response
- sendEmail($saleId): Dispatch email invoice job or return ready response

Controller rules:
- Controller should not contain heavy logic.
- Controller should call SaleInvoiceService.
- Use standard API response structure.
- Use authorization/organization check.
- One organization cannot access another organization's invoice.

---

# SaleInvoiceService

Create SaleInvoiceService with these methods:

- getInvoiceData(int $saleId): array
- getTemplateView(int $templateType): string
- generatePdf(int $saleId, int $templateType)
- getAmountInWords(float $amount): string
- calculateInvoiceSummary($sale): array
- getPrintSettings(int $organizationId): array
- prepareThermalData(int $saleId, int $paperSize): array
- prepareA4Data(int $saleId): array

Use relationships:

Sale::with([
    'customer',
    'items.product',
    'items.variant',
    'items.batch',
    'payments',
    'saleInvoiceDiscounts',
    'saleItemDiscounts',
    'organization',
    'organization.settings'
])

Important:
- Invoice should use saved sale snapshot fields from sale_items.
- Do not use current product price for old invoices.
- Product price changes later should not affect old invoices.
- Batch/expiry should come from sale_items snapshot and batch relation.
- Amount in words should be generated for A4 invoice.
- Cancelled invoices should show watermark.

---

# Invoice API Response Structure

The invoice preview API should return this structure:

{
  "shop": {
    "name": "Sharma Medical Store",
    "business_name": "Sharma Healthcare",
    "logo": "logo-url",
    "address": "Lucknow, Uttar Pradesh",
    "mobile_no": "9876543210",
    "email": "shop@email.com",
    "gstin": "09ABCDE1234F1Z5",
    "state": "Uttar Pradesh",
    "state_code": "09"
  },
  "invoice": {
    "id": 1,
    "invoice_no": "INV-0001",
    "invoice_date": "2026-05-17 10:30:00",
    "invoice_type": "POS",
    "subtotal": 2000,
    "item_discount_amount": 100,
    "invoice_discount_amount": 100,
    "coupon_discount_amount": 50,
    "promotion_discount_amount": 0,
    "total_discount_amount": 250,
    "taxable_amount": 1750,
    "cgst_amount": 45,
    "sgst_amount": 45,
    "igst_amount": 0,
    "gst_amount": 90,
    "round_off": 0,
    "grand_total": 1840,
    "paid_amount": 1500,
    "due_amount": 340,
    "amount_in_words": "One Thousand Eight Hundred Forty Rupees Only",
    "payment_status": "Partial",
    "sale_status": "Completed"
  },
  "customer": {
    "name": "Rahul",
    "mobile_no": "9999999999",
    "address": "Lucknow",
    "gstin": null
  },
  "items": [
    {
      "sr_no": 1,
      "product_name": "Dolo",
      "variant_name": "Dolo 500mg Strip",
      "hsn_code": "3004",
      "batch_no": "B102",
      "expiry_date": "2027-01",
      "qty": 2,
      "mrp": 35,
      "unit_price": 32,
      "gross_amount": 64,
      "discount_amount": 4,
      "taxable_amount": 60,
      "gst_percent": 0,
      "cgst_amount": 0,
      "sgst_amount": 0,
      "igst_amount": 0,
      "gst_amount": 0,
      "total_amount": 60
    }
  ],
  "payments": [
    {
      "payment_mode": "Cash",
      "amount": 1000,
      "reference_no": null
    },
    {
      "payment_mode": "UPI",
      "amount": 500,
      "reference_no": "UPI123"
    }
  ],
  "settings": {
    "template": "thermal_80mm",
    "show_logo": true,
    "show_gst": true,
    "show_discount": true,
    "show_batch": true,
    "show_expiry": true,
    "footer_message": "Thank You! Visit Again"
  }
}

---

# Thermal Invoice 80mm Design

Create compact 80mm thermal invoice.

Required layout:

SHOP NAME
Address
Mob: 9876543210
GSTIN: 09ABCDE1234F1Z5

Invoice: INV-0001
Date: 17-05-2026 10:30 AM
Customer: Rahul
Mobile: 9999999999

--------------------------------
Item          Qty Rate   Amount
--------------------------------
Dolo 500mg     2 32.00    64.00
Batch: B102 Exp: 01/27
Disc: 4.00

Parle-G        5 10.00    50.00
--------------------------------
Subtotal              114.00
Item Discount           4.00
Bill Discount           0.00
GST                     0.00
Round Off               0.00
Grand Total           110.00
Paid                  100.00
Due                    10.00
--------------------------------
Payment: Cash + UPI

Thank You! Visit Again

Thermal CSS:

@media print {
  @page {
    size: 80mm auto;
    margin: 0;
  }

  body {
    margin: 0;
    padding: 0;
    width: 80mm;
    font-size: 11px;
    font-family: Arial, sans-serif;
  }

  .no-print {
    display: none !important;
  }
}

.thermal-80 {
  width: 80mm;
  font-family: Arial, sans-serif;
  font-size: 11px;
  color: #000;
}

.thermal-80 table {
  width: 100%;
  border-collapse: collapse;
}

.thermal-80 .center {
  text-align: center;
}

.thermal-80 .right {
  text-align: right;
}

.thermal-80 .line {
  border-top: 1px dashed #000;
  margin: 4px 0;
}

Rules:
- Use compact layout.
- Product names can wrap.
- Show batch/expiry only when enabled.
- Show GST only when enabled.
- Show discount only when discount exists.
- Keep font readable for 80mm printer.

---

# Thermal Invoice 58mm Design

Create more compact 58mm invoice.

Rules:
- Width: 58mm
- Smaller font
- Less columns
- Product name can wrap
- Hide unnecessary details if space is less
- Show batch/expiry only if enabled
- Payment summary should be short

CSS:

@media print {
  @page {
    size: 58mm auto;
    margin: 0;
  }

  body {
    width: 58mm;
    font-size: 10px;
    font-family: Arial, sans-serif;
  }

  .no-print {
    display: none !important;
  }
}

.thermal-58 {
  width: 58mm;
  font-family: Arial, sans-serif;
  font-size: 10px;
  color: #000;
}

58mm layout should show:

SHOP NAME
Mob: 9876543210

Inv: INV-0001
Date: 17-05-2026

Item
Qty x Rate = Amount

Total
Paid
Due

Thank You

---

# A4 GST Invoice Design

Create professional A4 GST invoice.

Required sections:

1. Header:
- TAX INVOICE title
- Shop logo
- Shop name
- Business name
- Shop address
- Mobile
- Email
- GSTIN
- State and state code

2. Buyer Details:
- Customer name
- Customer mobile
- Customer address
- Customer GSTIN if available

3. Invoice Details:
- Invoice number
- Invoice date
- Payment status
- Sale status

4. Product Table columns:
- Sr
- Item
- HSN
- Batch
- Expiry
- Qty
- MRP
- Rate
- Discount
- Taxable
- GST %
- CGST
- SGST
- IGST
- Total

Rules:
- Hide Batch/Expiry columns if setting is off.
- Hide HSN if setting is off.
- Hide GST columns if GST is disabled.
- If table is wide, reduce font size.
- Use clean borders and professional spacing.

5. Summary:
- Subtotal
- Item Discount
- Invoice Discount
- Coupon Discount
- Promotion Discount
- Total Discount
- Taxable Amount
- CGST
- SGST
- IGST
- Round Off
- Grand Total
- Paid Amount
- Due Amount

6. Amount in Words
7. Terms & Conditions
8. Authorized Signature

A4 CSS:

@media print {
  @page {
    size: A4;
    margin: 10mm;
  }

  .no-print {
    display: none !important;
  }

  body {
    background: white;
  }
}

.a4-invoice {
  width: 210mm;
  min-height: 297mm;
  padding: 10mm;
  background: white;
  color: #111827;
  font-family: Arial, sans-serif;
}

.a4-invoice table {
  width: 100%;
  border-collapse: collapse;
}

.a4-invoice th,
.a4-invoice td {
  border: 1px solid #d1d5db;
  padding: 6px;
  font-size: 11px;
}

---

# Simple Retail Invoice Design

Create simple retail invoice for non-GST shops.

Show:
- Shop name
- Address
- Mobile
- Invoice number
- Invoice date
- Customer
- Product table
- Subtotal
- Discount
- Grand total
- Paid
- Due
- Footer message

Do not show GST breakup unless enabled.

Use for small shops without GST.

---

# Cancelled Invoice Watermark

If sale_status is Cancelled:
- Show large red/gray watermark: CANCELLED
- Show sale status near invoice header
- Still allow print for record purpose

CSS example:

.cancelled-watermark {
  position: absolute;
  top: 40%;
  left: 15%;
  transform: rotate(-25deg);
  font-size: 80px;
  color: rgba(220, 38, 38, 0.15);
  font-weight: bold;
  z-index: 0;
}

---

# React Frontend Requirements

Create React components:

src/pages/invoices/InvoicePreviewPage.tsx
src/components/invoice/ThermalInvoice80mm.tsx
src/components/invoice/ThermalInvoice58mm.tsx
src/components/invoice/A4GSTInvoice.tsx
src/components/invoice/SimpleRetailInvoice.tsx
src/components/invoice/InvoiceActions.tsx
src/components/invoice/InvoiceTemplateSelector.tsx

Use:
- React
- TypeScript
- Tailwind CSS
- Shadcn UI
- Lucide React
- react-to-print

Install:

npm install react-to-print

---

# Invoice Preview Page

Create page route:

/sales/invoice/:saleId

Page should show:
- Invoice preview
- Template selector
- Print button
- Download PDF button
- WhatsApp button
- Email button
- Back to POS button
- Loading state
- Error state

Actions:
- Print
- Download PDF
- Send WhatsApp
- Send Email
- Change Template
- Back to POS

Template selector options:
- Thermal 80mm
- Thermal 58mm
- A4 GST
- Simple Retail

Use InvoiceActions component for buttons.
Use InvoiceTemplateSelector component for template switching.

---

# React Print Logic

Use react-to-print.

Example:

import { useRef } from "react";
import { useReactToPrint } from "react-to-print";

export default function InvoicePreviewPage() {
  const invoiceRef = useRef<HTMLDivElement>(null);

  const handlePrint = useReactToPrint({
    contentRef: invoiceRef,
    documentTitle: "Invoice"
  });

  return (
    <>
      <div className="no-print flex gap-2">
        <button onClick={handlePrint}>Print</button>
        <button>Download PDF</button>
      </div>

      <div ref={invoiceRef}>
        {/* selected invoice template */}
      </div>
    </>
  );
}

---

# Auto Print After Sale

After sale save API success:

1. API returns sale_id
2. Redirect to /sales/invoice/{sale_id}?autoPrint=true
3. Invoice page loads data
4. Auto trigger print after 500ms

React logic:

useEffect(() => {
  if (autoPrint && invoiceData) {
    setTimeout(() => {
      handlePrint();
    }, 500);
  }
}, [autoPrint, invoiceData]);

Rules:
- Auto print only after invoice data loads.
- Add small delay before printing.
- If browser blocks auto print, show Print button.
- Respect organization setting print_after_sale.

---

# Backend PDF Generation

Use DomPDF.

Install:

composer require barryvdh/laravel-dompdf

Controller method:

public function pdf($saleId)
{
    $pdf = $this->saleInvoiceService->generatePdf(
        $saleId,
        InvoiceTemplateTypeEnum::A4GST->value
    );

    return $pdf->stream('invoice.pdf');
}

Service logic:

public function generatePdf(int $saleId, int $templateType)
{
    $data = $this->getInvoiceData($saleId);
    $view = $this->getTemplateView($templateType);

    return Pdf::loadView($view, $data);
}

Template mapping:

public function getTemplateView(int $templateType): string
{
    return match ($templateType) {
        1 => 'invoices.thermal-80mm',
        2 => 'invoices.thermal-58mm',
        3 => 'invoices.a4-gst',
        4 => 'invoices.simple-retail',
        default => 'invoices.a4-gst',
    };
}

PDF rules:
- Use A4 GST as default PDF.
- Thermal PDF optional.
- PDF generation can be queued for large invoices.
- Store generated PDF path if needed.
- WhatsApp and email should use generated PDF link/file later.

---

# GenerateSaleInvoicePdfJob

Create job:

class GenerateSaleInvoicePdfJob implements ShouldQueue
{
    public function __construct(
        public int $saleId,
        public int $templateType
    ) {}

    public function handle(SaleInvoiceService $service)
    {
        // Generate PDF
        // Store file
        // Update sale invoice_pdf_path if this field exists
    }
}

Use this job when:
- Sale completed
- User clicks generate PDF
- WhatsApp/email requires PDF

---

# SendSaleInvoiceJob

Create job placeholder:

class SendSaleInvoiceJob implements ShouldQueue
{
    public function __construct(
        public int $saleId,
        public string $channel
    ) {}

    public function handle(SaleInvoiceService $service)
    {
        // channel = whatsapp or email
        // Generate/get PDF
        // Send invoice link/file later
    }
}

Keep WhatsApp/Email integration ready but not mandatory.

---

# Invoice Data Rules

Invoice data must include:
- shop details
- customer details
- invoice details
- item details
- payment details
- discount details
- tax details
- batch details
- amount in words
- settings

For medical shop:
- Show batch number
- Show expiry date
- Show medicine variant name
- Show prescription note if required in future

For cloth store:
- Show variant attributes like Color, Size, Material if available
- Batch/expiry usually hidden

For kirana/grocery:
- Show batch/expiry if batch tracking is enabled
- Show packed/loose unit details

---

# Invoice Settings UI

Create Invoice Settings page or section.

Fields:
- Default Invoice Template
- Thermal Paper Size
- Print After Sale
- Show Logo
- Show GST
- Show Discount
- Show HSN
- Show Batch No
- Show Expiry Date
- Show Terms & Conditions
- Show Signature
- Footer Message
- Terms & Conditions

Buttons:
- Save Settings
- Preview Thermal
- Preview A4

UI rules:
- Use cards.
- Use switches for show/hide settings.
- Use select dropdown for template and paper size.
- Use textarea for terms and footer message.
- Use preview button to show sample invoice.

---

# Print CSS Rules

Global print CSS:

@media print {
  .no-print {
    display: none !important;
  }

  body {
    background: #fff !important;
  }

  .invoice-print-area {
    box-shadow: none !important;
    border: none !important;
  }
}

Thermal rules:

.thermal-80 {
  width: 80mm;
  font-family: Arial, sans-serif;
  font-size: 11px;
}

.thermal-58 {
  width: 58mm;
  font-family: Arial, sans-serif;
  font-size: 10px;
}

A4 rules:

.a4-invoice {
  width: 210mm;
  min-height: 297mm;
  padding: 10mm;
  background: white;
}

---

# Direct Thermal Printer Note

Do not implement direct ESC/POS print now unless explicitly required.

Phase 1:
React preview + browser print + thermal CSS

Phase 2:
Laravel PDF generation + download + WhatsApp/email

Phase 3:
Local print agent / desktop bridge / ESC-POS direct print

Important:
- Browser cannot silently print to thermal printer due to security.
- User usually selects printer from print dialog.
- For direct printing, local print agent is required.

---

# Important Business Rules

1. Invoice should always use saved sale snapshot data, not live product price.
2. Product price changes later should not affect old invoice.
3. Batch/expiry should come from sale_items snapshot and batch relation.
4. Item discount and invoice discount should be displayed separately.
5. GST should be shown only if enabled in organization settings.
6. Thermal invoice should be compact.
7. A4 GST invoice should be detailed.
8. Auto print should work after POS sale.
9. PDF generation should run in queue if heavy.
10. WhatsApp and Email can use generated PDF/link later.
11. Invoice number should be unique organization-wise.
12. Cancelled invoice should show CANCELLED watermark if printed.
13. For medical invoice, batch and expiry are important.
14. For cloth invoice, variant attributes like color/size should be visible if available.
15. For kirana/grocery invoice, batch/expiry should show only when enabled.

---

# Final Output Required

Generate:

1. Laravel SaleInvoiceController
2. Laravel SaleInvoiceService
3. InvoiceTemplateTypeEnum
4. InvoicePaperSizeEnum
5. Blade templates:
   - thermal-80mm.blade.php
   - thermal-58mm.blade.php
   - a4-gst.blade.php
   - simple-retail.blade.php
6. PDF generation logic using DomPDF
7. GenerateSaleInvoicePdfJob
8. SendSaleInvoiceJob placeholder
9. React InvoicePreviewPage
10. React ThermalInvoice80mm component
11. React ThermalInvoice58mm component
12. React A4GSTInvoice component
13. React SimpleRetailInvoice component
14. React InvoiceActions component
15. React InvoiceTemplateSelector component
16. Print CSS
17. Auto print logic
18. Invoice settings UI structure
19. API route list

Make it professional like Zoho/Odoo style where:

Invoice Data + Template = Print / PDF Output

Start generation step by step:

1. First generate backend controller, service, enums and routes.
2. Then generate Blade templates.
3. Then generate React invoice preview and template components.
4. Then generate print CSS and auto-print logic.
5. Then generate PDF job and WhatsApp/Email placeholders.
