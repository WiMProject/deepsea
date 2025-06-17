<?php
function chatWithDeepSeek($message, $conversationHistory = [], $fileContent = null) {
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . DEEPSEEK_API_KEY
    ];
    
    // Gabungkan konten file jika ada
    $fullMessage = $message;
    if ($fileContent) {
        $fullMessage .= "\n\nFile content:\n" . $fileContent;
    }
    
    $conversationHistory[] = ['role' => 'user', 'content' => $fullMessage];
    
    $data = [
        'model' => 'deepseek-chat',
        'messages' => $conversationHistory,
        'temperature' => 0.7,
        'max_tokens' => 2000
    ];
    
    $ch = curl_init(DEEPSEEK_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        error_log('DeepSeek API Error: ' . curl_error($ch));
        return ['error' => 'Terjadi kesalahan saat menghubungi DeepSeek API'];
    }
    
    curl_close($ch);
    
    $responseData = json_decode($response, true);
    
    if ($httpCode !== 200 || !isset($responseData['choices'][0]['message'])) {
        error_log('DeepSeek API Response: ' . $response);
        return ['error' => 'Gagal mendapatkan respons dari DeepSeek API'];
    }
    
    $assistantReply = $responseData['choices'][0]['message']['content'];
    $conversationHistory[] = ['role' => 'assistant', 'content' => $assistantReply];
    
    return [
        'response' => $assistantReply,
        'conversation' => $conversationHistory
    ];
}

function extractTextFromFile($filePath, $fileType) {
    try {
        switch ($fileType) {
            case 'pdf':
                // Butuh library seperti smalot/pdfparser
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($filePath);
                return $pdf->getText();
                
            case 'txt':
                return file_get_contents($filePath);
                
            default:
                return null;
        }
    } catch (Exception $e) {
        error_log("File extraction error: " . $e->getMessage());
        return null;
    }
}
