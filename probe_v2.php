<?php
use App\Models\AttendanceDevice;
use App\Services\ZKTecoService;
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
tenancy()->initialize('append4');

class ZKProbeV2 extends ZKTecoService
{
    public function createNewHeader($magic, $session, $reply, $payload)
    {
        $len = strlen($payload);
        // Try various header formats
        $headers = [
            'Standard 16-byte' => pack('vvvvV', $magic, 0, $session, $len, 0),
            'TAD-like 8-byte' => pack('vvvv', $magic, 0, $session, $len),
            'SilkID-like' => pack('CCCCvvvv', 0x5a, 0x4b, 0x00, 0x00, $session, $reply, $len, 0),
        ];

        return $headers;
    }

    public function probe($ip, $port)
    {
        $this->ip = $ip;
        $this->port = $port;
        $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 1, 'usec' => 500000]);

        $magics = [0x5a4b, 0x5050];
        $payload = $this->createCommand(1000); // Standard 8-byte CMD_CONNECT

        foreach ($magics as $magic) {
            $headers = $this->createNewHeader($magic, 0, 0, $payload);
            foreach ($headers as $name => $h) {
                echo "Testing Magic 0x" . dechex($magic) . " with {$name} header...\n";
                // Add checksum to header if needed
                // For now just try as is
                $packet = $h . $payload;
                socket_sendto($this->socket, $packet, strlen($packet), 0, $ip, $port);
                $reply = '';
                $from = '';
                $p = 0;
                if (@socket_recvfrom($this->socket, $reply, 1024, 0, $from, $p)) {
                    echo "  RESPONSE! " . bin2hex($reply) . "\n";
                } else {
                    echo "  No response.\n";
                }
            }
        }
    }
}

$device = AttendanceDevice::first();
$probe = new ZKProbeV2();
$probe->probe($device->ip_address, $device->port);
