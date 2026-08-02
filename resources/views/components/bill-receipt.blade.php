@props([
    'subtotal' => 0,
    'previousBalance' => 0,
    'totalPayable' => 0,
    'amountPaid' => 0,
    'remainingBalance' => 0,
    'previousBalancePayment' => 0,
    'showPreviousBalancePayment' => false
])

<div class="total-section">
    <div class="flex justify-between items-center mb-1">
        <span class="text-sm text-gray-700">Subtotal:</span>
        <span class="text-sm font-semibold text-gray-900">PKR {{ number_format($subtotal, 2) }}</span>
    </div>
    
    @if($previousBalance > 0)
    <div class="flex justify-between items-center mb-1">
        <span class="text-sm text-gray-700">Previous Balance:</span>
        <span class="text-sm font-semibold text-gray-900">PKR {{ number_format($previousBalance, 2) }}</span>
    </div>
    @endif
    
    <div class="border-t border-gray-300 my-2"></div>
    
    <div class="flex justify-between items-center mb-1">
        <span class="text-base font-bold text-gray-900">Total Payable:</span>
        <span class="text-base font-bold text-gray-900">PKR {{ number_format($totalPayable, 2) }}</span>
    </div>
    
    @if($showPreviousBalancePayment && $previousBalancePayment > 0)
    <div class="flex justify-between items-center mb-1">
        <span class="text-sm text-gray-700">Previous Balance Paid:</span>
        <span class="text-sm text-green-600">PKR {{ number_format($previousBalancePayment, 2) }}</span>
    </div>
    @endif
    
    @if($amountPaid > 0)
    <div class="flex justify-between items-center mb-1">
        <span class="text-sm text-gray-700">Amount Paid:</span>
        <span class="text-sm text-green-600">PKR {{ number_format($amountPaid, 2) }}</span>
    </div>
    @endif
    
    @if($remainingBalance > 0)
    <div class="border-t border-gray-300 my-2"></div>
    <div class="flex justify-between items-center">
        <span class="text-base font-bold text-gray-900">Remaining Balance:</span>
        <span class="text-base font-bold text-gray-900">PKR {{ number_format($remainingBalance, 2) }}</span>
    </div>
    @endif
</div>

