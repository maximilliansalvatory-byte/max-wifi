<?php
/**
 * =====================================================
 * MAX WIFI - NMB Provider (Placeholder)
 * =====================================================
 */
if (!class_exists('JinaLaClass')) {
    // class code hapa
}
class NmbProvider implements PaymentProviderInterface
{
    public function getName(): string
    {
        return 'NMB';
    }

    public function initiate(array $data): array
    {
        $fakeTransactionId = 'NMB-' . strtoupper(bin2hex(random_bytes(6)));

        return [
            'success'        => true,
            'message'        => 'NMB payment initiated (Sandbox).',
            'transaction_id' => $fakeTransactionId,
            'status'         => 'pending',
            'raw'            => [
                'provider'       => 'nmb',
                'transaction_id' => $fakeTransactionId,
                'note'           => 'Placeholder - Replace with real NMB API'
            ]
        ];
    }

    public function verify(string $transactionId): array
    {
        return [
            'success' => true,
            'status'  => 'pending',
            'message' => 'NMB verification (Sandbox)',
            'raw'     => ['transaction_id' => $transactionId]
        ];
    }
}