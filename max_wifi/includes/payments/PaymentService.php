<?php
/**
 * =====================================================
 * MAX WIFI - Payment Service (Main Layer)
 * =====================================================
 *
 * HOW TO USE:
 *
 * require_once __DIR__ . '/../includes/payments/PaymentService.php';
 *
 * $paymentService = new PaymentService($pdo);
 * $result = $paymentService->initiate([
 *     'provider'    => 'mpesa',
 *     'amount'      => 2000,
 *     'phone'       => '2557XXXXXXXX',
 *     'customer_id' => 5,
 *     'package_id'  => 1,
 *     'reference'   => 'INV-12345'
 * ]);
 */

require_once __DIR__ . '/PaymentProviderInterface.php';
require_once __DIR__ . '/providers/MpesaProvider.php';
require_once __DIR__ . '/providers/AirtelProvider.php';
require_once __DIR__ . '/providers/HaloPesaProvider.php';
require_once __DIR__ . '/providers/TigoProvider.php';
require_once __DIR__ . '/providers/NmbProvider.php';
require_once __DIR__ . '/providers/CrdbProvider.php';
require_once __DIR__ . '/providers/CashProvider.php';

class PaymentService
{
    private PDO $pdo;
    private array $providers = [];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

        // Register available providers
        $this->providers = [];
        
        if (class_exists('MpesaProvider')) {
            $this->providers['mpesa'] = new MpesaProvider();
        }
        if (class_exists('AirtelProvider')) {
            $this->providers['airtel'] = new AirtelProvider();
        }
        if (class_exists('HaloPesaProvider')) {
            $this->providers['halopesa'] = new HaloPesaProvider();
        }
        if (class_exists('TigoProvider')) {
            $this->providers['tigo'] = new TigoProvider();
        }
        if (class_exists('NmbProvider')) {
            $this->providers['nmb'] = new NmbProvider();
        }
        if (class_exists('CrdbProvider')) {
            $this->providers['crdb'] = new CrdbProvider();
        }
        if (class_exists('CashProvider')) {
            $this->providers['cash'] = new CashProvider();
        }
    }

    /**
     * Initiate a payment
     */
    public function initiate(array $data): array
    {
        $providerKey = strtolower($data['provider'] ?? '');

        if (!isset($this->providers[$providerKey])) {
            return [
                'success' => false,
                'message' => 'Payment provider not supported.'
            ];
        }

        $provider = $this->providers[$providerKey];

        // Create pending payment record first
        $uuid = $this->generateUuid();
        $reference = $data['reference'] ?? ('PAY-' . strtoupper(substr($uuid, 0, 8)));

        $stmt = $this->pdo->prepare("
            INSERT INTO payments 
            (uuid, customer_id, package_id, payment_method_id, amount, currency, reference, phone, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'TZS', ?, ?, 'pending', NOW())
        ");

        // Get payment_method_id from slug
        $methodId = $this->getPaymentMethodId($providerKey);

        $stmt->execute([
            $uuid,
            $data['customer_id'] ?? null,
            $data['package_id'],
            $methodId,
            $data['amount'],
            $reference,
            $data['phone'] ?? null
        ]);

        $paymentId = $this->pdo->lastInsertId();

        // Call the provider
        $result = $provider->initiate([
            'amount'      => $data['amount'],
            'phone'       => $data['phone'] ?? '',
            'reference'   => $reference,
            'payment_id'  => $paymentId,
            'uuid'        => $uuid,
            'customer_id' => $data['customer_id'] ?? null,
            'package_id'  => $data['package_id']
        ]);

        // Update payment with gateway response
        $status = $result['success'] ? ($result['status'] ?? 'pending') : 'failed';

        $update = $this->pdo->prepare("
            UPDATE payments 
            SET status = ?, gateway_response = ?, reference = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $update->execute([
            $status,
            json_encode($result['raw'] ?? $result),
            $result['transaction_id'] ?? $reference,
            $paymentId
        ]);

        return [
            'success'        => $result['success'],
            'message'        => $result['message'],
            'payment_id'     => $paymentId,
            'uuid'           => $uuid,
            'transaction_id' => $result['transaction_id'] ?? null,
            'status'         => $status
        ];
    }

    /**
     * Verify a payment
     */
    public function verify(string $providerKey, string $transactionId): array
    {
        $providerKey = strtolower($providerKey);

        if (!isset($this->providers[$providerKey])) {
            return [
                'success' => false,
                'message' => 'Provider not found.'
            ];
        }

        $provider = $this->providers[$providerKey];
        return $provider->verify($transactionId);
    }

    /**
     * Get payment method ID by slug
     */
    private function getPaymentMethodId(string $slug): int
    {
        $stmt = $this->pdo->prepare("SELECT id FROM payment_methods WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        $id = $stmt->fetchColumn();

        return $id ? (int)$id : 1; // fallback
    }

    /**
     * Generate UUID
     */
    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}