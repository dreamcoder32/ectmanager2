<?php
use App\Models\AttendanceDevice;
use App\Services\ZKTecoService;
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
tenancy()->initialize('append4');

class ZKProbe extends ZKTecoService
{
    public function probe($ip, $port)
    {
        $this->ip = $ip;
        $this->port = $port;

        $tests = [
            ['name' => 'Old Protocol, 0 bytes', 'new' => false, 'data' => ''],
            ['name' => 'Old Protocol, 4 bytes 0', 'new' => false, 'data' => pack('V', 0)],
            ['name' => 'New Protocol 0x5a4b, 0 bytes', 'new' => true, 'magic' => 0x5a4b, 'data' => ''],
            ['name' => 'New Protocol 0x5050, 0 bytes', 'new' => true, 'magic' => 0x5050, 'data' => ''],
        ];

        foreach ($tests as $test) {
            echo "Testing: {$test['name']}...\n";
            $this->useNewProtocol = $test['new'];
            // If we need to change magic, we'd need to modify createCommand.
            // For now, let's just see if any response other than 2005 comes back.

            if (!$this->socket) {
                $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
                socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 2, 'usec' => 0]);
            }

            $this->sessionId = 0;
            $this->replyId = 0;

            $cmd = $this->createCommand(1000, $test['data']);
            // Manually patch magic if needed
            if ($test['new'] && isset($test['magic']) && $test['magic'] === 0x5050) {
                $cmd[0] = chr(0x50);
                $cmd[1] = chr(0x50);
            }

            socket_sendto($this->socket, $cmd, strlen($cmd), 0, $ip, $port);
            $reply = '';
            $from = '';
            $p = 0;
            $res = @socket_recvfrom($this->socket, $reply, 1024, 0, $from, $p);

            if ($res) {
                echo "  Response! " . bin2hex($reply) . "\n";
                $header = unpack('vcmd/vchecksum/vsession/vreply', substr($reply, 0, 8));
                echo "  Parsed: CMD={$header['cmd']}, Session={$header['session']}, ReplyID={$header['reply']}\n";
            } else {
                echo "  No response.\n";
            }
            echo "\n";
        }
    }
}

$device = AttendanceDevice::first();
echo "Probing {$device->ip_address}:{$device->port}...\n";
$probe = new ZKProbe();
$probe->probe($device->ip_address, $device->port);
