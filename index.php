<?php
// .envから設定を読み込む簡易関数
$env = parse_ini_file('.env');
$apiKey = $env['OPENAI_API_KEY'] ?? '';

$result = "";
$input = $_POST['question'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($input)) {
    $url = "https://api.openai.com/v1/chat/completions";

    // プロンプトの設定
    $data = [
        "model" => "gpt-4o-mini", // 指定のモデル名
        "messages" => [
            [
                "role" => "system",
                "content" => "与えられた文章から最も重要な単語を1つ選び、[原語]:[英訳] という形式のプレーンテキストのみで回答してください。"
            ],
            [
                "role" => "user",
                "content" => $input
            ]
        ],
        "temperature" => 0.3
    ];

    // ストリームコンテキストの作成（ライブラリ不使用）
    $options = [
        'http' => [
            'method'  => 'POST',
            'header'  => [
                "Content-Type: application/json",
                "Authorization: Bearer $apiKey"
            ],
            'content' => json_encode($data),
            'ignore_errors' => true
        ]
    ];

    $context  = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    if ($response === FALSE) {
        $result = "エラー: APIへの接続に失敗しました。";
    } else {
        $resData = json_decode($response, true);
        $result = $resData['choices'][0]['message']['content'] ?? "エラー: 解析できませんでした。";
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Word Extractor</title>
</head>
<body>
    <h1>重要語抽出チャットボット</h1>
    <form method="POST">
        <input type="text" name="question" placeholder="文章を入力してください" style="width: 300px;" value="<?= htmlspecialchars($input) ?>">
        <button type="submit">抽出</button>
    </form>

    <?php if ($result): ?>
        <h2>結果:</h2>
        <pre><?= htmlspecialchars($result) ?></pre>
    <?php endif; ?>
</body>
</html>