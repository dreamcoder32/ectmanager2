<?php

namespace App\Services;

use App\Models\AttendanceDevice;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * ZKTeco K60 Pro Device Service
 * 
 * This service handles communication with ZKTeco K60 Pro attendance devices.
 * It supports fetching attendance records, syncing users, and managing device connections.
 */
class ZKTecoService
{
    protected $socket;
    protected $sessionId;
    protected $replyId;
    protected $useNewProtocol = true;
    protected $useTcp = true;
    protected $ip;
    protected $port;

    // ZKTeco Protocol Constants
    const CMD_CONNECT = 1000;
    const CMD_EXIT = 1001;
    const CMD_ENABLE_DEVICE = 1002;
    const CMD_DISABLE_DEVICE = 1003;
    const CMD_GET_TIME = 1201;
    const CMD_SET_TIME = 1202;
    const CMD_USER_WRQ = 8;
    const CMD_USERTEMP_RRQ = 9;
    const CMD_ATTLOG_RRQ = 13;
    const CMD_CLEAR_DATA = 14;
    const CMD_CLEAR_ATTLOG = 15;
    const CMD_DELETE_USER = 18;
    const CMD_GET_FREE_SIZES = 50;
    const CMD_GET_USER = 1100;
    const CMD_SET_USER = 1101;

    /**
     * Connect to ZKTeco device
     */
    public function connect(AttendanceDevice $device): bool
    {
        try {
            Log::info("Connecting to ZKTeco device at {$device->ip_address}:{$device->port} (TCP)");

            $this->ip = $device->ip_address;
            $this->port = $device->port;
            $this->useTcp = true;
            $this->useNewProtocol = false;

            if ($this->socket) {
                // Determine if socket is still alive? simpler to close and reopen
                $this->disconnect();
            }

            $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if ($this->socket === false) {
                throw new \Exception("Failed to create socket: " . socket_strerror(socket_last_error()));
            }

            socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 5, 'usec' => 0]);
            socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => 5, 'usec' => 0]);

            if (!@socket_connect($this->socket, $this->ip, $this->port)) {
                throw new \Exception("TCP Connect failed: " . socket_strerror(socket_last_error()));
            }

            $this->sessionId = 0;
            $this->replyId = 0;

            // Send CMD_CONNECT via TCP
            Log::info("Testing ZKTeco Connect (TCP)...");
            // Some devices need 4 bytes of 0 as payload for valid connection, or the Comm Key
            $commKey = 0;
            $command = $this->createCommand(self::CMD_CONNECT, pack('V', $commKey));
            $this->sendCommand($this->ip, $this->port, $command);

            $reply = $this->receiveReply();

            if ($reply) {
                $rHeader = unpack('vcmd', substr($reply, 0, 2));
                $cmdCode = $rHeader['cmd'] ?? 0;
                Log::info("Connect Reply CMD: " . $cmdCode);


                Log::info("Established TCP session: {$this->sessionId}");
                return true;
            }

            Log::error("No response from device for connection request.");
            return false;
        } catch (\Exception $e) {
            Log::error("Connection Error: {$e->getMessage()}");
            $this->disconnect();
            return false;
        }
    }

    public function getDeviceInfo(): array
    {
        $info = [];
        $cmds = [
            'Version' => '11,~SoftwareVersion',
            'SerialNumber' => '11,~SerialNumber',
            'UserCount' => 1001,
            'AttCount' => 1004,
            'Time' => self::CMD_GET_TIME,
        ];

        foreach ($cmds as $name => $code) {
            $data = '';
            if (is_string($code) && strpos($code, ',') !== false) {
                list($code, $data) = explode(',', $code);
                $command = $this->createCommand((int) $code, $data . "\0");
            } else {
                $command = $this->createCommand((int) $code);
            }

            $this->sendCommand($this->ip, $this->port, $command);

            $attempts = 3;
            $reply = null;
            while ($attempts-- > 0) {
                $res = $this->receiveReply();
                if (!$res)
                    break;

                $header = unpack('vcmd/vchecksum/vsession/vreply', substr($res, 0, 8));
                if ($header['cmd'] === 2000) { // ACK_OK
                    $reply = $res;
                    break;
                }
                if ($header['cmd'] === 2005) { // ACK
                    Log::info("Device Info {$name}: Received ACK, waiting for data...");
                    usleep(100000);
                    continue;
                }
                // If we get something else, maybe it's the data already
                $reply = $res;
                break;
            }

            $info[$name] = $reply ? bin2hex($reply) : 'null';
            if ($reply && strlen($reply) > 8) {
                $info[$name . '_parsed'] = substr($reply, 8);
            }
            Log::info("Device Info {$name}: " . $info[$name]);
        }

        return $info;
    }

    /**
     * Disconnect from ZKTeco device
     */
    public function disconnect(): void
    {
        if ($this->socket) {
            if ($this->ip && $this->port) {
                $command = $this->createCommand(self::CMD_EXIT);
                $this->sendCommand($this->ip, $this->port, $command);
            }
            socket_close($this->socket);
            $this->socket = null;
        }
    }

    /**
     * Fetch attendance records from device
     */
    public function fetchAttendanceRecords(AttendanceDevice $device, ?Carbon $fromDate = null): array
    {
        if (!$this->connect($device)) {
            throw new \Exception('Failed to connect to device');
        }

        try {
            // Disable device to prevent new records during sync
            $this->disableDevice();

            // Try to prepare/refresh the attendance data buffer
            // This might force the device to include new records
            Log::info("Sending CMD_PREPARE_DATA to refresh attendance buffer...");
            $prepareCmd = $this->createCommand(101); // CMD_PREPARE_DATA
            $this->sendCommand($device->ip_address, $device->port, $prepareCmd);
            $prepareReply = $this->receiveReply();
            if ($prepareReply) {
                Log::info("Prepare data reply received: " . bin2hex($prepareReply));
            }

            usleep(500000); // Wait 500ms for device to prepare data

            // Request user templates first (sometimes helps wake up the data stream)
            $preCmd = $this->createCommand(self::CMD_USERTEMP_RRQ);
            $this->sendCommand($device->ip_address, $device->port, $preCmd);
            $this->receiveReply();

            // Request attendance logs
            $command = $this->createCommand(self::CMD_ATTLOG_RRQ);
            $this->sendCommand($device->ip_address, $device->port, $command);

            $records = [];
            $maxAttempts = 50;
            $receivedBatch = false;

            while ($maxAttempts-- > 0) {
                $reply = $this->receiveReply();
                if (!$reply) {
                    if ($receivedBatch)
                        break; // If we already got some data and now it's silent, we might be done
                    usleep(100000); // 100ms
                    continue;
                }

                $details = unpack('vcmd/vchecksum/vsession/vreply', substr($reply, 0, 8));
                $cmd = $details['cmd'] ?? 0;

                if (strlen($reply) > 8) {
                    $receivedBatch = true;
                    Log::info("Received DATA packet (" . strlen($reply) . " bytes), CMD={$cmd}");
                    $batch = $this->parseAttendanceRecords($reply, $device, $fromDate);
                    $records = array_merge($records, $batch);

                    // If the packet is full, there's likely more. If not, we might be done.
                    if (strlen($reply) < 1024) {
                        // But wait, let's keep waiting a bit just in case
                    }
                } elseif ($cmd === 2005) {
                    Log::info("Received ACK (2005) for AttLog RRQ, waiting for data packets... (Attempts left: {$maxAttempts})");
                } elseif ($cmd === 2000) {
                    Log::info("Received ACK_OK (2000)");
                } else {
                    Log::info("Received unexpected 8-byte CMD: {$cmd}");
                }

                usleep(50000); // 50ms
            }

            // Re-enable device
            $this->enableDevice();

            return $records;
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Sync users to device
     */
    public function syncUsersToDevice(AttendanceDevice $device, array $users): bool
    {
        if (!$this->connect($device)) {
            throw new \Exception('Failed to connect to device');
        }

        try {
            $this->disableDevice();

            foreach ($users as $user) {
                $this->addUserToDevice($user);
            }

            $this->enableDevice();
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to sync users to device: {$e->getMessage()}");
            return false;
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Add user to device
     */
    protected function addUserToDevice(User $user): bool
    {
        if (!$user->device_user_id) {
            return false;
        }

        $command = $this->createUserCommand($user);
        socket_send($this->socket, $command, strlen($command), 0);

        $reply = $this->receiveReply();
        return $reply !== false;
    }

    /**
     * Enable device
     */
    protected function enableDevice(): void
    {
        $command = $this->createCommand(self::CMD_ENABLE_DEVICE);
        $this->sendCommand($this->ip, $this->port, $command);
        $this->receiveReply();
    }

    /**
     * Disable device
     */
    protected function disableDevice(): void
    {
        $command = $this->createCommand(self::CMD_DISABLE_DEVICE);
        $this->sendCommand($this->ip, $this->port, $command);
        $this->receiveReply();
    }

    /**
     * Create command packet
     */
    protected function createCommand(int $command, string $data = ''): string
    {
        $this->replyId = ($this->replyId ?? -1) + 1;
        if ($this->replyId >= 65535) {
            $this->replyId = 0;
        }

        // Inner packet (Body)
        $buf = pack('vvvv', $command, 0, $this->sessionId ?? 0, $this->replyId) . $data;

        $internalChecksum = $this->createChecksum($buf);
        $buf = pack('vvvv', $command, $internalChecksum, $this->sessionId ?? 0, $this->replyId) . $data;

        if (!$this->useNewProtocol) {
            return $buf;
        }

        // New protocol wrapper
        $headerChecksum = $this->createChecksum($buf);
        if ($headerChecksum === 0) {
            $headerChecksum = 0xFFFF;
        }

        $top = pack('vvvv', 0x5a4b, $headerChecksum, $this->sessionId ?? 0, strlen($buf));

        return $top . $buf;
    }

    /**
     * Create user command
     */
    protected function createUserCommand(User $user): string
    {
        $userData = pack('S', $user->device_user_id);
        $userData .= str_pad($user->full_name, 28, "\0");
        $userData .= pack('C', 0); // Password (not used)
        $userData .= pack('C', 0); // Card number
        $userData .= pack('C', 0); // Group
        $userData .= pack('S', 0); // Timezone

        return $this->createCommand(self::CMD_SET_USER, $userData);
    }

    /**
     * Send command to device
     */
    protected function sendCommand(string $ip, int $port, string $command): void
    {
        if ($this->useTcp) {
            // Standard ZK TCP wrapper: 
            // 0x50 0x50 0x82 0x7d [len] [len] 0x00 0x00
            $header = pack('vvvxx', 0x5050, 0x7d82, strlen($command));
            $packet = $header . $command;
            Log::info("Sending TCP packet (" . strlen($packet) . " bytes)");
            @socket_write($this->socket, $packet);
        } else {
            Log::info("Sending UDP packet (" . strlen($command) . " bytes) to {$ip}:{$port}");
            Log::info("Packet Hex: " . bin2hex($command));
            socket_sendto($this->socket, $command, strlen($command), 0, $ip, $port);
        }
    }

    /**
     * Receive reply from device
     */
    protected function receiveReply(): ?string
    {
        $reply = '';
        $bytes = 0;
        $from = 'unknown';

        if ($this->useTcp) {
            $header = @socket_read($this->socket, 8);
            if (!$header || strlen($header) < 8) {
                return null;
            }

            $h = unpack('v3', substr($header, 0, 6)); // Read first 6 bytes for unpack
            $magic = $h[1];
            $proto = $h[2];
            $dataLen = $h[3];

            if ($magic !== 0x5050) {
                Log::warning("TCP Magic mismatch: " . dechex($magic));
                return null;
            }

            Log::info("TCP Header: Len={$dataLen}");

            $reply = '';
            $received = 0;
            while ($received < $dataLen) {
                $chunk = @socket_read($this->socket, $dataLen - $received);
                if (!$chunk)
                    break;
                $reply .= $chunk;
                $received += strlen($chunk);
            }
            $bytes = strlen($reply);
        } else {
            $fromAddr = '';
            $fromPort = 0;
            $bytes = @socket_recvfrom($this->socket, $reply, 2048, 0, $fromAddr, $fromPort);
            $from = "{$fromAddr}:{$fromPort}";
        }

        if ($bytes === false || $bytes === 0) {
            return null;
        }

        Log::info("Received packet ({$bytes} bytes) from {$from}");
        Log::info("Reply Hex: " . bin2hex($reply));

        if ($bytes >= 8) {
            $header = unpack('vcmd/vchecksum/vsession/vreply', substr($reply, 0, 8));

            if ($header['cmd'] === 0x5050 || $header['cmd'] === 0x5a4b) {
                // Wrapper header detected
                $this->sessionId = $header['session'];
                Log::info("Wrapper Header Detected: CMD={$header['cmd']}, Session={$this->sessionId}");
                return substr($reply, 8);
            } else {
                // Standard header
                $this->sessionId = $header['session'];
                Log::info("Header Detected: CMD={$header['cmd']}, Session={$this->sessionId}, ReplyID={$header['reply']}");
                return $reply;
            }
        }

        return $reply;
    }

    /**
     * Parse attendance records from device response
     */
    protected function parseAttendanceRecords(string $data, AttendanceDevice $device, ?Carbon $fromDate): array
    {
        $records = [];
        $headerSize = 8;

        if (strlen($data) <= $headerSize) {
            return [];
        }

        $payload = substr($data, $headerSize);
        $payloadLen = strlen($payload);

        // Try to detect record size (usually 8, 16, or 40)
        $recordSize = 40;
        if ($payloadLen % 8 === 0 && ($payloadLen % 16 !== 0 || $payloadLen % 40 !== 0)) {
            $recordSize = 8;
        } elseif ($payloadLen % 16 === 0 && $payloadLen % 40 !== 0) {
            $recordSize = 16;
        }

        Log::info("Parsing attendance records. Payload Hex: " . bin2hex($payload));
        Log::info("Parsing attendance records. Payload length: {$payloadLen}, Estimated record size: {$recordSize}");

        $offset = 0;
        while ($offset + $recordSize <= $payloadLen) {
            $record = substr($payload, $offset, $recordSize);

            if ($recordSize === 8) {
                // 8 bytes total: vuser_id (2) + vstatus (2) + Vtime (4) = 8 bytes
                $parsed = unpack('vuser_id/vstatus/Vtime', $record);
            } elseif ($recordSize === 40) {
                $parsed = unpack('vuser_id/Cyear/Cmonth/Cday/Chour/Cminute/Csecond/Cverify', $record);
            } else {
                // 16 bytes: vuser_id (2) + vstatus (2) + Vtime (4) + rest (8)
                $parsed = unpack('vuser_id/vstatus/Vtime/Vreserved1/Vreserved2', $record);
            }

            if (!$parsed || !isset($parsed['user_id'])) {
                $offset += $recordSize;
                continue;
            }

            try {
                $punchTime = null;
                if ($recordSize === 40) {
                    $punchTime = Carbon::create(
                        2000 + ($parsed['year'] ?? 0),
                        $parsed['month'] ?? 1,
                        $parsed['day'] ?? 1,
                        $parsed['hour'] ?? 0,
                        $parsed['minute'] ?? 0,
                        $parsed['second'] ?? 0
                    );
                } elseif ($recordSize === 8 || $recordSize === 16) {
                    // For 8 and 16 byte records, 'time' is a Unix timestamp
                    if (isset($parsed['time']) && $parsed['time'] > 0) {
                        $punchTime = Carbon::createFromTimestamp($parsed['time']);
                    }
                }

                if ($punchTime) {
                    Log::info("Parsed Record: UserID={$parsed['user_id']}, Time={$punchTime->toDateTimeString()}");

                    // Filter by date if specified
                    if ($fromDate && $punchTime->lt($fromDate)) {
                        $offset += $recordSize;
                        continue;
                    }

                    // Find user by device_user_id
                    $user = User::query()->where('device_user_id', (string) $parsed['user_id'])->first();

                    if ($user) {
                        $records[] = [
                            'user_id' => $user->id,
                            'device_id' => $device->id,
                            'device_user_id' => $parsed['user_id'],
                            'punch_time' => $punchTime,
                            'punch_type' => 'check_in',
                            'verify_mode' => $this->getVerifyMode($parsed['verify'] ?? 0),
                            'is_synced' => true,
                        ];
                    } else {
                        Log::warning("No local user found for DeviceUserID: {$parsed['user_id']}");
                    }
                }
            } catch (\Exception $e) {
                // Log and continue
            }

            $offset += $recordSize;
        }

        return $records;
    }

    /**
     * Get verify mode from code
     */
    protected function getVerifyMode(int $code): string
    {
        return match ($code) {
            0 => 'password',
            1 => 'fingerprint',
            3 => 'card',
            4 => 'face',
            default => 'fingerprint',
        };
    }

    /**
     * Create checksum for command
     */
    protected function createChecksum(string $buf): int
    {
        $checksum = 0;
        for ($i = 0; $i < strlen($buf); $i += 2) {
            $word = ord($buf[$i]);
            if ($i + 1 < strlen($buf)) {
                $word += ord($buf[$i + 1]) << 8;
            }
            $checksum += $word;
        }

        while ($checksum > 0xFFFF) {
            $checksum = ($checksum & 0xFFFF) + ($checksum >> 16);
        }

        $res = ~$checksum & 0xFFFF;
        return $res === 0 ? 0xFFFF : $res;
    }

    /**
     * Get device time
     */
    public function getTime(): ?string
    {
        $command = $this->createCommand(self::CMD_GET_TIME);
        $this->sendCommand($this->ip, $this->port, $command);
        $reply = $this->receiveReply();

        if (!$reply || strlen($reply) < 12) {
            return null;
        }

        $data = substr($reply, 8);
        $time = unpack('I', $data)[1];

        // ZKTeco time is often encoded: (year - 2000) * 12 * 31 * 24 * 60 * 60 + ...
        // or it's just a timestamp. 
        return "Raw: " . bin2hex($data);
    }

    /**
     * Test device connection
     */
    public function testConnection(AttendanceDevice $device): array
    {
        try {
            if ($this->connect($device)) {
                $this->disconnect();
                return [
                    'success' => true,
                    'message' => 'Successfully connected to device',
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to connect to device',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get device version
     */
    public function getRecordsCount(): int
    {
        $command = $this->createCommand(1004); // CMD_GET_ATTENDANCE_COUNT
        $this->sendCommand($this->ip, $this->port, $command);
        $reply = $this->receiveReply();

        if (!$reply || strlen($reply) < 12) {
            return 0;
        }

        return unpack('V', substr($reply, 8, 4))[1] ?? 0;
    }

    public function getOption(string $option): ?string
    {
        $command = $this->createCommand(11, $option);
        $this->sendCommand($this->ip, $this->port, $command);
        $reply = $this->receiveReply();

        if (!$reply || strlen($reply) <= 8)
            return null;
        return substr($reply, 8);
    }
}
