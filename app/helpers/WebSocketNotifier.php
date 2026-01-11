<?php

/**
 * WebSocket Notifier
 * 
 * Helper class to send real-time notifications to WebSocket clients
 * This enables server-side broadcasting of updates to connected clients
 */
class WebSocketNotifier
{
    private $wsUrl;
    
    public function __construct($wsUrl = 'ws://localhost:3002') {
        $this->wsUrl = $wsUrl;
    }
    
    /**
     * Notify clients about a slide update
     * 
     * @param int $presentationId Presentation ID
     * @param int $slideId Slide ID
     * @param int $userId User who made the update
     * @param string $username Username who made the update
     * @return bool Success status
     */
    public function notifySlideUpdate($presentationId, $slideId, $userId, $username) {
        return $this->sendNotification([
            'type' => 'slide-updated',
            'presentationId' => $presentationId,
            'slideId' => $slideId,
            'userId' => $userId,
            'username' => $username,
            'timestamp' => time()
        ]);
    }
    
    /**
     * Notify clients about a new slide creation
     */
    public function notifySlideCreate($presentationId, $slideId, $userId, $username) {
        return $this->sendNotification([
            'type' => 'slide-created',
            'presentationId' => $presentationId,
            'slideId' => $slideId,
            'userId' => $userId,
            'username' => $username,
            'timestamp' => time()
        ]);
    }
    
    /**
     * Notify clients about a slide deletion
     */
    public function notifySlideDelete($presentationId, $slideId, $userId, $username) {
        return $this->sendNotification([
            'type' => 'slide-deleted',
            'presentationId' => $presentationId,
            'slideId' => $slideId,
            'userId' => $userId,
            'username' => $username,
            'timestamp' => time()
        ]);
    }
    
    /**
     * Notify clients about a presentation update
     */
    public function notifyPresentationUpdate($presentationId, $userId, $username) {
        return $this->sendNotification([
            'type' => 'presentation-updated',
            'presentationId' => $presentationId,
            'userId' => $userId,
            'username' => $username,
            'timestamp' => time()
        ]);
    }
    
    /**
     * Send notification via HTTP to WebSocket server's broadcast endpoint
     * Note: This requires the WebSocket server to have an HTTP endpoint
     */
    private function sendNotification($data) {
        try {
            // Convert ws:// to http:// for the broadcast endpoint
            $httpUrl = str_replace('ws://', 'http://', $this->wsUrl);
            $broadcastUrl = $httpUrl . '/broadcast';
            
            $payload = json_encode($data);
            
            error_log("[WebSocket] Attempting to broadcast: {$data['type']} to {$broadcastUrl}");
            error_log("[WebSocket] Payload: " . $payload);
            
            $ch = curl_init($broadcastUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload)
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2); // Short timeout for non-critical operation
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            error_log("[WebSocket] HTTP Response Code: $httpCode");
            error_log("[WebSocket] Response: " . ($response ?: 'empty'));
            if ($curlError) {
                error_log("[WebSocket] CURL Error: $curlError");
            }
            
            if ($httpCode === 200) {
                error_log("[WebSocket] Broadcast successful: {$data['type']}");
                return true;
            } else {
                error_log("[WebSocket] Broadcast failed with HTTP code: $httpCode");
                return false;
            }
        } catch (Exception $e) {
            error_log("[WebSocket] Broadcast error: " . $e->getMessage());
            return false;
        }
    }
}
