<?php
// .envファイルから環境変数を読み込む
function loadEnv($path) {
    if (!file_exists($path)) {
        die('.envファイルが見つかりません');
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

loadEnv(__DIR__ . '/.env');

$apiKey = $_ENV['OPENAI_API_KEY'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = $_POST['message'] ?? '';
    
    if (empty($input)) {
        $response = 'メッセージを入力してください。';
    } else {
        // OpenAI APIにリクエスト
        $data = [
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'ユーザーの問いかけから最も重要な語を1つ選び、その原語と英訳を「原語: [単語] / 英訳: [translation]」の形式で返してください。他の説明は不要です。'
                ],
                [
                    'role' => 'user',
                    'content' => $input
                ]
            ],
            'temperature' => 0.7
        ];
        
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $json = json_decode($result, true);
            $response = $json['choices'][0]['message']['content'] ?? 'エラーが発生しました。';
        } else {
            $response = 'APIエラー: ' . $result;
        }
    }
    
    echo $response;
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>重要語抽出チャットボット</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 600px;
            padding: 30px;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
        }
        .chat-box {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            min-height: 150px;
            margin-bottom: 20px;
            font-size: 16px;
            color: #333;
            white-space: pre-wrap;
        }
        .input-group {
            display: flex;
            gap: 10px;
        }
        input[type="text"] {
            flex: 1;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
        }
        button {
            padding: 12px 24px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }
        button:hover {
            background: #0056b3;
        }
        button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .loading {
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>重要語抽出チャットボット</h1>
        <div class="chat-box" id="response">問いかけを入力してください。</div>
        <div class="input-group">
            <input type="text" id="message" placeholder="メッセージを入力..." />
            <button onclick="sendMessage()" id="sendBtn">送信</button>
        </div>
    </div>

    <script>
        const messageInput = document.getElementById('message');
        const responseDiv = document.getElementById('response');
        const sendBtn = document.getElementById('sendBtn');

        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });

        async function sendMessage() {
            const message = messageInput.value.trim();
            if (!message) return;

            sendBtn.disabled = true;
            responseDiv.textContent = '処理中...';
            responseDiv.className = 'chat-box loading';

            const formData = new FormData();
            formData.append('message', message);

            try {
                const response = await fetch('index.php', {
                    method: 'POST',
                    body: formData
                });

                const text = await response.text();
                responseDiv.textContent = text;
                responseDiv.className = 'chat-box';
            } catch (error) {
                responseDiv.textContent = 'エラーが発生しました: ' + error.message;
                responseDiv.className = 'chat-box';
            }

            sendBtn.disabled = false;
            messageInput.value = '';
            messageInput.focus();
        }
    </script>
</body>
</html>