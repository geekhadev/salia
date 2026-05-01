// Log the raw request payload
$payload = file_get_contents('php://input');
file_put_contents('path/to/log/file.log', "Raw Payload: " . $payload . "\n", FILE_APPEND);

// Decode the payload to an array
$data = json_decode($payload, true);

// Log the incoming data
file_put_contents('path/to/log/file.log', "Incoming Data: " . print_r($data, true) . "\n", FILE_APPEND);

// Rest of your controller logic...