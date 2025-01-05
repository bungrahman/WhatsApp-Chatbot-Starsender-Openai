<?php

// Jika diakses melalui browser (metode GET), redirect ke domain utama
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: https://domainku.com");
    exit(); // Hentikan eksekusi script
}

// Token API dari Starsender
$starsenderApiKey = 'YOUR_STARSENDER_API_KEY';

// Token API dari OpenAI
$openAiApiKey = 'YOUR_OPENAI_API_KEY'; // Ganti dengan API key OpenAI Anda

// Pastikan direktori history ada
if (!file_exists('history')) {
    mkdir('history', 0755, true);
}

// Mendapatkan data yang dikirim dari Starsender
$json = file_get_contents('php://input');
$data = json_decode($json);

// Memastikan data yang diterima valid
if (isset($data->message) && isset($data->from)) {
    $message = $data->message; // Isi pesan
    $from = $data->from; // Nomor pengirim
    $timestamp = $data->timestamp; // Timestamp pesan

    // Muat riwayat percakapan
    $history = loadConversationHistory($from);

    // Periksa jika riwayat telah mencapai batas 10 pesan
    if (count($history) >= 10) {
        $notification = "Riwayat percakapan yang terkait dengan percakapan sebelumnya telah mencapai batas maksimal 10 pesan. kita akan memuali percakapan baru.";
        sendMessage($from, $notification, $starsenderApiKey);
        
        // Delete the history file
        $filename = "history/" . basename($from) . ".json";
        if (file_exists($filename)) {
            if (!unlink($filename)) {
                error_log("Gagal menghapus file riwayat untuk {$from}");
            }
        }
        
        // Reset history array
        $history = [];
    }

    // Tambahkan pesan pengguna ke riwayat
    $history[] = ["role" => "user", "content" => $message];

    // Cek apakah pesan mengandung kata kunci
    $responseMessage = getResponseFromKeywords($message);

    if ($responseMessage !== null) {
        // Tambahkan respons kata kunci ke riwayat
        $history[] = ["role" => "assistant", "content" => $responseMessage];
        // Simpan riwayat yang diperbarui
        saveConversationHistory($from, $history);
        // Kirim respons kembali ke pengguna
        sendMessage($from, $responseMessage, $starsenderApiKey);
    } else {
        // Kirim riwayat ke OpenAI
        $response = sendToOpenAI($history, $openAiApiKey);
        // Tambahkan respons dari OpenAI ke riwayat
        $history[] = ["role" => "assistant", "content" => $response];
        // Simpan riwayat yang diperbarui
        saveConversationHistory($from, $history);
        // Kirim respons kembali ke pengguna
        sendMessage($from, $response, $starsenderApiKey);
    }
}

/**
 * Fungsi untuk memeriksa kata kunci dan memberikan respons
 *
 * @param string $message Pesan dari pengguna
 * @return string|null Respons yang sesuai atau null jika tidak ada kata kunci yang cocok
 */
function getResponseFromKeywords($message) {
    // Baca file JSON
    $responses = json_decode(file_get_contents('responses.json'), true);

    // Urutkan kata kunci dari yang terpanjang ke terpendek
    uksort($responses["kata_kunci"], function($a, $b) {
        return strlen($b) - strlen($a);
    });

    // Cek apakah pesan mengandung kata kunci
    foreach ($responses["kata_kunci"] as $keyword => $response) {
        // Gunakan regex untuk mencocokkan frasa lengkap
        if (preg_match("/\b" . preg_quote($keyword, '/') . "\b/i", $message)) {
            // Jika kata kunci ditemukan, ganti placeholder (jika ada)
            if (strpos($response, "{{tanggal}}") !== false) {
                $response = str_replace("{{tanggal}}", date("Y-m-d"), $response);
            }
            return $response;
        }
    }

    // Jika tidak ada kata kunci yang cocok, kembalikan null
    return null;
}

/**
 * Muat riwayat percakapan dari file JSON
 *
 * @param string $from Nomor pengirim
 * @return array Riwayat percakapan
 */
function loadConversationHistory($from) {
    $filename = "history/" . basename($from) . ".json";
    if (file_exists($filename)) {
        $history = json_decode(file_get_contents($filename), true);
        if (is_array($history)) {
            return $history;
        }
    }
    return [];
}

/**
 * Simpan riwayat percakapan ke file JSON
 *
 * @param string $from Nomor pengirim
 * @param array $history Riwayat percakapan
 */
function saveConversationHistory($from, $history) {
    $filename = "history/" . basename($from) . ".json";
    if (!file_put_contents($filename, json_encode($history))) {
        error_log("Gagal menyimpan riwayat percakapan untuk {$from}");
    }
}

/**
 * Mengirim pesan ke OpenAI API dan mendapatkan respons AI
 *
 * @param array $history Riwayat percakapan
 * @param string $apiKey API key OpenAI
 * @return string Respons dari OpenAI
 */
function sendToOpenAI($history, $apiKey) {
    $url = 'https://api.openai.com/v1/chat/completions';

    // Tambahkan pesan sistem
    array_unshift($history, ["role" => "system", "content" => "Anda seorang asisten yang membantu."]);

    $data = [
        "messages" => $history,
        "model" => "gpt-4o-mini",
        "temperature" => 1,
        "max_tokens" => 1024,
        "top_p" => 1,
        "frequency_penalty" => 0,
        "presence_penalty" => 0
    ];

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => $headers,
    ]);

    $response = curl_exec($ch);
    if ($response === false) {
        $errorMessage = curl_error($ch);
        curl_close($ch);
        error_log("Kesalahan CURL: {$errorMessage}");
        return 'Maaf, saya tidak bisa memproses pesan Anda saat ini.';
    }

    curl_close($ch);
    $responseData = json_decode($response, true);

    if (isset($responseData['choices'][0]['message']['content'])) {
        return $responseData['choices'][0]['message']['content'];
    } else {
        error_log("Respons OpenAI tidak valid: " . print_r($responseData, true));
        return 'Maaf, saya tidak bisa memproses pesan Anda saat ini.';
    }
}

/**
 * Mengirim pesan ke pengguna melalui Starsender
 *
 * @param string $to Nomor tujuan
 * @param string $message Pesan
 * @param string $apiKey API key Starsender
 */
function sendMessage($to, $message, $apiKey) {
    $url = 'https://api.starsender.online/api/send'; // URL API Starsender

    $pesan = [
        "messageType" => "text",
        "to" => $to,
        "body" => $message,
        "delay" => 0, // Tidak ada delay
        "schedule" => null // Tidak ada jadwal
    ];

    $headers = [
        'Content-Type: application/json',
        'Authorization: ' . $apiKey
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($pesan),
        CURLOPT_HTTPHEADER => $headers,
    ]);

    $response = curl_exec($ch);
    if ($response === false) {
        $errorMessage = curl_error($ch);
        curl_close($ch);
        error_log("Kesalahan CURL saat mengirim pesan: {$errorMessage}");
    } else {
        // Log respons dari Starsender jika diperlukan
        error_log("Respons Starsender: {$response}");
    }
    curl_close($ch);

    return;
}

echo "Webhook berhasil diproses.";
?>
