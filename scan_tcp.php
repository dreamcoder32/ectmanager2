<?php
use App\Models\AttendanceDevice;
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
tenancy()->initialize('append4');

class ZKTcpScan
{
    protected $socket;
    protected $sessionId = 0;
    protected $replyId = 0;

    public function scan($ip, $port)
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 2, 'usec' => 0]);
        if (!@socket_connect($this->socket, $ip, $port)) {
            echo "Failed to connect.\n";
            return;
        }

        // Connect first
        $this->sendCmd(1000, '');

        $interesting = [
            11, // Version
            50, // FreeSizes
            1001, // UserCount
            1004, // AttCount
            1, // UDP Test / Check
            2,
            3,
            4,
            5,
            6,
            8,
            9,
            13,
            14,
            15
        ];

        foreach ($interesting as $cmd) {
            $this->sendCmd($cmd, '');
        }
    }

    protected function sendCmd($cmd, $data)
    {
        $this->replyId++;
        $payload = pack('vvvv', $cmd, 0, $this->sessionId, $this->replyId) . $data;

        // Sum Checksum
        $checksum = 0;
        for ($i = 0; $i < strlen($payload); $i += 2) {
            $u = unpack('v', substr($payload, $i, 2));
            $checksum += $u[1];
        }
        $checksum = ($checksum & 0xFFFF) + ($checksum >> 16);
        $checksum = ~$checksum & 0xFFFF;

        $payload = pack('vvvv', $cmd, $checksum, $this->sessionId, $this->replyId) . $data;

        $len = strlen($payload);
        $header = pack('vvvxx', 0x5050, 0x7d82, $len);
        socket_write($this->socket, $header . $payload);

        // Read
        $head = @socket_read($this->socket, 8);
        if ($head && strlen($head) == 8) {
            $h = unpack('v3', substr($head, 0, 6));
            $dLen = $h[3];
            echo "CMD $cmd -> Len: $dLen";

            if ($dLen > 0) {
                $body = "";
                $rem = $dLen;
                while ($rem > 0) {
                    $chunk = @socket_read($this->socket, $rem);
                    if (!$chunk)
                        break;
                    $body .= $chunk;
                    $rem -= strlen($chunk);
                }
                echo " | Payload: " . bin2hex($body) . "\n";
                // Parse CMD
                if (strlen($body) >= 8) {
                    $zk = unpack('v4', substr($body, 0, 8));
                    if ($cmd == 1000)
                        $this->sessionId = $zk[3];
                    echo "    AnswerCMD: {$zk[1]}\n";
                }
            } else {
                echo "\n";
            }
        } else {
            echo "CMD $cmd -> No Reply\n";
        }
    }
}

$device = AttendanceDevice::first();
echo "Scanning {$device->ip_address}...\n";
$scan = new ZKTcpScan();
$scan->scan($device->ip_address, $device->port);
