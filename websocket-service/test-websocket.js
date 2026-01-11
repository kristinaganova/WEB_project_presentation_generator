/**
 * WebSocket Service Test Suite
 * Tests WebSocket server functionality including connection, messaging, and room management
 */

const WebSocket = require('ws');

class WebSocketTester {
    constructor() {
        this.wsUrl = 'ws://localhost:3002';
        this.tests = [];
        this.passedTests = 0;
        this.failedTests = 0;
    }
    
    /**
     * Create WebSocket connection
     */
    createConnection() {
        return new Promise((resolve, reject) => {
            const ws = new WebSocket(this.wsUrl);
            const timeout = setTimeout(() => {
                ws.close();
                reject(new Error('Connection timeout'));
            }, 5000);
            
            ws.on('open', () => {
                clearTimeout(timeout);
                resolve(ws);
            });
            
            ws.on('error', (error) => {
                clearTimeout(timeout);
                reject(error);
            });
        });
    }
    
    /**
     * Send message and wait for response
     */
    sendAndWait(ws, message, expectedType, timeout = 3000) {
        return new Promise((resolve, reject) => {
            const timeoutId = setTimeout(() => {
                ws.removeAllListeners('message');
                reject(new Error(`Timeout waiting for ${expectedType}`));
            }, timeout);
            
            const handler = (data) => {
                try {
                    const response = JSON.parse(data);
                    if (response.type === expectedType) {
                        clearTimeout(timeoutId);
                        ws.removeListener('message', handler);
                        resolve(response);
                    }
                } catch (error) {
                    // Ignore parse errors, wait for correct message
                }
            };
            
            ws.on('message', handler);
            ws.send(JSON.stringify(message));
        });
    }
    
    /**
     * Run a test
     */
    async runTest(name, testFn) {
        process.stdout.write(`[TEST] ${name}... `);
        
        try {
            await testFn();
            console.log('\x1b[32m✓ PASS\x1b[0m');
            this.passedTests++;
            return true;
        } catch (error) {
            console.log(`\x1b[31m✗ FAIL\x1b[0m`);
            console.log(`       Error: ${error.message}`);
            this.failedTests++;
            return false;
        }
    }
    
    /**
     * Test 1: Server is running and accepts connections
     */
    async testServerConnection() {
        const ws = await this.createConnection();
        
        // Wait for connected message
        const response = await new Promise((resolve, reject) => {
            const timeout = setTimeout(() => reject(new Error('No welcome message')), 2000);
            
            ws.on('message', (data) => {
                clearTimeout(timeout);
                resolve(JSON.parse(data));
            });
        });
        
        if (response.type !== 'connected') {
            throw new Error('Expected connected message');
        }
        
        ws.close();
    }
    
    /**
     * Test 2: Join presentation room
     */
    async testJoinRoom() {
        const ws = await this.createConnection();
        
        // Wait for connected message
        await new Promise(resolve => {
            ws.once('message', () => resolve());
        });
        
        const response = await this.sendAndWait(ws, {
            type: 'join',
            presentationId: 'test-123',
            userId: 'user1',
            username: 'Test User 1'
        }, 'joined');
        
        if (response.presentationId !== 'test-123') {
            throw new Error('Incorrect presentation ID in response');
        }
        
        if (response.roomSize !== 1) {
            throw new Error('Expected room size of 1');
        }
        
        ws.close();
    }
    
    /**
     * Test 3: Multiple clients in same room
     */
    async testMultipleClientsInRoom() {
        const ws1 = await this.createConnection();
        const ws2 = await this.createConnection();
        
        // Wait for connected messages
        await Promise.all([
            new Promise(resolve => ws1.once('message', () => resolve())),
            new Promise(resolve => ws2.once('message', () => resolve()))
        ]);
        
        // Client 1 joins
        await this.sendAndWait(ws1, {
            type: 'join',
            presentationId: 'test-multi',
            userId: 'user1',
            username: 'User 1'
        }, 'joined');
        
        // Client 2 joins same room
        const joinResponse = await this.sendAndWait(ws2, {
            type: 'join',
            presentationId: 'test-multi',
            userId: 'user2',
            username: 'User 2'
        }, 'joined');
        
        if (joinResponse.roomSize !== 2) {
            throw new Error(`Expected room size of 2, got ${joinResponse.roomSize}`);
        }
        
        ws1.close();
        ws2.close();
    }
    
    /**
     * Test 4: Slide update broadcast
     */
    async testSlideUpdateBroadcast() {
        const ws1 = await this.createConnection();
        const ws2 = await this.createConnection();
        
        // Wait for connected messages
        await Promise.all([
            new Promise(resolve => ws1.once('message', () => resolve())),
            new Promise(resolve => ws2.once('message', () => resolve()))
        ]);
        
        // Both join same room
        await Promise.all([
            this.sendAndWait(ws1, {
                type: 'join',
                presentationId: 'test-broadcast',
                userId: 'user1',
                username: 'User 1'
            }, 'joined'),
            this.sendAndWait(ws2, {
                type: 'join',
                presentationId: 'test-broadcast',
                userId: 'user2',
                username: 'User 2'
            }, 'joined')
        ]);
        
        // Client 2 listens for update
        const updatePromise = new Promise((resolve) => {
            ws2.on('message', (data) => {
                const msg = JSON.parse(data);
                if (msg.type === 'slide-updated') {
                    resolve(msg);
                }
            });
        });
        
        // Client 1 sends update
        ws1.send(JSON.stringify({
            type: 'slide-update',
            presentationId: 'test-broadcast',
            slideId: 'slide-1',
            title: 'Updated Title',
            content: '{"text": "Updated content"}'
        }));
        
        const update = await updatePromise;
        
        if (update.slideId !== 'slide-1' || update.title !== 'Updated Title') {
            throw new Error('Update not broadcast correctly');
        }
        
        ws1.close();
        ws2.close();
    }
    
    /**
     * Test 5: Slide creation notification
     */
    async testSlideCreationNotification() {
        const ws1 = await this.createConnection();
        const ws2 = await this.createConnection();
        
        await Promise.all([
            new Promise(resolve => ws1.once('message', () => resolve())),
            new Promise(resolve => ws2.once('message', () => resolve()))
        ]);
        
        await Promise.all([
            this.sendAndWait(ws1, {
                type: 'join',
                presentationId: 'test-create',
                userId: 'user1',
                username: 'User 1'
            }, 'joined'),
            this.sendAndWait(ws2, {
                type: 'join',
                presentationId: 'test-create',
                userId: 'user2',
                username: 'User 2'
            }, 'joined')
        ]);
        
        const createPromise = new Promise((resolve) => {
            ws2.on('message', (data) => {
                const msg = JSON.parse(data);
                if (msg.type === 'slide-created') {
                    resolve(msg);
                }
            });
        });
        
        ws1.send(JSON.stringify({
            type: 'slide-created',
            presentationId: 'test-create',
            slideId: 'new-slide',
            title: 'New Slide',
            layout: 'blank'
        }));
        
        const notification = await createPromise;
        
        if (notification.slideId !== 'new-slide') {
            throw new Error('Creation notification not received');
        }
        
        ws1.close();
        ws2.close();
    }
    
    /**
     * Test 6: Slide deletion notification
     */
    async testSlideDeleteNotification() {
        const ws1 = await this.createConnection();
        const ws2 = await this.createConnection();
        
        await Promise.all([
            new Promise(resolve => ws1.once('message', () => resolve())),
            new Promise(resolve => ws2.once('message', () => resolve()))
        ]);
        
        await Promise.all([
            this.sendAndWait(ws1, {
                type: 'join',
                presentationId: 'test-delete',
                userId: 'user1',
                username: 'User 1'
            }, 'joined'),
            this.sendAndWait(ws2, {
                type: 'join',
                presentationId: 'test-delete',
                userId: 'user2',
                username: 'User 2'
            }, 'joined')
        ]);
        
        const deletePromise = new Promise((resolve) => {
            ws2.on('message', (data) => {
                const msg = JSON.parse(data);
                if (msg.type === 'slide-deleted') {
                    resolve(msg);
                }
            });
        });
        
        ws1.send(JSON.stringify({
            type: 'slide-deleted',
            presentationId: 'test-delete',
            slideId: 'deleted-slide'
        }));
        
        const notification = await deletePromise;
        
        if (notification.slideId !== 'deleted-slide') {
            throw new Error('Deletion notification not received');
        }
        
        ws1.close();
        ws2.close();
    }
    
    /**
     * Test 7: Ping-pong heartbeat
     */
    async testPingPong() {
        const ws = await this.createConnection();
        
        await new Promise(resolve => ws.once('message', () => resolve()));
        
        const response = await this.sendAndWait(ws, {
            type: 'ping'
        }, 'pong', 2000);
        
        if (!response.timestamp) {
            throw new Error('Pong missing timestamp');
        }
        
        ws.close();
    }
    
    /**
     * Test 8: Isolated rooms
     */
    async testIsolatedRooms() {
        const ws1 = await this.createConnection();
        const ws2 = await this.createConnection();
        
        await Promise.all([
            new Promise(resolve => ws1.once('message', () => resolve())),
            new Promise(resolve => ws2.once('message', () => resolve()))
        ]);
        
        // Join different rooms
        await Promise.all([
            this.sendAndWait(ws1, {
                type: 'join',
                presentationId: 'room-a',
                userId: 'user1',
                username: 'User 1'
            }, 'joined'),
            this.sendAndWait(ws2, {
                type: 'join',
                presentationId: 'room-b',
                userId: 'user2',
                username: 'User 2'
            }, 'joined')
        ]);
        
        // Set up listener on ws2
        let receivedUpdate = false;
        ws2.on('message', (data) => {
            const msg = JSON.parse(data);
            if (msg.type === 'slide-updated') {
                receivedUpdate = true;
            }
        });
        
        // ws1 sends update to room-a
        ws1.send(JSON.stringify({
            type: 'slide-update',
            presentationId: 'room-a',
            slideId: 'slide-1',
            content: '{}'
        }));
        
        // Wait a bit
        await new Promise(resolve => setTimeout(resolve, 500));
        
        if (receivedUpdate) {
            throw new Error('Update leaked to different room');
        }
        
        ws1.close();
        ws2.close();
    }
    
    /**
     * Test 9: Error handling for invalid messages
     */
    async testErrorHandling() {
        const ws = await this.createConnection();
        
        await new Promise(resolve => ws.once('message', () => resolve()));
        
        // Send invalid JSON
        ws.send('invalid json');
        
        // Should receive error response
        const errorResponse = await new Promise((resolve, reject) => {
            const timeout = setTimeout(() => reject(new Error('No error response')), 2000);
            
            ws.on('message', (data) => {
                const msg = JSON.parse(data);
                if (msg.type === 'error') {
                    clearTimeout(timeout);
                    resolve(msg);
                }
            });
        });
        
        if (!errorResponse.message) {
            throw new Error('Error response missing message');
        }
        
        ws.close();
    }
    
    /**
     * Test 10: Reconnection scenario
     */
    async testReconnection() {
        const ws1 = await this.createConnection();
        
        await new Promise(resolve => ws1.once('message', () => resolve()));
        
        await this.sendAndWait(ws1, {
            type: 'join',
            presentationId: 'test-reconnect',
            userId: 'user1',
            username: 'User 1'
        }, 'joined');
        
        // Close connection
        ws1.close();
        
        // Wait a bit
        await new Promise(resolve => setTimeout(resolve, 500));
        
        // Reconnect
        const ws2 = await this.createConnection();
        
        await new Promise(resolve => ws2.once('message', () => resolve()));
        
        // Rejoin same room
        const rejoinResponse = await this.sendAndWait(ws2, {
            type: 'join',
            presentationId: 'test-reconnect',
            userId: 'user1',
            username: 'User 1'
        }, 'joined');
        
        if (!rejoinResponse.presentationId) {
            throw new Error('Failed to rejoin room');
        }
        
        ws2.close();
    }
    
    /**
     * Run all tests
     */
    async runAllTests() {
        console.log('='.repeat(70));
        console.log('WebSocket Service Test Suite');
        console.log('='.repeat(70));
        console.log('');
        
        await this.runTest('Server connection', () => this.testServerConnection());
        await this.runTest('Join presentation room', () => this.testJoinRoom());
        await this.runTest('Multiple clients in same room', () => this.testMultipleClientsInRoom());
        await this.runTest('Slide update broadcast', () => this.testSlideUpdateBroadcast());
        await this.runTest('Slide creation notification', () => this.testSlideCreationNotification());
        await this.runTest('Slide deletion notification', () => this.testSlideDeleteNotification());
        await this.runTest('Ping-pong heartbeat', () => this.testPingPong());
        await this.runTest('Isolated rooms', () => this.testIsolatedRooms());
        await this.runTest('Error handling', () => this.testErrorHandling());
        await this.runTest('Reconnection scenario', () => this.testReconnection());
        
        console.log('');
        console.log('='.repeat(70));
        console.log('TEST SUMMARY');
        console.log('='.repeat(70));
        console.log(`Total tests:  ${this.passedTests + this.failedTests}`);
        console.log(`\x1b[32mPassed:       ${this.passedTests}\x1b[0m`);
        console.log(`\x1b[31mFailed:       ${this.failedTests}\x1b[0m`);
        console.log(`Success rate: ${Math.round((this.passedTests / (this.passedTests + this.failedTests)) * 100)}%`);
        console.log('='.repeat(70));
        
        if (this.failedTests === 0) {
            console.log('\n\x1b[32m✓ All tests passed!\x1b[0m\n');
        } else {
            console.log('\n\x1b[31m✗ Some tests failed\x1b[0m\n');
            process.exit(1);
        }
    }
}

// Run tests
const tester = new WebSocketTester();
tester.runAllTests().catch(error => {
    console.error('\n\x1b[31mTest suite error:\x1b[0m', error.message);
    process.exit(1);
});
