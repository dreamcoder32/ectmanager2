<?php
use App\Models\AttendanceDevice;
use App\Services\ZKTecoService;
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
tenancy()->initialize('append4');

class ZKNewProtoProbe extends ZKTecoService
{
    public function probe($ip, $port)
    {
        $this->ip = $ip;
        $this->port = $port;
        $this->useTcp = true;

        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!@socket_connect($this->socket, $this->ip, $this->port)) {
            echo "Failed to connect.\n";
            return;
        }

        // Try CMD_CONNECT (1000) with New Protocol Wrapper
        // Wrapper: 5a4b, 2 byte zero, 2 byte session, 2 byte reply? No.
        // Wrapper: 5a4b (2), min/ver?

        // Standard New Protocol Wrapper (UDP style, but inside TCP frame)
        // 0x5a4b [2 byte header checksum] [2 byte session] [2 byte reply/len]

        $this->sessionId = 0;
        $this->replyId = 0;

        // Construct Inner Data (Std ZK)
        // CMD (1000)
        $cmd = 1000;
        $data = pack('V', 0); // 4 byte zero for Connect

        // Inner Packet
        $inner = pack('vvvv', $cmd, 0, $this->sessionId, $this->replyId) . $data;
        $chk = $this->createChecksum($inner);
        $inner = pack('vvvv', $cmd, $chk, $this->sessionId, $this->replyId) . $data;

        // New Proto Wrapper
        // Mag(2) + Chk(2) + Ses(2) + Len(2)
        // Checksum for wrapper is on the inner packet?
        // ZK lib says: createChecksum of inner.
        $wrapperChk = $this->createChecksum($inner);
        if ($wrapperChk == 0)
            $wrapperChk = 0xFFFF;

        $wrapper = pack('vvvv', 0x5a4b, $wrapperChk, $this->sessionId, strlen($inner));

        $fullPayload = $wrapper . $inner;

        // TCP Header
        // 5050 827d len(4) 0000
        // or 5050 827d len(2) 0000 ??
        // In my successful TCP tests, I used: 5050 827d [len 2] [0000]
        $header = pack('vvvxx', 0x5050, 0x7d82, strlen($fullPayload));

        $packet = $header . $fullPayload;

        echo "Sending TCP+NewProto: " . bin2hex($packet) . "\n";

        @socket_write($this->socket, $packet);

        // Read
        $h = @socket_read($this->socket, 8);
        if ($h) {
            echo "Header: " . bin2hex($h) . "\n";
            $u = unpack('v3', substr($h, 0, 6));
            $len = $u[3];
            if ($len > 0) {
                $body = @socket_read($this->socket, $len);
                echo "Body: " . bin2hex($body) . "\n";
            }
        }
    }
}

$device = AttendanceDevice::first();
$probe = new ZKNewProtoProbe();
$probe->probe($device->ip_address, $device->port);
