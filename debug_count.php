<?php
use App\Models\AttendanceDevice;
use App\Services\ZKTecoService;
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
tenancy()->initialize('append4');

class ZKCount extends ZKTecoService
{
    public function getCountDebug()
    {
        echo "Querying AttCount (1004)...\n";
        $command = $this->createCommand(1004);
        $this->sendCommand($this->ip, $this->port, $command);

        $reply = $this->receiveReply();
        if ($reply) {
            echo "Reply: " . bin2hex($reply) . "\n";
            $header = unpack('vcmd/vchecksum/vsession/vreply', substr($reply, 0, 8));
            echo "CMD: {$header['cmd']}\n";
            if (strlen($reply) > 8) {
                $count = unpack('V', substr($reply, 8, 4))[1];
                echo "Count: {$count}\n";
            }
        } else {
            echo "No reply\n";
        }
    }
}

$device = AttendanceDevice::first();
$zk = new ZKCount();
if ($zk->connect($device)) {
    echo "Connected!\n";
    $zk->getCountDebug();
    $zk->disconnect();
} else {
    echo "Failed to connect\n";
}
