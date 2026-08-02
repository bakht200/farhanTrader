/**
 * Reusable Bill/Receipt Helper Functions
 * Generates standardized bill totals section HTML
 */

/**
 * Generate bill totals section HTML for print/display
 * @param {Object} data - Bill data object
 * @param {number} data.subtotal - Subtotal amount
 * @param {number} data.previousBalance - Previous balance (optional)
 * @param {number} data.totalPayable - Total payable amount
 * @param {number} data.amountPaid - Amount paid (optional)
 * @param {number} data.remainingBalance - Remaining balance (optional)
 * @param {boolean} isPrint - Whether this is for print (uses inline styles)
 * @returns {string} HTML string for totals section
 */
function generateBillTotals(data, isPrint = false) {
    const {
        subtotal = 0,
        previousBalance = 0,
        totalPayable = 0,
        amountPaid = 0,
        remainingBalance = 0,
        previousBalancePayment = 0
    } = data;

    if (isPrint) {
        // Print format with inline styles
        let html = `
            <div class="total-section">
                <p style="font-size: 10px; margin-bottom: 3px; display: flex; justify-content: space-between;">
                    <span>Subtotal:</span>
                    <span>PKR ${parseFloat(subtotal || 0).toFixed(2)}</span>
                </p>`;
        
        if (previousBalance > 0) {
            html += `
                <p style="font-size: 10px; margin-bottom: 3px; display: flex; justify-content: space-between;">
                    <span>Previous Balance:</span>
                    <span>PKR ${parseFloat(previousBalance).toFixed(2)}</span>
                </p>`;
        }
        
        html += `
                <p style="border-top: 1px solid #ddd; margin: 5px 0; padding-top: 5px;"></p>
                <p style="font-size: 12px; margin-bottom: 3px; display: flex; justify-content: space-between; font-weight: bold;">
                    <span>Total Payable:</span>
                    <span>PKR ${parseFloat(totalPayable || 0).toFixed(2)}</span>
                </p>`;
        
        if (amountPaid > 0) {
            html += `
                <p style="font-size: 10px; margin-bottom: 3px; display: flex; justify-content: space-between;">
                    <span>Amount Paid:</span>
                    <span>PKR ${parseFloat(amountPaid).toFixed(2)}</span>
                </p>`;
        }
        
        if (remainingBalance > 0) {
            html += `
                <p style="border-top: 1px solid #ddd; margin: 5px 0; padding-top: 5px;"></p>
                <p style="font-size: 12px; margin-top: 5px; display: flex; justify-content: space-between; font-weight: bold; color: #000;">
                    <span>Remaining Balance:</span>
                    <span>PKR ${parseFloat(remainingBalance).toFixed(2)}</span>
                </p>`;
        }
        
        html += `</div>`;
        return html;
    } else {
        // Display format with Tailwind classes
        let html = `
            <div class="text-right mb-4 space-y-1">
                <div class="flex justify-between items-center mb-1">
                    <span class="text-sm text-gray-700">Subtotal:</span>
                    <span class="text-sm font-semibold text-gray-900">PKR ${parseFloat(subtotal || 0).toFixed(2)}</span>
                </div>`;
        
        if (previousBalance > 0) {
            html += `
                <div class="flex justify-between items-center mb-1">
                    <span class="text-sm text-gray-700">Previous Balance:</span>
                    <span class="text-sm font-semibold text-gray-900">PKR ${parseFloat(previousBalance).toFixed(2)}</span>
                </div>`;
        }
        
        html += `
                <div class="border-t border-gray-300 my-2"></div>
                <div class="flex justify-between items-center mb-1">
                    <span class="text-base font-bold text-gray-900">Total Payable:</span>
                    <span class="text-base font-bold text-gray-900">PKR ${parseFloat(totalPayable || 0).toFixed(2)}</span>
                </div>`;
        
        if (amountPaid > 0) {
            html += `
                <div class="flex justify-between items-center mb-1">
                    <span class="text-sm text-gray-700">Amount Paid:</span>
                    <span class="text-sm text-green-600">PKR ${parseFloat(amountPaid).toFixed(2)}</span>
                </div>`;
        }
        
        if (remainingBalance > 0) {
            html += `
                <div class="border-t border-gray-300 my-2"></div>
                <div class="flex justify-between items-center">
                    <span class="text-base font-bold text-red-600">Remaining Balance:</span>
                    <span class="text-base font-bold text-red-600">PKR ${parseFloat(remainingBalance).toFixed(2)}</span>
                </div>`;
        }
        
        html += `</div>`;
        return html;
    }
}

