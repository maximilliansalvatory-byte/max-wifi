<?php
/**
 * =====================================================
 * MAX WIFI - Airtel Money Provider (Placeholder)
 * =====================================================
 */
if (!class_exists('JinaLaClass')) {
    // class code hapa
}
class AirtelProvider implements PaymentProviderInterface
{
    public function getName(): string
    {
        return 'Airtel Money';
    }

    public function initiate(array $data): array
    {
        $fakeTransactionId = 'AIRTEL-' . strtoupper(bin2hex(random_bytes(6)));

        return [
            'success'        => true,
            'message'        => 'Airtel Money payment initiated (Sandbox).',
            'transaction_id' => $fakeTransactionId,
            'status'         => 'pending',
            'raw'            => [
                'provider'       => 'airtel',
                'phone'          => $data['phone'] ?? '',
                'amount'         => $data['amount'],
                'transaction_id' => $fakeTransactionId,
                'note'           => 'Placeholder - Replace with real Airtel Money API'
            ]
        ];
    }

    public function verify(string $transactionId): array
    {
        return [
            'success' => true,
            'status'  => 'pending',
            'message' => 'Airtel verification (Sandbox)',
            'raw'     => ['transaction_id' => $transactionId]
        ];
    }
}