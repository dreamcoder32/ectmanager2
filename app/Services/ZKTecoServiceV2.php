<?php

namespace App\Services;

use App\Models\AttendanceDevice;
use App\Models\User;
use Carbon\Carbon;
use CodingLibs\ZktecoPhp\Libs\ZKTeco;
use Illuminate\Support\Facades\Log;

/**
 * ZKTeco Device Service using coding-libs/zkteco-php package
 */
class ZKTecoServiceV2
{
    /**
     * Fetch attendance records from device using the package
     */
    public function fetchAttendanceRecords(AttendanceDevice $device, ?Carbon $fromDate = null): array
    {
        try {
            // Create ZKTeco instance
            $zk = new ZKTeco($device->ip_address, $device->port);

            // Connect
            if (!$zk->connect()) {
                throw new \Exception('Failed to connect to device');
            }

            Log::info("Connected to ZKTeco device at {$device->ip_address}:{$device->port}");

            // Disable device to prevent new records during sync
            $zk->disableDevice();

            // Get attendance logs
            $attendanceData = $zk->getAttendances();

            Log::info("Fetched " . count($attendanceData) . " attendance records from device");

            // Re-enable device
            $zk->enableDevice();

            // Disconnect
            $zk->disconnect();

            // Process records
            $records = [];
            foreach ($attendanceData as $record) {
                $deviceUserId = (string) ($record['user_id'] ?? null);
                $recordTime = $record['record_time'] ?? null;

                if (!$deviceUserId || !$recordTime) {
                    continue;
                }

                // Parse the record time
                $punchTime = Carbon::parse($recordTime);

                // Filter by date if specified
                if ($fromDate && $punchTime->lt($fromDate)) {
                    continue;
                }

                // Find user by device_user_id
                $user = User::where('device_user_id', $deviceUserId)->first();

                if (!$user) {
                    Log::warning("No local user found for DeviceUserID: {$deviceUserId}");
                    continue;
                }

                Log::info("Matched record: User={$user->first_name}, DeviceUserID={$deviceUserId}, Time={$punchTime}");

                $records[] = [
                    'user_id' => $user->id,
                    'device_id' => $device->id,
                    'device_user_id' => $deviceUserId,
                    'punch_time' => $punchTime,
                    'punch_state' => $record['state'] ?? 0,
                    'verify_type' => $record['type'] ?? 0,
                ];
            }

            return $records;

        } catch (\Exception $e) {
            Log::error("ZKTeco fetch error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test connection to device
     */
    public function testConnection(AttendanceDevice $device): array
    {
        try {
            $zk = new ZKTeco($device->ip_address, $device->port);

            if (!$zk->connect()) {
                return [
                    'success' => false,
                    'message' => 'Failed to connect to device'
                ];
            }

            // Get device info
            $serialNumber = $zk->serialNumber();
            $version = $zk->version();
            $users = $zk->getUsers();

            $zk->disconnect();

            return [
                'success' => true,
                'message' => 'Connection successful',
                'serial_number' => $serialNumber,
                'version' => $version,
                'user_count' => count($users),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
