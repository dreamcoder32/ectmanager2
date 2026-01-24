<?php
use App\Models\AttendanceDevice;
use App\Services\ZKTecoService;
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
tenancy()->initialize('append4');

class ZKUserFetch extends ZKTecoService
{
    public function fetchUsersDebug()
    {
        echo "Querying UserCount (1001)...\n";
        $command = $this->createCommand(1001);
        $this->sendCommand($this->ip, $this->port, $command);
        $reply = $this->receiveReply();
        if ($reply) {
            echo "Reply UserCount: " . bin2hex($reply) . "\n";
            if (strlen($reply) > 8) {
                // ZK Packet: CMD(2) CHK(2) SES(2) REP(2) DATA...
                $count = unpack('V', substr($reply, 8, 4))[1];
                echo "User Count: {$count}\n";
            }
        }

        echo "Querying All Users (CMD_USER_RRQ = 1100)...\n";
        $command = $this->createCommand(1100);
        $this->sendCommand($this->ip, $this->port, $command);

        $attempts = 10;
        $reply = $this->receiveReply();
        if ($reply) {
            echo "Received: " . bin2hex(substr($reply, 0, 16)) . "... (Len: " . strlen($reply) . ")\n";
            $header = unpack('v4', substr($reply, 0, 8)); // Numeric
            if ($header[1] == 2005) {
                echo "  ACK (2005)\n";
            }
            if (strlen($reply) > 8) {
                echo "  DATA FOUND!\n";
            }
        }
    }
}

$device = AttendanceDevice::first();
$zk = new ZKUserFetch();
if ($zk->connect($device)) {
    echo "Connected!\n";
    $zk->fetchUsersDebug();
    $zk->disconnect();
} else {
    echo "Failed to connect\n";
}
