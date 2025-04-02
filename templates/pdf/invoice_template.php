<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/functions.php';

function generateInvoicePDF($invoiceId) {
    $db = getDBConnection();
    
    // Get invoice data
    $invoiceStmt = $db->prepare("
        SELECT i.*, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone,
               u.full_name AS created_by_name
        FROM invoices i
        LEFT JOIN customers c ON i.customer_id = c.customer_id
        LEFT JOIN users u ON i.created_by = u.user_id
        WHERE i.invoice_id = ?
    ");
    $invoiceStmt->bind_param("i", $invoiceId);
    $invoiceStmt->execute();
    $invoice = $invoiceStmt->get_result()->fetch_assoc();
    
    if (!$invoice) {
        throw new Exception("Invoice not found");
    }
    
    // Get invoice items
    $itemsStmt = $db->prepare("
        SELECT oi.*, mi.name AS item_name
        FROM order_items oi
        JOIN menu_items mi ON oi.menu_item_id = mi.item_id
        WHERE oi.order_id = ?
    ");
    $itemsStmt->bind_param("i", $invoice['order_id']);
    $itemsStmt->execute();
    $items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Include TCPDF library
    require_once __DIR__ . '/../../../lib/tcpdf/tcpdf.php';
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Restaurant Management System');
    $pdf->SetTitle('Invoice #' . $invoice['invoice_number']);
    $pdf->SetSubject('Invoice');
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(10);
    
    // Add a page
    $pdf->AddPage();
    
    // Logo
    $pdf->Image(__DIR__ . '/../../../assets/images/logo.png', 15, 10, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
    
    // Invoice header
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'INVOICE', 0, 1, 'R');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, '#' . $invoice['invoice_number'], 0, 1, 'R');
    
    // Restaurant info
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Restaurant Name', 0, 1);
    $pdf->Cell(0, 5, '123 Restaurant Street', 0, 1);
    $pdf->Cell(0, 5, 'Food City, FC 12345', 0, 1);
    $pdf->Cell(0, 5, 'Phone: (123) 456-7890', 0, 1);
    $pdf->Cell(0, 5, 'Email: info@restaurant.com', 0, 1);
    $pdf->Ln(10);
    
    // Invoice details
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 5, 'Invoice Details', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    
    $pdf->Cell(40, 5, 'Issue Date:', 0, 0);
    $pdf->Cell(0, 5, date('F j, Y', strtotime($invoice['issue_date'])), 0, 1);
    
    $pdf->Cell(40, 5, 'Due Date:', 0, 0);
    $pdf->Cell(0, 5, date('F j, Y', strtotime($invoice['due_date'])), 0, 1);
    
    $pdf->Cell(40, 5, 'Status:', 0, 0);
    $pdf->Cell(0, 5, ucfirst($invoice['status']), 0, 1);
    $pdf->Ln(5);
    
    // Customer info
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 5, 'Bill To:', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    
    $pdf->Cell(0, 5, $invoice['customer_name'] ?: 'Walk-in Customer', 0, 1);
    if ($invoice['customer_email']) {
        $pdf->Cell(0, 5, $invoice['customer_email'], 0, 1);
    }
    if ($invoice['customer_phone']) {
        $pdf->Cell(0, 5, $invoice['customer_phone'], 0, 1);
    }
    $pdf->Ln(10);
    
    // Items table
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(100, 6, 'Description', 1, 0);
    $pdf->Cell(25, 6, 'Quantity', 1, 0, 'C');
    $pdf->Cell(25, 6, 'Unit Price', 1, 0, 'R');
    $pdf->Cell(25, 6, 'Amount', 1, 1, 'R');
    
    $pdf->SetFont('helvetica', '', 10);
    foreach ($items as $item) {
        $pdf->Cell(100, 6, $item['item_name'], 1, 0);
        $pdf->Cell(25, 6, $item['quantity'], 1, 0, 'C');
        $pdf->Cell(25, 6, '$' . number_format($item['unit_price'], 2), 1, 0, 'R');
        $pdf->Cell(25, 6, '$' . number_format($item['unit_price'] * $item['quantity'], 2), 1, 1, 'R');
    }
    
    // Totals
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(150, 6, 'Subtotal:', 1, 0, 'R');
    $pdf->