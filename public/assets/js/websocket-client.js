/**
 * WebSocket Client for Real-time Presentation Collaboration
 * Handles connection, reconnection, and UI updates
 */

class PresentationWebSocket {
    constructor(presentationId, options = {}) {
        this.presentationId = presentationId;
        this.ws = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 3000;
        this.isConnecting = false;
        this.isIntentionallyClosed = false;
        
        // Configuration
        this.config = {
            url: options.url || 'ws://localhost:3002',
            userId: options.userId || 'anonymous',
            username: options.username || 'Anonymous User',
            debug: options.debug || false
        };
        
        // Callbacks
        this.onSlideUpdated = options.onSlideUpdated || this.defaultOnSlideUpdated;
        this.onSlideCreated = options.onSlideCreated || this.defaultOnSlideCreated;
        this.onSlideDeleted = options.onSlideDeleted || this.defaultOnSlideDeleted;
        this.onPresentationUpdated = options.onPresentationUpdated || this.defaultOnPresentationUpdated;
        this.onUserJoined = options.onUserJoined || this.defaultOnUserJoined;
        this.onUserLeft = options.onUserLeft || this.defaultOnUserLeft;
        
        // UI elements
        this.statusIndicator = null;
        this.notificationContainer = null;
        
        this.initUI();
        this.connect();
    }
    
    /**
     * Initialize UI elements
     */
    initUI() {
        // Create status indicator
        if (!document.querySelector('.ws-status-indicator')) {
            this.statusIndicator = document.createElement('div');
            this.statusIndicator.className = 'ws-status-indicator ws-disconnected';
            this.statusIndicator.innerHTML = '<span class="ws-status-dot"></span><span class="ws-status-text">Disconnected</span>';
            document.body.appendChild(this.statusIndicator);
        }
        
        // Create notification container
        if (!document.querySelector('.ws-notification-container')) {
            this.notificationContainer = document.createElement('div');
            this.notificationContainer.className = 'ws-notification-container';
            document.body.appendChild(this.notificationContainer);
        } else {
            this.notificationContainer = document.querySelector('.ws-notification-container');
        }
    }
    
    /**
     * Connect to WebSocket server
     */
    connect() {
        if (this.isConnecting || (this.ws && this.ws.readyState === WebSocket.OPEN)) {
            return;
        }
        
        this.isConnecting = true;
        this.updateStatus('connecting', 'Connecting...');
        this.log('Connecting to WebSocket server...');
        
        try {
            this.ws = new WebSocket(this.config.url);
            
            this.ws.onopen = () => this.handleOpen();
            this.ws.onmessage = (event) => this.handleMessage(event);
            this.ws.onerror = (error) => this.handleError(error);
            this.ws.onclose = (event) => this.handleClose(event);
            
        } catch (error) {
            this.log('Connection error:', error);
            this.isConnecting = false;
            this.scheduleReconnect();
        }
    }
    
    /**
     * Handle connection opened
     */
    handleOpen() {
        this.isConnecting = false;
        this.reconnectAttempts = 0;
        this.updateStatus('connected', 'Connected');
        this.log('Connected to WebSocket server');
        
        // Join presentation room
        this.send({
            type: 'join',
            presentationId: this.presentationId,
            userId: this.config.userId,
            username: this.config.username
        });
        
        this.showNotification('Connected to real-time updates', 'success');
    }
    
    /**
     * Handle incoming message
     */
    handleMessage(event) {
        try {
            const data = JSON.parse(event.data);
            this.log('Received message:', data.type);
            
            switch(data.type) {
                case 'connected':
                    this.log('Server acknowledged connection');
                    break;
                    
                case 'joined':
                    this.log(`Joined presentation ${data.presentationId}, room size: ${data.roomSize}`);
                    this.updateStatus('connected', `Connected (${data.roomSize} viewer${data.roomSize !== 1 ? 's' : ''})`);
                    break;
                    
                case 'slide-updated':
                    this.onSlideUpdated(data);
                    break;
                    
                case 'slide-created':
                    this.onSlideCreated(data);
                    break;
                    
                case 'slide-deleted':
                    this.onSlideDeleted(data);
                    break;
                    
                case 'presentation-updated':
                    this.onPresentationUpdated(data);
                    break;
                    
                case 'user-joined':
                    this.onUserJoined(data);
                    break;
                    
                case 'user-left':
                    this.onUserLeft(data);
                    break;
                    
                case 'update-confirmed':
                    this.log('Update confirmed by server');
                    break;
                    
                case 'pong':
                    this.log('Pong received');
                    break;
                    
                case 'error':
                    this.log('Server error:', data.message);
                    this.showNotification(data.message, 'error');
                    break;
                    
                case 'server-shutdown':
                    this.showNotification('Server is shutting down', 'warning');
                    break;
                    
                default:
                    this.log('Unknown message type:', data.type);
            }
        } catch (error) {
            this.log('Failed to parse message:', error);
        }
    }
    
    /**
     * Handle connection error
     */
    handleError(error) {
        this.log('WebSocket error:', error);
        this.updateStatus('error', 'Connection error');
    }
    
    /**
     * Handle connection closed
     */
    handleClose(event) {
        this.isConnecting = false;
        this.log('Disconnected from WebSocket server', event.code, event.reason);
        
        if (!this.isIntentionallyClosed) {
            this.updateStatus('disconnected', 'Disconnected');
            this.showNotification('Lost connection. Reconnecting...', 'warning');
            this.scheduleReconnect();
        }
    }
    
    /**
     * Schedule reconnection attempt
     */
    scheduleReconnect() {
        if (this.isIntentionallyClosed || this.reconnectAttempts >= this.maxReconnectAttempts) {
            if (this.reconnectAttempts >= this.maxReconnectAttempts) {
                this.updateStatus('error', 'Connection failed');
                this.showNotification('Unable to connect. Please refresh the page.', 'error');
            }
            return;
        }
        
        this.reconnectAttempts++;
        this.log(`Reconnecting in ${this.reconnectDelay}ms (attempt ${this.reconnectAttempts}/${this.maxReconnectAttempts})`);
        
        setTimeout(() => {
            this.connect();
        }, this.reconnectDelay);
    }
    
    /**
     * Send message to server
     */
    send(data) {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify(data));
            this.log('Sent message:', data.type);
        } else {
            this.log('Cannot send message: not connected');
        }
    }
    
    /**
     * Send slide update
     */
    sendSlideUpdate(slideId, content, title, layout) {
        this.send({
            type: 'slide-update',
            presentationId: this.presentationId,
            slideId: slideId,
            content: content,
            title: title,
            layout: layout
        });
    }
    
    /**
     * Send slide created notification
     */
    sendSlideCreated(slideId, title, layout) {
        this.send({
            type: 'slide-created',
            presentationId: this.presentationId,
            slideId: slideId,
            title: title,
            layout: layout
        });
    }
    
    /**
     * Send slide deleted notification
     */
    sendSlideDeleted(slideId) {
        this.send({
            type: 'slide-deleted',
            presentationId: this.presentationId,
            slideId: slideId
        });
    }
    
    /**
     * Send presentation update
     */
    sendPresentationUpdate(title, theme, language) {
        this.send({
            type: 'presentation-update',
            presentationId: this.presentationId,
            title: title,
            theme: theme,
            language: language
        });
    }
    
    /**
     * Disconnect from server
     */
    disconnect() {
        this.isIntentionallyClosed = true;
        
        if (this.ws) {
            this.send({
                type: 'leave',
                presentationId: this.presentationId
            });
            
            this.ws.close();
            this.ws = null;
        }
        
        this.updateStatus('disconnected', 'Disconnected');
        this.log('Disconnected');
    }
    
    /**
     * Update status indicator
     */
    updateStatus(status, text) {
        if (this.statusIndicator) {
            this.statusIndicator.className = `ws-status-indicator ws-${status}`;
            this.statusIndicator.querySelector('.ws-status-text').textContent = text;
        }
    }
    
    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        if (!this.notificationContainer) return;
        
        const notification = document.createElement('div');
        notification.className = `ws-notification ws-notification-${type}`;
        
        const icon = type === 'success' ? '✓' : type === 'error' ? '✗' : type === 'warning' ? '⚠' : 'ℹ';
        notification.innerHTML = `<span class="ws-notification-icon">${icon}</span><span class="ws-notification-message">${message}</span>`;
        
        this.notificationContainer.appendChild(notification);
        
        // Animate in
        setTimeout(() => notification.classList.add('ws-notification-show'), 10);
        
        // Remove after delay
        setTimeout(() => {
            notification.classList.remove('ws-notification-show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    /**
     * Log message (if debug enabled)
     */
    log(...args) {
        if (this.config.debug) {
            console.log('[WebSocket]', ...args);
        }
    }
    
    /**
     * Default slide updated handler
     */
    defaultOnSlideUpdated(data) {
        this.showNotification(`Slide "${data.title || 'Untitled'}" updated by ${data.updatedBy}`, 'info');
        
        // Highlight updated slide
        const slideElement = document.querySelector(`[data-slide-id="${data.slideId}"]`);
        if (slideElement) {
            slideElement.classList.add('ws-updated-remotely');
            setTimeout(() => slideElement.classList.remove('ws-updated-remotely'), 2000);
        }
        
        // Dispatch custom event for application to handle
        window.dispatchEvent(new CustomEvent('ws-slide-updated', { detail: data }));
    }
    
    /**
     * Default slide created handler
     */
    defaultOnSlideCreated(data) {
        this.showNotification(`New slide created by ${data.createdBy}`, 'success');
        window.dispatchEvent(new CustomEvent('ws-slide-created', { detail: data }));
    }
    
    /**
     * Default slide deleted handler
     */
    defaultOnSlideDeleted(data) {
        this.showNotification(`Slide deleted by ${data.deletedBy}`, 'warning');
        window.dispatchEvent(new CustomEvent('ws-slide-deleted', { detail: data }));
    }
    
    /**
     * Default presentation updated handler
     */
    defaultOnPresentationUpdated(data) {
        this.showNotification(`Presentation updated by ${data.updatedBy}`, 'info');
        window.dispatchEvent(new CustomEvent('ws-presentation-updated', { detail: data }));
    }
    
    /**
     * Default user joined handler
     */
    defaultOnUserJoined(data) {
        this.showNotification(`${data.username} joined`, 'info');
        this.updateStatus('connected', `Connected (${data.roomSize} viewer${data.roomSize !== 1 ? 's' : ''})`);
    }
    
    /**
     * Default user left handler
     */
    defaultOnUserLeft(data) {
        this.updateStatus('connected', `Connected (${data.roomSize} viewer${data.roomSize !== 1 ? 's' : ''})`);
    }
}

// Auto-initialize if on presentation view page
document.addEventListener('DOMContentLoaded', () => {
    // Check if we're on a presentation view page
    const pathMatch = window.location.pathname.match(/\/presentation\/view\/(\d+)/);
    
    if (pathMatch) {
        const presentationId = pathMatch[1];
        
        // Get user info from page (if available)
        const userId = document.body.dataset.userId || 'anonymous';
        const username = document.body.dataset.username || 'Anonymous User';
        
        // Create WebSocket client
        window.presentationWS = new PresentationWebSocket(presentationId, {
            userId: userId,
            username: username,
            debug: true // Enable logging
        });
        
        // Hook into slide edit events (if they exist)
        document.addEventListener('slideEdited', (e) => {
            if (window.presentationWS) {
                window.presentationWS.sendSlideUpdate(
                    e.detail.slideId,
                    e.detail.content,
                    e.detail.title,
                    e.detail.layout
                );
            }
        });
        
        // Hook into slide created events
        document.addEventListener('slideCreated', (e) => {
            if (window.presentationWS) {
                window.presentationWS.sendSlideCreated(
                    e.detail.slideId,
                    e.detail.title,
                    e.detail.layout
                );
            }
        });
        
        // Hook into slide deleted events
        document.addEventListener('slideDeleted', (e) => {
            if (window.presentationWS) {
                window.presentationWS.sendSlideDeleted(e.detail.slideId);
            }
        });
        
        // Disconnect on page unload
        window.addEventListener('beforeunload', () => {
            if (window.presentationWS) {
                window.presentationWS.disconnect();
            }
        });
        
        console.log('WebSocket client initialized for presentation', presentationId);
    }
});
