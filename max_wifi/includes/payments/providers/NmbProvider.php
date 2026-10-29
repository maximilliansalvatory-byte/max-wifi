<?php

if (!class_exists('NmbProvider')) {

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
                    'note'           => 'Placeholder'
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

}