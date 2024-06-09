<?php

namespace App\Services;

class LiveStatusService {
    private $params;

    public function __construct($params = []) {
        $this->params = $params;
    }

    public function getParams() {
        return $this->params;
    }

    public static function buildLivestatusQuery($request, $type, $name = false) {
        if ($name) {
            if ($type === 'service') {
                if(false === strpos($name, ';')) {
                    return ['error' => 'Wrong format of service name', 'code' => 400];
                }
                list($name, $descr) = explode(';', $name);
                return "GET {$type}s\n" .
                    "Filter: host_name = $name\n" .
                    "Filter: description = $descr\n";
            }
            return "GET {$type}s\n" .
                "Filter: name = $name\n";
        }

        $params = $request->getParams();
        $query = ($type === 'service') ? "GET {$type}s\nColumns: host_name description\n" : "GET {$type}s\nColumns: name\n";

        if (isset($params['state'])) {
            if (is_array($params['state'])) {
                foreach ($params['state'] as $state) {
                    $state = (int)$state;
                    $query .= "Filter: state = $state\n";
                }
                $query .= "Or: " . count($params['state']) . "\n";
            } else {
                $state = (int)$params['state'];
                $query .= "Filter: state = $state\n";
            }
        }

        if (isset($params['hostgroup'])) {
            $hostgroup = $params['hostgroup'];
            if (is_array($hostgroup)) {
                foreach ($hostgroup as $hg) {
                    $query .= ($type === 'service') ? "Filter: host_groups >= $hg\n" : "Filter: groups >= $hg\n";
                }
                $query .= "Or: " . count($hostgroup) . "\n";
            } else {
                $query .= ($type === 'service') ? "Filter: host_groups >= $hostgroup\n" : "Filter: groups >= $hostgroup\n";
            }
        }

        if (isset($params['hard'])) {
            $hard = (int)$params['hard'];
            $query .= "Filter: state_type = $hard\n";
        }

        if (isset($params['name'])) {
            if ($type === 'service') {
                $query .= "Filter: host_name ~ {$params['name']}\n";
                $query .= "Filter: description ~ {$params['name']}\n";
                $query .= "Or: 2\n";
            } else {
                $query .= "Filter: name ~ {$params['name']}\n";
            }
        }

        return $query;
    }

    public function openSock() {
        $socketPath = env('NAEMON_SOCKET_PATH'); // Assuming you're using Laravel's env() helper

        if (!$socketPath) {
            throw new Exception("Socket path not set in environment variables");
        }

        $sock = @fsockopen("unix://$socketPath", 0, $errno, $errstr);
        if ($errno) {
            $i = 0;
            while ($i++ < 5) {
                sleep(1);
                $sock = @fsockopen("unix://$socketPath", 0, $errno, $errstr);
                if (!$errno) {
                    break;
                }
            }
        }
        if ($errno) {
            return false;
        }
        return $sock;
    }

    public function readSocket($socket, $len) {
        $offset = 0;
        $res = '';
        while ($offset < $len) {
            $data = fread($socket, $len - $offset);
            if (empty($data)) {
                break;
            }
            $res .= $data;
            $offset += strlen($data);
        }
        return $res;
    }

    public function executeQuery($type, $name = false) {
        $name = urldecode($name);
        $query = self::buildLivestatusQuery($this, $type, $name);

        if (is_array($query) && isset($query['error'])) {
            return $query;
        }

        $query .= "OutputFormat: json\nResponseHeader: fixed16\n\n";

        $sock = $this->openSock();
        if (!$sock) {
            return ['error' => "Couldn't open livestatus socket", 'code' => 500];
        }

        @fwrite($sock, $query);

        $head = $this->readSocket($sock, 16);
        $len = (int)substr($head, 4, 12);

        $out = $this->readSocket($sock, $len);

        fclose($sock);

        if (empty($out)) {
            return ['error' => 'No response received from the socket.', 'code' => 500];
        }

        $jsonResponse = json_decode($out, true);
        if ($jsonResponse === null && json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'Failed to decode JSON response: ' . json_last_error_msg(), 'code' => 500];
        }

        // If name is provided, return mapped result
        if ($name) {
            $columns = $jsonResponse[0];
            $values = $jsonResponse[1];

            $mappedResult = [];
            foreach ($columns as $index => $columnName) {
                $mappedResult[$columnName] = $values[$index];
            }

            return $mappedResult;
        } else {
            // If name is not provided, return structured array with resource links
            $base_url = env('APP_URL').'/status/';
            $response = [];

            foreach ($jsonResponse as $obj) {
                if ($type === 'service') {
                    $response[] = [
                        'name' => $obj[0] . ';' . $obj[1],
                        'resource' => $base_url . 'service/' . urlencode(urlencode($obj[0] . ';' . $obj[1]))
                    ];
                } else {
                    $response[] = [
                        'name' => $obj[0],
                        'resource' => $base_url . $type . '/' . urlencode(urlencode($obj[0]))
                    ];
                }
            }

            return $response;
        }
    }



}
