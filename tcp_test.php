<?php
use App\Models\AttendanceDevice;
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
tenancy()->initialize('append4');

class ZKTcpTest
{
    protected $socket;
    protected $ip;
    protected $port;
    protected $sessionId = 0;
    protected $replyId = 0;

    public function test($ip, $port)
    {
        $this->ip = $ip;
        $this->port = $port;
        echo "Connecting TCP to $ip:$port...\n";

        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!socket_connect($this->socket, $this->ip, $this->port)) {
            echo "Connect failed: " . socket_strerror(socket_last_error()) . "\n";
            return;
        }
        echo "Socket connected.\n";

        // 1. Try sending a command directly (e.g. GetTime or GetVersion)
        // Command 1000 (Connect) first
        $this->sendCmd(1000, 'Connect');

        // Command 11 (Version)
        $this->sendCmd(11, 'Version');

        socket_close($this->socket);
    }

    protected function sendCmd($cmdCode, $name)
    {
        echo "Sending $name ($cmdCode)...\n";

        // Create Payload (Standard ZK Packet)
        // CMD (2), Checksum (2), Session (2), Reply (2), Data...
        $this->replyId++;
        $payload = pack('vvvv', $cmdCode, 0, $this->sessionId, $this->replyId);

        // Checksum
        $checksum = 0;
        for ($i = 0; $i < strlen($payload); $i += 2) {
            $val = unpack('v', substr($payload, $i, 2))[1];
            $checksum += $val;
        }
        $checksum = ($checksum & 0xFFFF) + ($checksum >> 16);
        $checksum = ~$checksum & 0xFFFF;

        // Rebuild Payload with Checksum
        $payload = pack('vvvv', $cmdCode, $checksum, $this->sessionId, $this->replyId);

        // Wrap in TCP Header
        // 0x50 0x50 0x82 0x7d [Len low] [Len high] 0x00 0x00
        $len = strlen($payload);
        $header = pack('vvvxx', 0x5050, 0x7d82, $len);

        $packet = $header . $payload;

        socket_write($this->socket, $packet);

        // Read Reply
        $headerIn = socket_read($this->socket, 8);
        if (!$headerIn || strlen($headerIn) < 8) {
            echo "  No Header received.\n";
            return;
        }

        echo "  RX Header Raw: " . bin2hex($headerIn) . "\n";
        $h = unpack('vmagic/vproto/vlen', substr($headerIn, 0, 6));
        echo "  Header: Magic=" . dechex($h['magic']) . " Len=" . ($h['vlen'] ?? 'indx') . "\n";

        if ($h['vlen'] > 0) {
            $data = socket_read($this->socket, $h['vlen']);
            echo "  Data Raw: " . bin2hex($data) . "\n";

            if (strlen($data) >= 8) {
                // Parse ZK Packet
                $zk = unpack('vcmd/vchk/vses/vrep', substr($data, 0, 8));
                echo "  ZK CMD: {$zk['cmd']} (Ack=" . ($zk['cmd'] == 2000 ? 'OK' : 'NO') . ")\n";
                if (strlen($data) > 8) {
                    echo "  Payload: " . substr($data, 8) . "\n";
                }
            }
        }
    }
}

$device = AttendanceDevice::first();
$test = new ZKTcpTest();
$test->test($device->ip_address, $device->port);
