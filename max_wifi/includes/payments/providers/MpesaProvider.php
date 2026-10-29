<?php
/**
 * =====================================================
 * MAX WIFI - M-Pesa Provider (Placeholder)
 * =====================================================
 * 
 * Later you will replace the logic inside initiate() and verify()
 * with real Vodacom M-Pesa API calls.
 */
if (!class_exists('JinaLaClass')) {
    // class code hapa
}
class MpesaProvider implements PaymentProviderInterface
{
    public function getName(): string
    {
        return 'M-Pesa';
    }

    public function initiate(array $data): array
    {
        // ============================================
        // PLACEHOLDER - Replace with real M-Pesa API
        // ============================================

        // Example of what real integration will look like later:
        // 1. Get access token
        // 2. Call STK Push / C2B API
        // 3. Return transaction ID

        $fakeTransactionId = 'MPESA-' . strtoupper(bin2hex(random_bytes(6)));

        return [
            'success'        => true,
            'message'        => 'M-Pesa payment initiated successfully (Sandbox). Please complete payment on your phone.',
            'transaction_id' => $fakeTransactionId,
            'status'         => 'pending',
            'raw'            => [
                'provider'       => 'mpesa',
                'phone'          => $data['phone'] ?? '',
                'amount'         => $data['amount'],
                'reference'      => $data['reference'],
                'transaction_id' => $fakeTransactionId,
                'note'           => 'This is a placeholder response. Replace with real M-Pesa API.'
            ]
        ];
    }

    public function verify(string $transactionId): array
    {
        // PLACEHOLDER - Replace with real M-Pesa query API

        return [
            'success' => true,
            'status'  => 'pending', // or 'success' / 'failed'
            'message' => 'Payment status checked (Sandbox)',
            'raw'     => [
                'transaction_id' => $transactionId,
                'note'           => 'Placeholder verification'
            ]
        ];
    }
}