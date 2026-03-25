<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;
use TCPDF;

class PdfService
{
    public function invoice(array $invoice): string
    {
        $pdf = $this->baseDocument('Invoice ' . $invoice['invoice_number']);
        $pdf->writeHTML($this->invoiceHtml($invoice), true, false, true, false, '');

        $relativePath = 'storage/pdfs/invoices/' . $invoice['invoice_number'] . '.pdf';
        $this->write($pdf, $relativePath);

        return $relativePath;
    }

    public function receipt(array $payment, array $invoice): string
    {
        $pdf = $this->baseDocument('Receipt ' . $payment['receipt_number']);
        $pdf->writeHTML($this->receiptHtml($payment, $invoice), true, false, true, false, '');

        $relativePath = 'storage/pdfs/receipts/' . $payment['receipt_number'] . '.pdf';
        $this->write($pdf, $relativePath);

        return $relativePath;
    }

    public function adoptionCertificate(array $application, array $completion): string
    {
        $pdf = $this->baseDocument('Adoption Certificate ' . $application['application_number']);
        $pdf->writeHTML($this->adoptionCertificateHtml($application, $completion), true, false, true, false, '');

        $relativePath = 'storage/pdfs/adoptions/' . $application['application_number'] . '-certificate.pdf';
        $this->write($pdf, $relativePath);

        return $relativePath;
    }

    public function report(array $report): string
    {
        $pdf = $this->baseDocument($report['title']);
        $pdf->writeHTML($this->reportHtml($report), true, false, true, false, '');

        $relativePath = 'storage/pdfs/reports/' . $report['type'] . '-' . date('Ymd-His') . '.pdf';
        $this->write($pdf, $relativePath);

        return $relativePath;
    }

    public function animalDossier(array $animal): string
    {
        $pdf = $this->baseDocument('Animal Dossier ' . ($animal['animal_id'] ?? $animal['id']));
        $pdf->writeHTML($this->animalDossierHtml($animal), true, false, true, false, '');

        $relativePath = 'storage/pdfs/animals/' . ($animal['animal_id'] ?? ('animal-' . $animal['id'])) . '-dossier.pdf';
        $this->write($pdf, $relativePath);

        return $relativePath;
    }

    private function baseDocument(string $title): TCPDF
    {
        $pdf = new TCPDF();
        $pdf->SetCreator('Catarman Animal Shelter');
        $pdf->SetAuthor('Catarman Animal Shelter');
        $pdf->SetTitle($title);
        $pdf->SetMargins(12, 12, 12);
        $pdf->SetAutoPageBreak(true, 14);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);

        return $pdf;
    }

    private function write(TCPDF $pdf, string $relativePath): void
    {
        $absolutePath = dirname(__DIR__, 2) . '/' . $relativePath;
        $directory = dirname($absolutePath);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Failed to create PDF storage directory.');
        }

        $pdf->Output($absolutePath, 'F');
    }

    private function invoiceHtml(array $invoice): string
    {
        $rows = '';
        foreach ($invoice['line_items'] as $item) {
            $rows .= sprintf(
                '<tr><td>%s</td><td align="right">%d</td><td align="right">%.2f</td><td align="right">%.2f</td></tr>',
                htmlspecialchars((string) $item['description'], ENT_QUOTES, 'UTF-8'),
                (int) $item['quantity'],
                (float) $item['unit_price'],
                (float) $item['total_price'],
            );
        }

        return sprintf(
            '<h1>Invoice %s</h1>
             <p><strong>Payor:</strong> %s<br><strong>Issue Date:</strong> %s<br><strong>Due Date:</strong> %s</p>
             <table border="1" cellpadding="6">
                <thead><tr><th>Description</th><th width="60" align="right">Qty</th><th width="90" align="right">Unit</th><th width="90" align="right">Total</th></tr></thead>
                <tbody>%s</tbody>
             </table>
             <p align="right"><strong>Subtotal:</strong> %.2f<br><strong>Tax:</strong> %.2f<br><strong>Total:</strong> %.2f<br><strong>Paid:</strong> %.2f<br><strong>Balance:</strong> %.2f</p>
             <p><strong>Notes:</strong> %s</p>
             <p><strong>Terms:</strong> %s</p>',
            htmlspecialchars((string) $invoice['invoice_number'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) $invoice['payor_name'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) $invoice['issue_date'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) $invoice['due_date'], ENT_QUOTES, 'UTF-8'),
            $rows,
            (float) $invoice['subtotal'],
            (float) $invoice['tax_amount'],
            (float) $invoice['total_amount'],
            (float) $invoice['amount_paid'],
            (float) $invoice['balance_due'],
            htmlspecialchars((string) ($invoice['notes'] ?? 'None'), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) ($invoice['terms'] ?? 'Due on or before the due date.'), ENT_QUOTES, 'UTF-8'),
        );
    }

    private function receiptHtml(array $payment, array $invoice): string
    {
        return sprintf(
            '<h1>Official Receipt %s</h1>
             <p><strong>Payment No.:</strong> %s<br><strong>Invoice:</strong> %s<br><strong>Payor:</strong> %s</p>
             <p><strong>Amount:</strong> %.2f<br><strong>Method:</strong> %s<br><strong>Date:</strong> %s</p>
             <p><strong>Reference:</strong> %s<br><strong>Notes:</strong> %s</p>',
            htmlspecialchars((string) $payment['receipt_number'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) $payment['payment_number'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) $invoice['invoice_number'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) $invoice['payor_name'], ENT_QUOTES, 'UTF-8'),
            (float) $payment['amount'],
            htmlspecialchars((string) $payment['payment_method'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) $payment['payment_date'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) ($payment['reference_number'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) ($payment['notes'] ?? 'None'), ENT_QUOTES, 'UTF-8'),
        );
    }

    private function adoptionCertificateHtml(array $application, array $completion): string
    {
        return sprintf(
            '<h1>Certificate of Adoption</h1>
             <p>This certifies that <strong>%s</strong> has successfully completed the adoption process for <strong>%s</strong>.</p>
             <p><strong>Application Number:</strong> %s<br><strong>Animal ID:</strong> %s<br><strong>Species:</strong> %s<br><strong>Completion Date:</strong> %s</p>
             <p>The Catarman Animal Shelter acknowledges the adopter&apos;s commitment to responsible pet ownership and the welfare of the adopted animal.</p>
             <p><strong>Processed By:</strong> %s</p>
             <p><strong>Notes:</strong> %s</p>',
            htmlspecialchars((string) ($application['adopter_name'] ?? 'Unknown Adopter'), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) ($application['animal_name'] ?? 'Unnamed Animal'), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) ($application['application_number'] ?? ''), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) ($application['animal_code'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) ($application['animal_species'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) ($completion['completion_date'] ?? ''), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) ($completion['processed_by_name'] ?? 'System Administrator'), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) ($completion['notes'] ?? 'None'), ENT_QUOTES, 'UTF-8'),
        );
    }

    private function reportHtml(array $report): string
    {
        $summaryRows = '';
        foreach ($report['summary'] as $label => $value) {
            $summaryRows .= sprintf(
                '<tr><td>%s</td><td align="right">%s</td></tr>',
                htmlspecialchars(ucwords(str_replace('_', ' ', (string) $label)), ENT_QUOTES, 'UTF-8'),
                htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8')
            );
        }

        $tableHead = '';
        foreach ($report['columns'] as $column) {
            $tableHead .= '<th>' . htmlspecialchars(ucwords(str_replace('_', ' ', (string) $column)), ENT_QUOTES, 'UTF-8') . '</th>';
        }

        $tableRows = '';
        foreach ($report['rows'] as $row) {
            $cells = '';
            foreach ($report['columns'] as $column) {
                $cells .= '<td>' . htmlspecialchars((string) ($row[$column] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
            }
            $tableRows .= '<tr>' . $cells . '</tr>';
        }

        return sprintf(
            '<h1>%s</h1>
             <p><strong>Generated:</strong> %s<br><strong>Date Range:</strong> %s to %s<br><strong>Grouped By:</strong> %s</p>
             <h2>Summary</h2>
             <table border="1" cellpadding="5">%s</table>
             <h2>Details</h2>
             <table border="1" cellpadding="5"><thead><tr>%s</tr></thead><tbody>%s</tbody></table>',
            htmlspecialchars((string) $report['title'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) ($report['generated_at'] ?? date(DATE_ATOM)), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) ($report['filters']['start_date'] ?? ''), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) ($report['filters']['end_date'] ?? ''), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) ($report['filters']['group_by'] ?? 'month'), ENT_QUOTES, 'UTF-8'),
            $summaryRows,
            $tableHead,
            $tableRows !== '' ? $tableRows : '<tr><td colspan="' . count($report['columns']) . '">No rows available for the selected range.</td></tr>'
        );
    }

    private function animalDossierHtml(array $animal): string
    {
        $medicalRows = '';
        foreach (($animal['medical_records'] ?? []) as $record) {
            $medicalRows .= sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
                htmlspecialchars((string) ($record['record_date'] ?? ''), ENT_QUOTES, 'UTF-8'),
                htmlspecialchars((string) ucfirst((string) ($record['procedure_type'] ?? '')), ENT_QUOTES, 'UTF-8'),
                htmlspecialchars((string) ($record['general_notes'] ?? 'No notes'), ENT_QUOTES, 'UTF-8')
            );
        }

        $invoiceRows = '';
        foreach (($animal['invoices'] ?? []) as $invoice) {
            $invoiceRows .= sprintf(
                '<tr><td>%s</td><td>%s</td><td align="right">%.2f</td><td>%s</td></tr>',
                htmlspecialchars((string) ($invoice['invoice_number'] ?? ''), ENT_QUOTES, 'UTF-8'),
                htmlspecialchars((string) ($invoice['issue_date'] ?? ''), ENT_QUOTES, 'UTF-8'),
                (float) ($invoice['total_amount'] ?? 0),
                htmlspecialchars((string) ($invoice['payment_status'] ?? ''), ENT_QUOTES, 'UTF-8')
            );
        }

        $auditRows = '';
        foreach (($animal['audit_trail'] ?? []) as $entry) {
            $auditRows .= sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                htmlspecialchars((string) ($entry['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'),
                htmlspecialchars((string) ($entry['module'] ?? ''), ENT_QUOTES, 'UTF-8'),
                htmlspecialchars((string) ($entry['action'] ?? ''), ENT_QUOTES, 'UTF-8'),
                htmlspecialchars((string) ($entry['user_name'] ?? 'System'), ENT_QUOTES, 'UTF-8')
            );
        }

        return sprintf(
            '<h1>Animal Dossier</h1>
             <p><strong>Animal ID:</strong> %s<br><strong>Name:</strong> %s<br><strong>Species:</strong> %s<br><strong>Status:</strong> %s<br><strong>Intake Date:</strong> %s</p>
             <p><strong>Breed:</strong> %s<br><strong>Gender:</strong> %s<br><strong>Size:</strong> %s<br><strong>Current Kennel:</strong> %s</p>
             <h2>Medical Records</h2>
             <table border="1" cellpadding="5"><thead><tr><th>Date</th><th>Procedure</th><th>Notes</th></tr></thead><tbody>%s</tbody></table>
             <h2>Billing</h2>
             <table border="1" cellpadding="5"><thead><tr><th>Invoice</th><th>Date</th><th>Total</th><th>Status</th></tr></thead><tbody>%s</tbody></table>
             <h2>Audit Trail</h2>
             <table border="1" cellpadding="5"><thead><tr><th>Date</th><th>Module</th><th>Action</th><th>User</th></tr></thead><tbody>%s</tbody></table>',
            htmlspecialchars((string) ($animal['animal_id'] ?? ''), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) ($animal['name'] ?? 'Unnamed'), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) ($animal['species'] ?? ''), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) ($animal['status'] ?? ''), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) ($animal['intake_date'] ?? ''), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) ($animal['breed_name'] ?? $animal['breed_other'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) ($animal['gender'] ?? ''), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) ($animal['size'] ?? ''), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) ($animal['current_kennel']['kennel_code'] ?? 'Unassigned'), ENT_QUOTES, 'UTF-8'),
            $medicalRows !== '' ? $medicalRows : '<tr><td colspan="3">No medical records available.</td></tr>',
            $invoiceRows !== '' ? $invoiceRows : '<tr><td colspan="4">No billing records available.</td></tr>',
            $auditRows !== '' ? $auditRows : '<tr><td colspan="4">No audit records available.</td></tr>'
        );
    }
}
