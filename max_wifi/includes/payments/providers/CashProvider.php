<?php
/**
 * =====================================================
 * MAX WIFI - Cash Provider (Manual)
 * =====================================================
 * This one can immediately mark payment as success
 * because admin is recording cash payment manually.
 */
if (!class_exists('JinaLaClass')) {
    // class code hapa
}
class CashProvider implements PaymentProviderInterface
{
    public function getName(): string
    {
        return 'Cash';
    }

    public function initiate(array $data): array
    {
        $fakeTransactionId = 'CASH-' . strtoupper(bin2hex(random_bytes(5)));

        return [
            'success'        => true,
            'message'        => 'Cash payment recorded successfully.',
            'transaction_id' => $fakeTransactionId,
            'status'         => 'success',   // Cash is immediately successful
            'raw'            => [
                'provider'       => 'cash',
                'transaction_id' => $fakeTransactionId,
                'note'           => 'Manual cash payment'
            ]
        ];
    }

    public function verify(string $transactionId): array
    {
        return [
            'success' => true,
            'status'  => 'success',
            'message' => 'Cash payment verified',
            'raw'     => ['transaction_id' => $transactionId]
        ];
    }
}