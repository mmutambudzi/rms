<?php
require_once __DIR__ . '/../config/database.php';

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function hasPermission($requiredRole) {
    if (!isLoggedIn()) return false;
    
    // Admin has all permissions
    if ($_SESSION['role'] === 'admin') return true;
    
    return $_SESSION['role'] === $requiredRole;
}

function generateInvoicePDF($invoiceId) {
    // Implementation for PDF generation
    // Would use a library like TCPDF or Dompdf
}

function generateQuotationPDF($quotationId) {
    // Implementation for quotation PDF generation
}
?>