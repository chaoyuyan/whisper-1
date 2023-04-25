<?php 
// 定义常量和变量
$api_url = 'https://api.openai.com/v1/audio/transcriptions';
$api_timeout = 30; // 设置API请求超时时间

$errors = []; // 用于存储错误信息

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 验证和过滤用户输入
  $token = $_POST['token'];
    $model = 'whisper-1';
    $file = $_FILES['file'];

    if (empty($token)) {
        $errors[] = 'Token is required';
    }

    if (empty($model)) {
        $errors[] = 'Model is required';
    }

    if (empty($file)) {
        $errors[] = 'File is required';
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload failed';
    } elseif (!in_array($file['type'], ['audio/m4a', 'audio/mpeg', 'audio/mp3', 'audio/mp4', 'audio/mpeg', 'audio/mpga', 'audio/wav', 'audio/webm'])) {
        $errors[] = 'Invalid file format';
    }

    // 发送请求到OpenAI API
    if (empty($errors)) {
        $fields = [
            'file' => new CURLFile($file['tmp_name'], $file['type'], $file['name']),
            'model' => $model
        ];
        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: multipart/form-data'
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $api_url,
            CURLOPT_POST => TRUE,
            CURLOPT_POSTFIELDS => $fields,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_CONNECTTIMEOUT => $api_timeout,
            CURLOPT_TIMEOUT => $api_timeout
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        // 处理响应
        if ($err) {
    $errors[] = 'API request failed: ' . $err;
} else {
    $data = json_decode($response, TRUE);
    if (!$data) {
        $errors[] = 'API response format error';
    }
    // Debugging code: print out API response
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>OpenAI API Demo</title>
</head>
<body>
    <h1>OpenAI API Demo</h1>

    <?php if (!empty($errors)): ?>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= $error ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div>
            <label for="token">API Token:</label>
            <input type="text" id="token" name="token" value="">
        </div>
        
        <div>
            <label for="file">Audio File:</label>
            <input type="file" id="file" name="file">
        </div>
        <div>
            <button type="submit">Submit</button>
        </div>
    </form>

   <?php if (!empty($data) && isset($data['text']) && $data['text'] !== null): ?>
    <h2>Transcription Result:</h2>
    <div>
        <?= htmlentities($data['text']) ?>
    </div>
<?php endif; ?>
</body>
</html>
