<?php
/**
 * =====================================================
 * MAX WIFI - CRDB Provider (Placeholder)
 * =====================================================
 */
if (!class_exists('JinaLaClass')) {
    // class code hapa
}
class CrdbProvider implements PaymentProviderInterface
{
    public function getName(): string
    {
        return 'CRDB';
    }

    public function initiate(array $data): array
    {
        $fakeTransactionId = 'CRDB-' . strtoupper(bin2hex(random_bytes(6)));

        return [
            'success'        => true,
            'message'        => 'CRDB payment initiated (Sandbox).',
            'transaction_id' => $fakeTransactionId,
            'status'         => 'pending',
            'raw'            => [
                'provider'       => 'crdb',
                'transaction_id' => $fakeTransactionId,
                'note'           => 'Placeholder - Replace with real CRDB API'
            ]
        ];
    }

    public function verify(string $transactionId): array
    {
        return [
            'success' => true,
            'status'  => 'pending',
            'message' => 'CRDB verification (Sandbox)',
            'raw'     => ['transaction_id' => $transactionId]
        ];
    }
}