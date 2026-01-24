<?php
use App\Models\AttendanceDevice;
use App\Services\ZKTecoService;
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
tenancy()->initialize('append4');

class ZKDebug extends ZKTecoService
{
    public function getOptionDebug($option)
    {
        echo "Querying option: {$option}\n";
        $command = $this->createCommand(11, $option . "\0");
        $this->sendCommand($this->ip, $this->port, $command);

        $attempts = 5;
        while ($attempts-- > 0) {
            $reply = $this->receiveReply();
            if (!$reply) {
                echo "  Timeout or No reply\n";
                break;
            }
            $hex = bin2hex($reply);
            $header = unpack('vcmd/vchecksum/vsession/vreply', substr($reply, 0, 8));
            echo "  Received: CMD={$header['cmd']}, Len=" . strlen($reply) . ", Hex=" . substr($hex, 0, 32) . "...\n";

            if ($header['cmd'] == 2000) {
                $data = substr($reply, 8);
                echo "  Found Data (ACK_OK): " . $data . "\n";
                return $data;
            }
            if ($header['cmd'] == 2005) {
                echo "  Received ACK (2005), waiting for more...\n";
            }
        }
        return null;
    }
}

$device = AttendanceDevice::first();
$zk = new ZKDebug();
if ($zk->connect($device)) {
    echo "Connected!\n";
    $zk->getOptionDebug("~SerialNumber");
    $zk->getOptionDebug("~DeviceName");
    $zk->getOptionDebug("~SoftwareVersion");
    $zk->disconnect();
} else {
    echo "Failed to connect\n";
}
