<?php
/**
 * =====================================================
 * MAX WIFI - Payment Provider Interface
 * =====================================================
 */

interface PaymentProviderInterface
{
    /**
     * Initialize a payment
     * 
     * @param array $data [amount, phone, reference, customer_id, package_id, etc.]
     * @return array [success => bool, message => string, transaction_id => string|null, raw => array]
     */
    public function initiate(array $data): array;

    /**
     * Verify / Check payment status
     * 
     * @param string $transactionId
     * @return array [success => bool, status => string, message => string, raw => array]
     */
    public function verify(string $transactionId): array;

    /**
     * Get provider name
     */
    public function getName(): string;
}