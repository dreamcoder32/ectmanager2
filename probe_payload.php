<?php
use App\Models\AttendanceDevice;
use App\Services\ZKTecoService;
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
tenancy()->initialize('append4');

class ZKPayloadProbe extends ZKTecoService
{
    public function probePayloads($ip, $port)
    {
        $this->ip = $ip;
        $this->port = $port;
        $this->useTcp = true;

        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!@socket_connect($this->socket, $this->ip, $this->port)) {
            echo "Failed to connect.\n";
            return;
        }

        // Connect
        $this->sessionId = 0;
        $this->replyId = 0;
        $cmd = $this->createCommand(1000, pack('V', 0));
        $this->sendProb($cmd);
        $this->recvProb();

        // 1. Try CMD_VERSION (11) with empty, null-term string, or 4-byte 0
        $tests = [
            'Cmd 11 Empty' => [$this->createCommand(11), 11],
            'Cmd 11 Null' => [$this->createCommand(11, "\0"), 11],
            'Cmd 11 Zero' => [$this->createCommand(11, pack('V', 0)), 11],
            'Cmd 11 Key ~Version' => [$this->createCommand(11, "~Version\0"), 11],
            // Try CMD_GET_FREE_SIZES (50)
            'Cmd 50' => [$this->createCommand(50), 50],
            // Try CMD_USER_TEMP_RRQ (9) - often needed to enable data
            'Cmd 9' => [$this->createCommand(9, pack('v', 5)), 9], // 5=User info?
            'Cmd 9 Empty' => [$this->createCommand(9), 9],
        ];

        foreach ($tests as $name => $t) {
            echo "Testing $name ...\n";
            $this->sendProb($t[0]);
            $this->recvProb();
        }
    }

    protected function sendProb($packet)
    {
        if ($this->useTcp) {
            $header = pack('vvvxx', 0x5050, 0x7d82, strlen($packet));
            @socket_write($this->socket, $header . $packet);
        }
    }

    protected function recvProb()
    {
        $header = @socket_read($this->socket, 8);
        if ($header && strlen($header) == 8) {
            $h = unpack('v3', substr($header, 0, 6));
            $len = $h[3];
            echo "  Hdr Len: $len\n";
            if ($len > 0) {
                $data = socket_read($this->socket, $len);
                echo "  Data: " . bin2hex($data) . "\n";
                if (strlen($data) >= 8) {
                    $zk = unpack('v4', $data);
                    echo "  CMD: {$zk[1]} SES: {$zk[3]}\n";
                    if ($zk[1] == 1000 || $zk[1] == 2005)
                        $this->sessionId = $zk[3];
                }
                if (strlen($data) > 8) {
                    echo "  Payload: " . substr($data, 8) . "\n";
                }
            }
        } else {
            echo "  No Reply\n";
        }
    }
}

$device = AttendanceDevice::first();
$probe = new ZKPayloadProbe();
$probe->probePayloads($device->ip_address, $device->port);
