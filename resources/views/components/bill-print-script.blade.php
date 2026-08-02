@php
    $company = auth()->user()?->company;
    $companySettings = is_array($company?->settings) ? $company->settings : [];
    $tpl = $companySettings['bill_template'] ?? \App\Http\Controllers\Admin\BillTemplateController::defaultTemplate();
    $coData = [
        'name' => $company?->name ?? config('app.name'),
        'phone' => $company?->phone ?? '',
        'email' => $company?->email ?? '',
        'address' => $company?->address ?? '',
        'logo_url' => $company?->logo_url ?? '',
    ];
@endphp
<script>
window.__billTpl = {!! json_encode($tpl, 15, 512) !!};
window.__billCo = {!! json_encode($coData, 15, 512) !!};

function buildBillHtml(inv) {
    const tpl = window.__billTpl;
    const co = window.__billCo;
    const sec = (tpl.sections || []).slice().sort((a, b) => a.order - b.order).filter(s => s.enabled == true || s.enabled == '1' || s.enabled === 1);
    const cols = tpl.table_columns || ['product', 'qty', 'price', 'total'];
    const color = tpl.primary_color || '#000';
    const logoPos = tpl.header_logo_position || 'center';
    const align = {left: 'left', center: 'center', right: 'right'}[logoPos] || 'center';
    const fmt = n => parseFloat(n || 0).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    let html = '';
    sec.forEach(s => {
        if (s.id === 'header') {
            html += `<div style="text-align:${align};border-bottom:2px solid ${color};padding-bottom:10px;margin-bottom:10px;">`;
            if ((tpl.show_logo == true || tpl.show_logo == '1' || tpl.show_logo === 1) && co.logo_url) {
                html += `<img src="${co.logo_url}" style="height:48px;margin:0 ${logoPos === 'center' ? 'auto' : '0'};display:block;">`;
            }
            html += `<p style="font-size:18px;font-weight:bold;margin:6px 0 2px;color:${color};">${tpl.header_company_name || co.name || 'Company'}</p>`;
            if (tpl.header_tagline) html += `<p style="font-size:10px;color:#555;font-weight:500;">${tpl.header_tagline}</p>`;
            let ct = [];
            if (tpl.header_phone || co.phone) ct.push('Ph: ' + (tpl.header_phone || co.phone));
            if (tpl.header_email || co.email) ct.push(tpl.header_email || co.email);
            if (ct.length) html += `<p style="font-size:9px;color:#888;margin-top:4px;">${ct.join(' | ')}</p>`;
            if (tpl.header_address || co.address) html += `<p style="font-size:9px;color:#888;">${tpl.header_address || co.address}</p>`;
            html += '</div>';
        }
        if (s.id === 'title') {
            const titleText = inv._titleOverride || tpl.title_text || 'INVOICE';
            const sz = (tpl.page_size || '80mm') === '80mm' ? '14px' : '18px';
            html += `<p style="text-align:center;font-weight:bold;letter-spacing:3px;color:${color};font-size:${sz};margin:8px 0;">${titleText}</p>`;
        }
        if (s.id === 'customer_info') {
            html += '<div style="font-size:11px;margin:8px 0;">';
            html += `<div style="display:flex;justify-content:space-between;"><span style="color:#888;">Customer:</span><span style="font-weight:600;">${inv.customer_name || 'N/A'}</span></div>`;
            if ((tpl.show_customer_phone == true || tpl.show_customer_phone == '1') && inv.customer_phone) html += `<div style="display:flex;justify-content:space-between;"><span style="color:#888;">Phone:</span><span>${inv.customer_phone}</span></div>`;
            if ((tpl.show_customer_address == true || tpl.show_customer_address == '1') && inv.customer_address) html += `<div style="display:flex;justify-content:space-between;"><span style="color:#888;">Address:</span><span>${inv.customer_address}</span></div>`;
            html += '</div>';
        }
        if (s.id === 'invoice_details') {
            html += '<div style="font-size:11px;margin:8px 0;border-top:1px solid #e5e7eb;padding-top:6px;">';
            if (inv.invoice_number) html += `<div style="display:flex;justify-content:space-between;"><span style="color:#888;">${inv._refLabel || 'Invoice'} #:</span><span style="font-weight:600;">${inv.invoice_number}</span></div>`;
            if (inv.invoice_date) html += `<div style="display:flex;justify-content:space-between;"><span style="color:#888;">Date:</span><span>${inv.invoice_date}</span></div>`;
            if (inv.due_date) html += `<div style="display:flex;justify-content:space-between;"><span style="color:#888;">Due:</span><span>${inv.due_date}</span></div>`;
            if (inv.status) html += `<div style="display:flex;justify-content:space-between;"><span style="color:#888;">Status:</span><span style="font-weight:600;">${(inv.status || '').toUpperCase()}</span></div>`;
            html += '</div>';
        }
        if (s.id === 'items_table' && inv.items && inv.items.length) {
            html += '<table style="width:100%;border-collapse:collapse;font-size:10px;margin:10px 0;"><thead><tr style="border-bottom:2px solid ' + color + ';">';
            if (cols.includes('product')) html += '<th style="text-align:left;padding:5px 3px;font-weight:bold;">Product</th>';
            if (cols.includes('qty')) html += '<th style="text-align:right;padding:5px 3px;font-weight:bold;">Qty</th>';
            if (cols.includes('price')) html += '<th style="text-align:right;padding:5px 3px;font-weight:bold;">Price</th>';
            if (cols.includes('discount')) html += '<th style="text-align:right;padding:5px 3px;font-weight:bold;">Disc</th>';
            if (cols.includes('tax')) html += '<th style="text-align:right;padding:5px 3px;font-weight:bold;">Tax</th>';
            if (cols.includes('total')) html += '<th style="text-align:right;padding:5px 3px;font-weight:bold;">Total</th>';
            html += '</tr></thead><tbody>';
            inv.items.forEach(it => {
                html += '<tr style="border-bottom:1px solid #e5e7eb;">';
                if (cols.includes('product')) html += `<td style="padding:5px 3px;">${it.product_name || 'N/A'}</td>`;
                if (cols.includes('qty')) html += `<td style="text-align:right;padding:5px 3px;">${fmt(it.quantity)}</td>`;
                if (cols.includes('price')) html += `<td style="text-align:right;padding:5px 3px;">${fmt(it.unit_price)}</td>`;
                if (cols.includes('discount')) html += `<td style="text-align:right;padding:5px 3px;">${fmt(it.discount)}</td>`;
                if (cols.includes('tax')) html += `<td style="text-align:right;padding:5px 3px;">${fmt(it.tax)}</td>`;
                if (cols.includes('total')) html += `<td style="text-align:right;padding:5px 3px;font-weight:600;">${fmt(it.total)}</td>`;
                html += '</tr>';
            });
            html += '</tbody></table>';
        }
        if (s.id === 'totals') {
            html += `<div style="border-top:2px solid ${color};padding-top:8px;margin:8px 0;font-size:11px;">`;
            if (inv._posTotals == true || inv._posTotals == '1' || inv._posTotals === 1) {
                const sub = parseFloat(inv.subtotal || 0);
                const prev = parseFloat(inv.previous_balance || 0);
                const payable = parseFloat(inv.total_amount || 0);
                const totalPaid = parseFloat(inv.paid_amount || 0);
                let reg = parseFloat(inv.regular_paid_amount != null ? inv.regular_paid_amount : totalPaid);
                const adj = parseFloat(inv.adj_paid_amount || 0);
                const adjBill = inv.adj_bill_number || '';
                let rem = inv.remaining_balance !== undefined && inv.remaining_balance !== null ? parseFloat(inv.remaining_balance) : (payable - totalPaid);
                rem = Math.max(0, rem);
                if (reg < 0) reg = 0;
                html += `<div style="display:flex;justify-content:space-between;"><span>Subtotal:</span><span style="font-weight:600;">PKR ${fmt(sub)}</span></div>`;
                if (prev > 0) html += `<div style="display:flex;justify-content:space-between;"><span>Previous Balance:</span><span>PKR ${fmt(prev)}</span></div>`;
                html += `<div style="border-top:1px solid #e5e7eb;margin:5px 0;padding-top:5px;"></div>`;
                html += `<div style="display:flex;justify-content:space-between;font-size:12px;font-weight:bold;color:${color};"><span>Total Payable:</span><span>PKR ${fmt(payable)}</span></div>`;
                if (reg > 0) html += `<div style="display:flex;justify-content:space-between;"><span>Amount Paid:</span><span style="color:#15803d;">PKR ${fmt(reg)}</span></div>`;
                if (adj > 0 && adjBill) html += `<div style="display:flex;justify-content:space-between;font-size:10px;"><span>Previous Balance Paid (${adjBill}):</span><span style="color:#15803d;">PKR ${fmt(adj)}</span></div>`;
                if (totalPaid > 0 && (reg > 0 || adj > 0)) {
                    html += `<div style="display:flex;justify-content:space-between;font-weight:bold;border-top:1px solid #e5e7eb;padding-top:4px;margin-top:4px;"><span>Total Paid:</span><span style="color:#15803d;">PKR ${fmt(totalPaid)}</span></div>`;
                } else if (totalPaid > 0) {
                    html += `<div style="display:flex;justify-content:space-between;"><span>Amount Paid:</span><span style="color:#15803d;">PKR ${fmt(totalPaid)}</span></div>`;
                    html += `<div style="display:flex;justify-content:space-between;font-weight:bold;border-top:1px solid #e5e7eb;padding-top:4px;margin-top:4px;"><span>Total Paid:</span><span style="color:#15803d;">PKR ${fmt(totalPaid)}</span></div>`;
                }
                html += `<div style="border-top:1px solid #e5e7eb;margin:5px 0;padding-top:5px;"></div>`;
                html += `<div style="display:flex;justify-content:space-between;font-weight:bold;"><span>Remaining Balance:</span><span>PKR ${fmt(rem)}</span></div>`;
            } else {
                html += `<div style="display:flex;justify-content:space-between;"><span>Subtotal:</span><span style="font-weight:600;">Rs ${fmt(inv.subtotal)}</span></div>`;
                if (parseFloat(inv.discount_amount) > 0) html += `<div style="display:flex;justify-content:space-between;"><span>Discount:</span><span>Rs ${fmt(inv.discount_amount)}</span></div>`;
                if (parseFloat(inv.tax_amount) > 0) html += `<div style="display:flex;justify-content:space-between;"><span>Tax:</span><span>Rs ${fmt(inv.tax_amount)}</span></div>`;
                html += `<div style="display:flex;justify-content:space-between;margin-top:4px;padding-top:4px;border-top:1px solid #e5e7eb;font-size:14px;font-weight:bold;color:${color};"><span>Total:</span><span>Rs ${fmt(inv.total_amount)}</span></div>`;
                if (parseFloat(inv.paid_amount) > 0) html += `<div style="display:flex;justify-content:space-between;color:#15803d;"><span>Paid:</span><span>Rs ${fmt(inv.paid_amount)}</span></div>`;
                const rem = parseFloat(inv.total_amount || 0) - parseFloat(inv.paid_amount || 0);
                if (rem > 0) html += `<div style="display:flex;justify-content:space-between;font-weight:bold;"><span>Remaining:</span><span>Rs ${fmt(rem)}</span></div>`;
            }
            html += '</div>';
        }
        if (s.id === 'notes' && inv.notes) html += `<div style="font-size:9px;color:#888;margin:8px 0;font-style:italic;"><span style="font-weight:600;font-style:normal;">Notes:</span> ${inv.notes}</div>`;
        if (s.id === 'footer') {
            html += `<div style="text-align:center;border-top:1px solid ${color};padding-top:8px;margin-top:12px;font-size:9px;color:#888;">`;
            if (tpl.footer_text) html += `<p style="margin:2px 0;">${tpl.footer_text}</p>`;
            if (tpl.footer_note) html += `<p style="margin:2px 0;">${tpl.footer_note}</p>`;
            html += '</div>';
        }
        if (s.id === 'signature' && (tpl.show_signature_line == true || tpl.show_signature_line == '1' || tpl.show_signature_line === 1)) {
            html += '<div style="margin-top:30px;display:flex;justify-content:space-between;font-size:10px;color:#888;">';
            html += '<div style="text-align:center;"><div style="width:100px;border-top:1px solid #999;margin-bottom:4px;"></div>Customer Sign</div>';
            html += '<div style="text-align:center;"><div style="width:100px;border-top:1px solid #999;margin-bottom:4px;"></div>Authorized Sign</div>';
            html += '</div>';
        }
    });
    return html;
}

function openBillPrintWindow(html, title) {
    const tpl = window.__billTpl;
    const font = tpl.font_family || 'Arial';
    const size = tpl.page_size || '80mm';
    const maxW = size === '80mm' ? '302px' : size === 'a5' ? '420px' : '595px';
    const fs = size === '80mm' ? '11px' : '13px';
    const pw = window.open('', '_blank');
    pw.document.write(`<!DOCTYPE html><html><head><title>${title || 'Print'}</title>
    <style>@media print{@page{margin:5mm;}}*{color:#000!important;}body{font-family:'${font}',sans-serif;padding:16px;max-width:${maxW};margin:0 auto;font-size:${fs};}</style>
    </head><body>${html}</body></html>`);
    pw.document.close();
    setTimeout(() => pw.print(), 400);
}
</script>
