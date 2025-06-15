let peer = null;
let localStream = null;
let screenStream = null;
let currentCall = null;
let dataConnection = null;
let sessionId = null;
let isInCall = false;
let isScreenSharing = false;
let typingTimeout = null;
let availableDevices = {
    audioInputs: [],
    videoInputs: []
};
let isMobile = false;
let stopChat = false;
let callStartTime = null;
let callDurationInterval = null;
let isMuted = false;
let isVideoEnabled = true;
let currentVideoQuality = 'high';
let isDarkMode = false;
let intervalss = null;
let isSearching = false;
let isConnecting = false;
let connectionAttempts = 0;
const MAX_CONNECTION_ATTEMPTS = 3;

// Check if device is mobile
function checkDevice() {
    const userAgent = navigator.userAgent || navigator.vendor || window.opera;
    const mobileRegex = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i;
    isMobile = mobileRegex.test(userAgent.toLowerCase());
    
    // Hide settings button on mobile
    const settingsButton = document.getElementById('settingsButton');
    if (settingsButton) {
        settingsButton.classList.toggle('hidden', isMobile);
    }
}

// Registration form handling
const registrationForm = document.getElementById('registrationForm');
if (registrationForm) {
    registrationForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = {
            name: registrationForm.querySelector('[name="name"]').value,
            dob: registrationForm.querySelector('[name="dob"]').value,
            gender: registrationForm.querySelector('[name="gender"]').value
        };

        try {
            const response = await fetch('/api/register.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();
            
            if (data.success) {
                // Reload the page to show the chat interface
                window.location.reload();
            } else {
                alert(data.error || 'Registration failed');
            }
        } catch (error) {
            console.error('Registration error:', error);
            alert('Registration failed. Please try again.');
        }
    });
}

// Only initialize chat interface if user is registered
if (window.APP_CONFIG.isRegistered) {
    const localVideo = document.getElementById('localVideo');
    const remoteVideo = document.getElementById('remoteVideo');
    const startButton = document.getElementById('startButton');
    const nextButton = document.getElementById('nextButton');
    const stopButton = document.getElementById('stopButton');
    const screenShareButton = document.getElementById('screenShareButton');
    const messageInput = document.getElementById('messageInput');
    const sendButton = document.getElementById('sendButton');
    const chatMessages = document.getElementById('chatMessages');

    // Initialize PeerJS
    async function initializePeer() {
        peer = new Peer();
        
        peer.on('open', (id) => {
            console.log('My peer ID is: ' + id);
            sessionId = id;
        });

        peer.on('call', (call) => {
            call.answer(localStream);
            handleCall(call);
        });

        peer.on('connection', (conn) => {
            handleDataConnection(conn);
        });
    }

    // Handle incoming/outgoing calls
    function handleCall(call) {
        currentCall = call;
        isInCall = true;
        
        call.on('stream', (remoteStream) => {
            console.log('Received remote stream');
            remoteVideo.srcObject = remoteStream;
            startButton.classList.add('hidden');
            nextButton.classList.remove('hidden');
            stopButton.classList.remove('hidden');
            screenShareButton.classList.remove('hidden');
            updateConnectionStatus('connected');
            
            // Start call duration timer
            callStartTime = Date.now();
            callDurationInterval = setInterval(updateCallDuration, 1000);
            
            // Show enhanced controls
            showEnhancedControls();
        });

        call.on('close', async () => {
            console.log('Call closed');
            if (currentCall === call && !isConnecting) {  // Only handle if this is our current call and we're not connecting
                currentCall = null;
                updateConnectionStatus('disconnected');
                isInCall = false;
                
                // Clear call duration timer
                if (callDurationInterval) {
                    clearInterval(callDurationInterval);
                    callDurationInterval = null;
                }
                callStartTime = null;
                
                // Hide enhanced controls
                hideEnhancedControls(); 
                
                await startChat();
                
                // // Only try to find new peer if we're not already searching and haven't exceeded max attempts
                // if (!isSearching && connectionAttempts < MAX_CONNECTION_ATTEMPTS) {
                //     connectionAttempts++;
                //     console.log('Finding new peer...');
                //     await startChat();
                // }
            }
        });

        call.on('error', async (err) => {
            console.error('Call error:', err);
            if (currentCall === call && !isConnecting) {  // Only handle if this is our current call and we're not connecting
                currentCall = null;
                updateConnectionStatus('disconnected');
                // Only try to find new peer if we're not already searching and haven't exceeded max attempts
                if (!isSearching && connectionAttempts < MAX_CONNECTION_ATTEMPTS) {
                    connectionAttempts++;
                    await findNewPeer();
                }
            }
        });
    }

    // Handle data connection for chat
    function handleDataConnection(conn) {
        dataConnection = conn;
        
        conn.on('open', () => {
            console.log('Data connection opened');
        });
        
        conn.on('data', async (data) => {
            if (data.type === 'message') {
                addMessage(data.content, 'stranger');
                hideTypingIndicator();
            } else if (data.type === 'image') {
                try {
                    // Create blob from received data
                    const blob = new Blob([data.content.data], { type: data.content.type });
                    const blobUrl = URL.createObjectURL(blob);
                    
                    // Add image to chat
                    addMessage(blobUrl, 'stranger', true);
                    hideTypingIndicator();
                } catch (err) {
                    console.error('Error displaying received image:', err);
                }
            } else if (data.type === 'userLeft') {
                addMessage('Stranger has left the chat', 'system');
                // Instead of ending call, try to find a new peer
                if (connectionAttempts < MAX_CONNECTION_ATTEMPTS) {
                    connectionAttempts++;
                    await findNewPeer();
                }
            } else if (data.type === 'typing') {
                showTypingIndicator();
            } else if (data.type === 'stopTyping') {
                hideTypingIndicator();
            }
        });

        conn.on('close', async () => {
            console.log('Data connection closed');
            //end call
            console.log('Notifying server about chat end...');
                const response = await fetch('/api/end-chat.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        session_id: document.cookie.match(/PHPSESSID=([^;]+)/)?.[1] || '',
                        user_id: window.APP_CONFIG.userId
                    })
                });
                const data = await response.json();
                console.log('Server response:', data);
                 
            
            hideEnhancedControls(); 
            await findNewPeer();
            // if (dataConnection === conn && !isConnecting) {  // Only handle if this is our current connection and we're not connecting
            //     dataConnection = null;
            //     if (currentCall && !isSearching && connectionAttempts < MAX_CONNECTION_ATTEMPTS) {
            //         connectionAttempts++;
            //         await findNewPeer();
            //     }else{
                    
            //         await findNewPeer();
            //     }
            // }else{
                
            //     await findNewPeer();

            // }
            
        });

        conn.on('error', async (err) => {
            console.error('Data connection error:', err);
            if (dataConnection === conn && !isConnecting) {  // Only handle if this is our current connection and we're not connecting
                dataConnection = null;
                if (currentCall && !isSearching && connectionAttempts < MAX_CONNECTION_ATTEMPTS) {
                    connectionAttempts++;
                    await findNewPeer();
                }
            }
        });
    }

    // Find new peer
    async function findNewPeer() {
        if (isSearching || isConnecting) return; // Prevent multiple simultaneous searches
        isSearching = true;
        connectionAttempts = 0;
        
        console.log('Finding new peer...');
        
        // Clear current connections
        if (currentCall) {
            currentCall.close();
            currentCall = null;
        }
        if (dataConnection) {
            dataConnection.close();
            dataConnection = null;
        }
        
        // Clear remote video
        remoteVideo.srcObject = null;
        
        // Clear chat messages
        chatMessages.innerHTML = '';
        
        // Add system message
        addMessage('Looking for a new peer...', 'system');
        
        // Update connection status
        updateConnectionStatus('searching');
        
        // Try to find a new peer
        try {
            const response = await fetch('/api/find-peer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    session_id: window.APP_CONFIG.sessionId,
                    user_id: window.APP_CONFIG.userId,
                    peer_id: sessionId
                })
            });
            
            const data = await response.json();
            if (data.success && data.peer_id) {
                console.log('Found peer:', data);
                
                // Clear any existing interval
                if (intervalss) {
                    clearInterval(intervalss);
                    intervalss = null;
                }
                
                isConnecting = true;
                
                // Connect to new peer
                const call = peer.call(data.peer_id, localStream);
                handleCall(call);
                
                const conn = peer.connect(data.peer_id);
                handleDataConnection(conn);
                
                isSearching = false;
                isConnecting = false;
            } else {
                // If no peer found, show appropriate message
                addMessage('No peers available at the moment. Please try again later.', 'system');
                updateConnectionStatus('disconnected');
                
                 
                
                // Show start button
                await findNewPeer();
                // startButton.classList.remove('hidden');
                // nextButton.classList.add('hidden');
                // stopButton.classList.add('hidden');
                // screenShareButton.classList.add('hidden');
                
                // isSearching = false;
            }
        } catch (err) {
            console.error('Error finding new peer:', err);
            
            await findNewPeer();
            addMessage('Error finding a new peer. Please try again.', 'system');
            updateConnectionStatus('disconnected');
            
            // Show start button
            startButton.classList.remove('hidden');
            nextButton.classList.add('hidden');
            stopButton.classList.add('hidden');
            screenShareButton.classList.add('hidden');
            
            isSearching = false;
            isConnecting = false;
        }
    }

    // Modify endCall function to handle stop button differently
    async function endCall(isStopButton = false) {
        console.log('Ending call...');
        
        // Clear call duration timer
        if (callDurationInterval) {
            clearInterval(callDurationInterval);
            callDurationInterval = null;
        }
        callStartTime = null;
        
        // Hide enhanced controls
        hideEnhancedControls();
        
        if (currentCall) {
            // Stop screen sharing if active
            if (isScreenSharing) {
                await stopScreenSharing();
            }

            // Notify the other user that we're leaving
            if (dataConnection) {
                try {
                    dataConnection.send({
                        type: 'userLeft',
                        content: 'User has left the chat'
                    });
                } catch (err) {
                    console.error('Error sending leave message:', err);
                }
            }
            currentCall?.close();
            currentCall = null;
        }
        if (dataConnection) {
            dataConnection.close();
            dataConnection = null;
        }
        remoteVideo.srcObject = null;
        chatMessages.innerHTML = '';
        isInCall = false;

        // Show start button, hide other buttons
        startButton.classList.remove('hidden');
        nextButton.classList.add('hidden');
        stopButton.classList.add('hidden');
        screenShareButton.classList.add('hidden');
        updateConnectionStatus('hidden');

        // Only notify server if it's not a stop button click
        if (!isStopButton) {
            try {
                console.log('Notifying server about chat end...');
                const response = await fetch('/api/end-chat.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        session_id: document.cookie.match(/PHPSESSID=([^;]+)/)?.[1] || '',
                        user_id: window.APP_CONFIG.userId
                    })
                });
                const data = await response.json();
                console.log('Server response:', data);
            } catch (err) {
                console.error('Error ending chat:', err);
            }
        }
    }

    // Modify stop button click handler
    if (stopButton) {
        stopButton.addEventListener('click', async () => {
            // Notify the other user that we're stopping
            if (dataConnection) {
                try {
                    dataConnection.send({
                        type: 'userLeft',
                        content: 'User has stopped the chat'
                    });
                } catch (err) {
                    console.error('Error sending stop message:', err);
                }
            }
            
            // Clear current connections
            if (currentCall) {
                currentCall.close();
                currentCall = null;
            }
            if (dataConnection) {
                dataConnection.close();
                dataConnection = null;
            }
            
            // Clear remote video and chat
            remoteVideo.srcObject = null;
            chatMessages.innerHTML = '';
            
            // Only try to find new peer if we're not already searching
            if (!isSearching) {
                await findNewPeer();
            }
        });
    }

    // Add window unload handler
    window.addEventListener('beforeunload', async () => {
        console.log('Window closing, ending chat...');
        await endCall();
    });

    // Add visibility change handler
    document.addEventListener('visibilitychange', async () => {
        if (document.visibilityState === 'hidden') {
            // console.log('Page hidden, ending chat...');
            // await endCall();
        }
    });

    // Add network status handler
    window.addEventListener('offline', async () => {
        console.log('Network offline, ending chat...');
        await endCall();
    });

    // Show typing indicator
    function showTypingIndicator() {
        const typingDiv = document.getElementById('typingIndicator');
        if (typingDiv) {
            typingDiv.classList.remove('hidden');
        }
    }

    // Hide typing indicator
    function hideTypingIndicator() {
        const typingDiv = document.getElementById('typingIndicator');
        if (typingDiv) {
            typingDiv.classList.add('hidden');
        }
    }

    // Send typing status
    function sendTypingStatus(isTyping) {
        if (dataConnection && dataConnection.open) {
            dataConnection.send({
                type: isTyping ? 'typing' : 'stopTyping'
            });
        }
    }

    // Handle message input
    function handleMessageInput() {
        if (!messageInput) return;
        
        messageInput.addEventListener('input', () => {
            if (dataConnection && dataConnection.open) {
                // Clear existing timeout
                if (typingTimeout) {
                    clearTimeout(typingTimeout);
                }
                
                // Send typing status
                sendTypingStatus(true);
                
                // Set timeout to send stop typing after 1 second of no input
                typingTimeout = setTimeout(() => {
                    sendTypingStatus(false);
                }, 1000);
            }
        });

        messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    }

    // Send message
    function sendMessage() {
        if (!messageInput || !dataConnection || !dataConnection.open) return;
        
        const message = messageInput.value.trim();
        if (message) {
            // Send message to peer
            dataConnection.send({
                type: 'message',
                content: message
            });
            
            // Add message to chat
            addMessage(message, 'me');
            
            // Clear input and hide typing indicator
            messageInput.value = '';
            sendTypingStatus(false);
            if (typingTimeout) {
                clearTimeout(typingTimeout);
            }
        }
    }

    // Add message to chat
    function addMessage(message, sender, isImage = false) {
        const chatMessages = document.getElementById('chatMessages');
        if (!chatMessages) return;

        const messageDiv = document.createElement('div');
        messageDiv.className = `flex ${sender === 'me' ? 'justify-end' : 'justify-start'}`;

        const messageBubble = document.createElement('div');
        messageBubble.className = `max-w-[70%] rounded-lg px-4 py-2 ${
            sender === 'me' 
                ? 'bg-blue-600 text-white rounded-br-none' 
                : sender === 'stranger' 
                    ? 'bg-gray-200 text-gray-800 rounded-bl-none' 
                    : 'bg-gray-100 text-gray-600 text-sm italic'
        }`;

        if (isImage) {
            const img = document.createElement('img');
            img.src = message;
            img.className = 'max-w-full rounded-lg';
            img.alt = 'Shared image';
            
            // Add loading state
            img.onload = () => {
                img.classList.add('loaded');
            };
            
            // Add click handler to view full size
            img.addEventListener('click', () => {
                const fullSizeImg = document.createElement('img');
                fullSizeImg.src = message;
                fullSizeImg.className = 'fixed inset-0 m-auto max-h-[90vh] max-w-[90vw] object-contain z-50 cursor-zoom-out';
                fullSizeImg.onclick = () => fullSizeImg.remove();
                document.body.appendChild(fullSizeImg);
            });
            
            messageBubble.appendChild(img);
        } else {
            const messageText = document.createElement('p');
            messageText.textContent = message;
            messageBubble.appendChild(messageText);
        }

        messageDiv.appendChild(messageBubble);
        chatMessages.appendChild(messageDiv);

        // Auto scroll to bottom
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Event Listeners
    startButton.addEventListener('click', () => {
        stopChat = false;
        startChat();
    });

    // Next button click handler
    nextButton.addEventListener('click', async () => {
        await endCall();
        startChat();
    });

    // Stop button click handler
    stopButton.addEventListener('click', async () => {
        if(isInCall){
            await endCall();
            isInCall = false;
            currentCall = null;
            stopChat = true;
            // peer.destroy();
            peer = null;
            stopButton.classList.add('hidden');
            updateConnectionStatus('hidden');
            // refresh page
            window.location.reload();
        }else{
            isInCall = false;
            currentCall = null;
            stopChat = true;
            // peer.destroy();
            peer = null;
            stopButton.classList.add('hidden');
            updateConnectionStatus('hidden');
            // refresh page
            window.location.reload();
        }

        // Stop all video tracks
        if (localStream) {
            localStream.getTracks().forEach(track => track.stop());
            localStream = null;
            localVideo.srcObject = null;
        }
        // Show start button
        startButton.classList.remove('hidden');
    });

    sendButton.addEventListener('click', sendMessage);
    messageInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });

    // Initialize chat UI
    function initializeChatUI() {
        const messageInput = document.getElementById('messageInput');
        const sendButton = document.getElementById('sendButton');
        const chatMessages = document.getElementById('chatMessages');
        const imageUploadButton = document.getElementById('imageUploadButton');
        const imageInput = document.getElementById('imageInput');
        const screenShareButton = document.getElementById('screenShareButton');

        if (messageInput && sendButton) {
            // Auto-resize textarea
            messageInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });

            // Handle enter key
            messageInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });

            // Send button click
            sendButton.addEventListener('click', sendMessage);
        }

        // Handle image upload
        if (imageUploadButton && imageInput) {
            imageUploadButton.addEventListener('click', () => {
                imageInput.click();
            });

            imageInput.addEventListener('change', async (e) => {
                const file = e.target.files[0];
                if (file && file.type.startsWith('image/')) {
                    try {
                        // Read file as ArrayBuffer
                        const arrayBuffer = await file.arrayBuffer();
                        
                        // Send image data
                        if (dataConnection && dataConnection.open) {
                            dataConnection.send({
                                type: 'image',
                                content: {
                                    data: arrayBuffer,
                                    type: file.type,
                                    name: file.name
                                }
                            });
                            
                            // Create blob URL for local preview
                            const blob = new Blob([arrayBuffer], { type: file.type });
                            const blobUrl = URL.createObjectURL(blob);
                            
                            // Add image to chat
                            addMessage(blobUrl, 'me', true);
                            
                            // Clear file input
                            imageInput.value = '';
                        }
                    } catch (err) {
                        console.error('Error sending image:', err);
                    }
                }
            });
        }

        // Handle screen sharing
        if (screenShareButton) {
            screenShareButton.addEventListener('click', async () => {
                try {
                    if (!isScreenSharing) {
                        // Start screen sharing
                        screenStream = await navigator.mediaDevices.getDisplayMedia({
                            video: {
                                cursor: 'always'
                            },
                            audio: false
                        });

                        // Replace video track in the call
                        const videoTrack = screenStream.getVideoTracks()[0];
                        const sender = currentCall.peerConnection.getSenders().find(s => s.track.kind === 'video');
                        if (sender) {
                            sender.replaceTrack(videoTrack);
                        }

                        // Update UI
                        screenShareButton.classList.add('bg-red-600', 'hover:bg-red-700');
                        screenShareButton.classList.remove('bg-purple-600', 'hover:bg-purple-700');
                        isScreenSharing = true;

                        // Handle screen share stop
                        videoTrack.onended = async () => {
                            await stopScreenSharing();
                        };
                    } else {
                        await stopScreenSharing();
                    }
                } catch (err) {
                    console.error('Error sharing screen:', err);
                }
            });
        }

        // Initialize message input handling
        handleMessageInput();
    }

    // Get available media devices
    async function getMediaDevices() {
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            availableDevices.audioInputs = devices.filter(device => device.kind === 'audioinput');
            availableDevices.videoInputs = devices.filter(device => device.kind === 'videoinput');
            updateDeviceSelects();
        } catch (err) {
            console.error('Error getting media devices:', err);
        }
    }

    // Update device select options
    function updateDeviceSelects() {
        const audioSelect = document.getElementById('audioSource');
        const videoSelect = document.getElementById('videoSource');

        if (!audioSelect || !videoSelect) return;

        // Clear existing options
        audioSelect.innerHTML = '';
        videoSelect.innerHTML = '';

        // Add audio devices
        availableDevices.audioInputs.forEach(device => {
            const option = document.createElement('option');
            option.value = device.deviceId;
            option.text = device.label || `Microphone ${audioSelect.length + 1}`;
            audioSelect.appendChild(option);
        });

        // Add video devices
        availableDevices.videoInputs.forEach(device => {
            const option = document.createElement('option');
            option.value = device.deviceId;
            option.text = device.label || `Camera ${videoSelect.length + 1}`;
            videoSelect.appendChild(option);
        });
    }

    // Switch media devices
    async function switchMediaDevices() {
        const audioSource = document.getElementById('audioSource')?.value;
        const videoSource = document.getElementById('videoSource')?.value;

        try {
            // Stop current stream
            if (localStream) {
                localStream.getTracks().forEach(track => track.stop());
            }

            // Get new stream with selected devices
            localStream = await navigator.mediaDevices.getUserMedia({
                audio: { deviceId: audioSource ? { exact: audioSource } : undefined },
                video: { deviceId: videoSource ? { exact: videoSource } : undefined }
            });

            // Update local video
            localVideo.srcObject = localStream;

            // If in a call, update the stream
            if (currentCall) {
                const senders = currentCall.peerConnection.getSenders();
                senders.forEach(sender => {
                    if (sender.track.kind === 'audio') {
                        sender.replaceTrack(localStream.getAudioTracks()[0]);
                    } else if (sender.track.kind === 'video') {
                        sender.replaceTrack(localStream.getVideoTracks()[0]);
                    }
                });
            }

            // Close settings modal
            document.getElementById('settingsModal')?.classList.add('hidden');
        } catch (err) {
            console.error('Error switching devices:', err);
            alert('Error switching devices. Please try again.');
        }
    }

    // Initialize settings UI
    function initializeSettingsUI() {
        const settingsButton = document.getElementById('settingsButton');
        const settingsModal = document.getElementById('settingsModal');
        const closeSettings = document.getElementById('closeSettings');
        const applySettings = document.getElementById('applySettings');

        if (!settingsButton || !settingsModal || !closeSettings || !applySettings) return;

        // Only show settings for desktop users
        if (isMobile) {
            settingsButton.classList.add('hidden');
            return;
        }

        // Show settings modal
        settingsButton.addEventListener('click', async () => {
            settingsModal.classList.remove('hidden');
            await getMediaDevices();
        });

        // Close settings modal
        closeSettings.addEventListener('click', () => {
            settingsModal.classList.add('hidden');
        });

        // Apply settings
        applySettings.addEventListener('click', switchMediaDevices);

        // Close modal when clicking outside
        settingsModal.addEventListener('click', (e) => {
            if (e.target === settingsModal) {
                settingsModal.classList.add('hidden');
            }
        });
    }

    // Update connection status
    function updateConnectionStatus(status) {
        const statusIndicator = document.getElementById('connectionStatus');
        if (!statusIndicator) return;

        // Remove all status classes
        statusIndicator.classList.remove('bg-green-500', 'bg-red-500', 'bg-orange-500', 'hidden');

        switch (status) {
            case 'connected':
                statusIndicator.classList.add('bg-green-500');
                statusIndicator.title = 'Connected';
                startButton.classList.add('hidden');
                break;
            case 'disconnected':
                statusIndicator.classList.add('bg-red-500');
                statusIndicator.title = 'Disconnected';
                
                break;
            case 'waiting':
                statusIndicator.classList.add('bg-orange-500');
                statusIndicator.title = 'Waiting for peer...'; 
                startButton.classList.add('hidden');
                stopButton.classList.remove('hidden');
                break;
            case 'hidden':
                statusIndicator.classList.add('hidden');
                break;
        }
    }

    // Start video chat
    async function startChat() {
        try {
            // Don't start if already in a call
            if (isInCall) {
                console.log('Already in a call, not starting new one');
                return;
            }
            if(stopChat){
                return;
            }

            // Show waiting status
            updateConnectionStatus('waiting');

            // If we don't have a local stream yet, get it
            if (!localStream) {
                await getMediaDevices();
                localStream = await navigator.mediaDevices.getUserMedia({ 
                    video: true, 
                    audio: true 
                });
                localVideo.srcObject = localStream;
            }
            
            // Initialize PeerJS and wait for connection
            if (!peer) {
                await new Promise((resolve) => {
                    peer = new Peer();
                    
                    peer.on('open', (id) => {
                        console.log('My peer ID is: ' + id);
                        sessionId = id;
                        resolve();
                    });

                    peer.on('error', (err) => {
                        console.error('PeerJS error:', err);
                        updateConnectionStatus('disconnected');
                        // If there's an error with PeerJS, try to reconnect
                        peer.destroy();
                        peer = null;
                        startChat();
                    });

                    peer.on('disconnected', async () => {
                        console.log('PeerJS disconnected');
                        updateConnectionStatus('disconnected');
                        await endCall();
                    });

                    peer.on('close', async () => {
                        console.log('PeerJS closed');
                        updateConnectionStatus('disconnected');
                        await endCall();
                    });
                });

                peer.on('call', (call) => {
                    call.answer(localStream);
                    handleCall(call);
                });

                peer.on('connection', (conn) => {
                    handleDataConnection(conn);
                });
            }

            // Find a peer
            const response = await fetch('/api/find-peer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    session_id: document.cookie.match(/PHPSESSID=([^;]+)/)?.[1] || '',
                    user_id: window.APP_CONFIG.userId,
                    peer_id: sessionId
                })
            });

            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Failed to find peer');
            }

            if (data.status === 'waiting') {
                console.log('Waiting for a peer...');
                // Retry after a short delay if not in a call
                if (!isInCall) {
                    setTimeout(startChat, 2000);
                }
                return;
            }

            if (data.peer) {
                console.log('Found peer:', data.peer);
                // Hide start button while connecting
                startButton.classList.add('hidden');
                // Initiate call to the peer
                const call = peer.call(data.peer.peer_id, localStream);
                handleCall(call);

                // Create data connection
                const conn = peer.connect(data.peer.peer_id);
                handleDataConnection(conn);
            }
        } catch (err) {
            console.error('Error starting chat:', err);
            updateConnectionStatus('disconnected');
            startButton.classList.remove('hidden');
            nextButton.classList.add('hidden');
            stopButton.classList.add('hidden');
        }
    }

    // Stop screen sharing
    async function stopScreenSharing() {
        if (screenStream) {
            // Get the original video track
            const videoTrack = localStream.getVideoTracks()[0];
            
            // Replace screen track with original video track
            const sender = currentCall.peerConnection.getSenders().find(s => s.track.kind === 'video');
            if (sender) {
                sender.replaceTrack(videoTrack);
            }

            // Stop all tracks in screen stream
            screenStream.getTracks().forEach(track => track.stop());
            screenStream = null;

            // Update UI
            const screenShareButton = document.getElementById('screenShareButton');
            if (screenShareButton) {
                screenShareButton.classList.remove('bg-red-600', 'hover:bg-red-700');
                screenShareButton.classList.add('bg-purple-600', 'hover:bg-purple-700');
            }
            isScreenSharing = false;
        }
    }

    // Call checkDevice and initializeSettingsUI when the page loads
    document.addEventListener('DOMContentLoaded', () => {
        checkDevice();
        initializeChatUI();
        initializeSettingsUI();
    });
}

// Add new UI elements
function initializeEnhancedUI() {
    const videoControls = document.querySelector('.video-controls');
    if (!videoControls) return;

    // Add quality selector
    const qualitySelector = document.createElement('select');
    qualitySelector.id = 'qualitySelector';
    qualitySelector.className = 'bg-gray-700 text-white px-3 py-1 rounded hidden';
    qualitySelector.innerHTML = `
        <option value="low">Low Quality</option>
        <option value="medium">Medium Quality</option>
        <option value="high" selected>High Quality</option>
    `;
    videoControls.appendChild(qualitySelector);

    // Add mute button
    const muteButton = document.createElement('button');
    muteButton.id = 'muteButton';
    muteButton.className = 'bg-gray-700 hover:bg-gray-600 text-white px-3 py-1 rounded hidden';
    muteButton.innerHTML = '<i class="fas fa-microphone"></i>';
    videoControls.appendChild(muteButton);

    // Add video toggle button
    const videoToggleButton = document.createElement('button');
    videoToggleButton.id = 'videoToggleButton';
    videoToggleButton.className = 'bg-gray-700 hover:bg-gray-600 text-white px-3 py-1 rounded hidden';
    videoToggleButton.innerHTML = '<i class="fas fa-video"></i>';
    videoControls.appendChild(videoToggleButton);

    // Add call duration display
    const callDuration = document.createElement('div');
    callDuration.id = 'callDuration';
    callDuration.className = 'text-white text-sm hidden';
    videoControls.appendChild(callDuration);

    // Add theme toggle
    const themeToggle = document.createElement('button');
    themeToggle.id = 'themeToggle';
    themeToggle.className = 'bg-gray-700 hover:bg-gray-600 text-white px-3 py-1 rounded';
    themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
    videoControls.appendChild(themeToggle);

    // Initialize event listeners
    initializeEnhancedControls();
}

// Initialize enhanced controls
function initializeEnhancedControls() {
    const qualitySelector = document.getElementById('qualitySelector');
    const muteButton = document.getElementById('muteButton');
    const videoToggleButton = document.getElementById('videoToggleButton');
    const themeToggle = document.getElementById('themeToggle');

    if (qualitySelector) {
        qualitySelector.addEventListener('change', (e) => {
            currentVideoQuality = e.target.value;
            updateVideoQuality();
        });
    }

    if (muteButton) {
        muteButton.addEventListener('click', toggleMute);
    }

    if (videoToggleButton) {
        videoToggleButton.addEventListener('click', toggleVideo);
    }

    if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
    }
}

// Update video quality
async function updateVideoQuality() {
    if (!localStream) return;

    const constraints = {
        video: {
            width: { ideal: currentVideoQuality === 'high' ? 1280 : currentVideoQuality === 'medium' ? 720 : 480 },
            height: { ideal: currentVideoQuality === 'high' ? 720 : currentVideoQuality === 'medium' ? 480 : 360 },
            frameRate: { ideal: currentVideoQuality === 'high' ? 30 : currentVideoQuality === 'medium' ? 24 : 15 }
        }
    };

    try {
        const newStream = await navigator.mediaDevices.getUserMedia(constraints);
        const videoTrack = newStream.getVideoTracks()[0];
        
        if (currentCall) {
            const sender = currentCall.peerConnection.getSenders().find(s => s.track.kind === 'video');
            if (sender) {
                sender.replaceTrack(videoTrack);
            }
        }

        localStream.getVideoTracks().forEach(track => track.stop());
        localStream.addTrack(videoTrack);
        localVideo.srcObject = localStream;
    } catch (err) {
        console.error('Error updating video quality:', err);
    }
}

// Toggle mute
function toggleMute() {
    if (!localStream) return;

    const audioTrack = localStream.getAudioTracks()[0];
    if (audioTrack) {
        isMuted = !isMuted;
        audioTrack.enabled = !isMuted;
        
        const muteButton = document.getElementById('muteButton');
        if (muteButton) {
            muteButton.innerHTML = isMuted ? '<i class="fas fa-microphone-slash"></i>' : '<i class="fas fa-microphone"></i>';
            muteButton.classList.toggle('bg-red-600', isMuted);
            muteButton.classList.toggle('bg-gray-700', !isMuted);
        }
    }
}

// Toggle video
function toggleVideo() {
    if (!localStream) return;

    const videoTrack = localStream.getVideoTracks()[0];
    if (videoTrack) {
        isVideoEnabled = !isVideoEnabled;
        videoTrack.enabled = isVideoEnabled;
        
        const videoToggleButton = document.getElementById('videoToggleButton');
        if (videoToggleButton) {
            videoToggleButton.innerHTML = isVideoEnabled ? '<i class="fas fa-video"></i>' : '<i class="fas fa-video-slash"></i>';
            videoToggleButton.classList.toggle('bg-red-600', !isVideoEnabled);
            videoToggleButton.classList.toggle('bg-gray-700', isVideoEnabled);
        }
    }
}

// Toggle theme
function toggleTheme() {
    isDarkMode = !isDarkMode;
    document.documentElement.classList.toggle('dark', isDarkMode);
    
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.innerHTML = isDarkMode ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
    }
}

// Update call duration
function updateCallDuration() {
    if (!callStartTime) return;
    
    const duration = Math.floor((Date.now() - callStartTime) / 1000);
    const minutes = Math.floor(duration / 60);
    const seconds = duration % 60;
    
    const callDuration = document.getElementById('callDuration');
    if (callDuration) {
        callDuration.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }
}

// Show enhanced controls
function showEnhancedControls() {
    const qualitySelector = document.getElementById('qualitySelector');
    const muteButton = document.getElementById('muteButton');
    const videoToggleButton = document.getElementById('videoToggleButton');
    const callDuration = document.getElementById('callDuration');

    if (qualitySelector) qualitySelector.classList.remove('hidden');
    if (muteButton) muteButton.classList.remove('hidden');
    if (videoToggleButton) videoToggleButton.classList.remove('hidden');
    if (callDuration) callDuration.classList.remove('hidden');
}

// Hide enhanced controls
function hideEnhancedControls() {
    const qualitySelector = document.getElementById('qualitySelector');
    const muteButton = document.getElementById('muteButton');
    const videoToggleButton = document.getElementById('videoToggleButton');
    const callDuration = document.getElementById('callDuration');

    if (qualitySelector) qualitySelector.classList.add('hidden');
    if (muteButton) muteButton.classList.add('hidden');
    if (videoToggleButton) videoToggleButton.classList.add('hidden');
    if (callDuration) callDuration.classList.add('hidden');
} 