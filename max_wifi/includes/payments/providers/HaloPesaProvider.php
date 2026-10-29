<?php
/**
 * =====================================================
 * MAX WIFI - HaloPesa Provider (Placeholder)
 * =====================================================
 */
if (!class_exists('JinaLaClass')) {
    // class code hapa
}
class HaloPesaProvider implements PaymentProviderInterface
{
    public function getName(): string
    {
        return 'HaloPesa';
    }

    public function initiate(array $data): array
    {
        $fakeTransactionId = 'HALO-' . strtoupper(bin2hex(random_bytes(6)));

        return [
            'success'        => true,
            'message'        => 'HaloPesa payment initiated (Sandbox).',
            'transaction_id' => $fakeTransactionId,
            'status'         => 'pending',
            'raw'            => [
                'provider'       => 'halopesa',
                'transaction_id' => $fakeTransactionId,
                'note'           => 'Placeholder - Replace with real HaloPesa API'
            ]
        ];
    }

    public function verify(string $transactionId): array
    {
        return [
            'success' => true,
            'status'  => 'pending',
            'message' => 'HaloPesa verification (Sandbox)',
            'raw'     => ['transaction_id' => $transactionId]
        ];
    }
}