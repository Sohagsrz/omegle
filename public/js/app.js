let peer = null;
let localStream = null;
let currentCall = null;
let dataConnection = null;
let sessionId = null;

const localVideo = document.getElementById('localVideo');
const remoteVideo = document.getElementById('remoteVideo');
const startButton = document.getElementById('startButton');
const nextButton = document.getElementById('nextButton');
const stopButton = document.getElementById('stopButton');
const messageInput = document.getElementById('messageInput');
const sendButton = document.getElementById('sendButton');
const chatMessages = document.getElementById('chatMessages');

// Initialize PeerJS
function initializePeer() {
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
    
    call.on('stream', (remoteStream) => {
        remoteVideo.srcObject = remoteStream;
    });

    call.on('close', () => {
        endCall();
    });
}

// Handle data connection for chat
function handleDataConnection(conn) {
    dataConnection = conn;
    
    conn.on('data', (data) => {
        addMessage(data, 'stranger');
    });

    conn.on('close', () => {
        dataConnection = null;
    });
}

// Start video chat
async function startChat() {
    try {
        localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
        localVideo.srcObject = localStream;
        
        initializePeer();
        
        // Request a random peer
        fetch('/api/find-peer.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ sessionId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.peerId) {
                const call = peer.call(data.peerId, localStream);
                handleCall(call);
                
                const conn = peer.connect(data.peerId);
                handleDataConnection(conn);
            }
        });

        startButton.classList.add('hidden');
        nextButton.classList.remove('hidden');
        stopButton.classList.remove('hidden');
    } catch (err) {
        console.error('Error accessing media devices:', err);
        alert('Error accessing camera and microphone');
    }
}

// End current chat
function endCall() {
    if (currentCall) {
        currentCall.close();
        currentCall = null;
    }
    if (dataConnection) {
        dataConnection.close();
        dataConnection = null;
    }
    remoteVideo.srcObject = null;
    chatMessages.innerHTML = '';
}

// Send chat message
function sendMessage() {
    const message = messageInput.value.trim();
    if (message && dataConnection) {
        dataConnection.send(message);
        addMessage(message, 'you');
        messageInput.value = '';
    }
}

// Add message to chat
function addMessage(message, sender) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `mb-2 ${sender === 'you' ? 'text-right' : 'text-left'}`;
    messageDiv.innerHTML = `
        <span class="inline-block px-4 py-2 rounded-lg ${
            sender === 'you' ? 'bg-blue-500 text-white' : 'bg-gray-200'
        }">${message}</span>
    `;
    chatMessages.appendChild(messageDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Event Listeners
startButton.addEventListener('click', startChat);
nextButton.addEventListener('click', () => {
    endCall();
    startChat();
});
stopButton.addEventListener('click', () => {
    endCall();
    startButton.classList.remove('hidden');
    nextButton.classList.add('hidden');
    stopButton.classList.add('hidden');
});

sendButton.addEventListener('click', sendMessage);
messageInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        sendMessage();
    }
}); 