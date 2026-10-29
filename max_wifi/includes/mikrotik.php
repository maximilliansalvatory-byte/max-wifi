<?php
/**
 * =====================================================
 * MAX WIFI - MikroTik API Helper
 * Phase 10: MikroTik Integration
 * =====================================================
 *
 * HOW TO USE:
 * 
 * require_once __DIR__ . '/../includes/mikrotik.php';
 * 
 * $mikrotik = new MikroTik('192.168.88.1', 'admin', 'password', 8728);
 * 
 * if ($mikrotik->connect()) {
 *     $users = $mikrotik->getActiveUsers();
 *     $mikrotik->disconnect();
 * }
 */

class MikroTik
{
    private string $ip;
    private string $username;
    private string $password;
    private int $port;
    private $socket = null;
    private bool $connected = false;

    public function __construct(string $ip, string $username, string $password, int $port = 8728)
    {
        $this->ip       = $ip;
        $this->username = $username;
        $this->password = $password;
        $this->port     = $port;
    }

    /**
     * Connect to MikroTik API
     */
    public function connect(): bool
    {
        $this->socket = @fsockopen($this->ip, $this->port, $errno, $errstr, 5);

        if (!$this->socket) {
            return false;
        }

        // Login
        $this->write('/login');
        $response = $this->read();

        if (isset($response[0]) && $response[0] === '!done') {
            $this->connected = true;
            return true;
        }

        // Try with username/password
        $this->write('/login', [
            'name'     => $this->username,
            'password' => $this->password
        ]);

        $response = $this->read();

        if (isset($response[0]) && strpos($response[0], '!done') !== false) {
            $this->connected = true;
            return true;
        }

        $this->disconnect();
        return false;
    }

    /**
     * Disconnect
     */
    public function disconnect(): void
    {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
        }
        $this->connected = false;
    }

    /**
     * Check if connected
     */
    public function isConnected(): bool
    {
        return $this->connected;
    }

    /**
     * Get active Hotspot users (example)
     */
    public function getActiveUsers(): array
    {
        if (!$this->connected) return [];

        $this->write('/ip/hotspot/active/print');
        return $this->read();
    }

    /**
     * Write command to MikroTik
     */
    private function write(string $command, array $params = []): void
    {
        $data = $command . "\n";
        foreach ($params as $key => $value) {
            $data .= "=$key=$value\n";
        }
        $data .= "\n";

        fwrite($this->socket, $data);
    }

    /**
     * Read response from MikroTik
     */
    private function read(): array
    {
        $response = [];
        while (true) {
            $line = fgets($this->socket);
            if ($line === false || trim($line) === '') break;
            $response[] = trim($line);
        }
        return $response;
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}