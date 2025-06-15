<?php
    require_once __DIR__ . '/bootstrap.php';

    session_start();

    // Check if user is registered
    if (! isset($_SESSION['user_id'])) {
        header('Location: /');
        exit;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Random Video Chat</title>
    <script src="https://unpkg.com/peerjs@1.4.7/dist/peerjs.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Chat Interface -->
    <div class="container mx-auto p-4">
        <!-- Header -->
        <div class="bg-white p-4 rounded-lg shadow mb-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-800">Random Video Chat</h1>
            <a href="/logout.php" class="text-red-600 hover:text-red-800">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>

        <!-- Video Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div class="bg-white p-4 rounded-lg shadow">
                <h2 class="text-xl font-bold mb-4">Your Video</h2>
                <video id="localVideo" autoplay muted playsinline class="w-full rounded"></video>
            </div>
            <div class="bg-white p-4 rounded-lg shadow">
                <h2 class="text-xl font-bold mb-4">Stranger's Video</h2>
                <video id="remoteVideo" autoplay playsinline class="w-full rounded"></video>
            </div>
        </div>

        <!-- Controls -->
        <div class="bg-white p-4 rounded-lg shadow mb-4">
            <div class="video-controls bg-gray-800 p-4 rounded-lg">
                <div class="flex items-center justify-center space-x-4">
                    <button id="startButton" class="p-3 bg-green-600 text-white hover:bg-green-700 rounded-full transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </button>
                    <div id="connectionStatus" class="w-3 h-3 rounded-full hidden" title="Connection Status"></div>
                    <button id="nextButton" class="p-3 bg-blue-600 text-white hover:bg-blue-700 rounded-full transition-colors hidden">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                    <button id="stopButton" class="p-3 bg-red-600 text-white hover:bg-red-700 rounded-full transition-colors hidden">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" />
                        </svg>
                    </button>
                    <button id="screenShareButton" class="p-3 bg-purple-600 text-white hover:bg-purple-700 rounded-full transition-colors hidden" title="Share Screen">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </button>
                    <button id="settingsButton" class="p-3 bg-gray-600 text-white hover:bg-gray-700 rounded-full transition-colors" title="Device Settings">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Chat messages -->
        <div class="bg-white rounded-lg shadow mb-4">
            <div id="chatMessages" class="h-96 overflow-y-auto p-4 space-y-4">
                <!-- Messages will be added here -->
            </div>

            <!-- Typing indicator -->
            <div id="typingIndicator" class="hidden px-4 py-2 bg-gray-50 border-t">
                <div class="flex items-center space-x-2">
                    <div class="flex space-x-1">
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                    </div>
                    <span class="text-sm text-gray-500">Stranger is typing...</span>
                </div>
            </div>

            <!-- Message input -->
            <div class="border-t p-4 bg-white">
                <div class="flex space-x-2">
                    <div class="flex-1 flex space-x-2">
                        <button
                            id="imageUploadButton"
                            class="bg-gray-100 text-gray-600 px-3 py-2 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"
                            title="Send Image"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </button>
                        <input
                            type="file"
                            id="imageInput"
                            accept="image/*"
                            class="hidden"
                        />
                        <textarea
                            id="messageInput"
                            class="flex-1 border rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                            placeholder="Type a message..."
                            rows="1"
                            style="min-height: 44px; max-height: 120px;"
                        ></textarea>
                    </div>
                    <button
                        id="sendButton"
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center space-x-2"
                    >
                        <span>Send</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Modal -->
    <div id="settingsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-96">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Device Settings</h3>
                <button id="closeSettings" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label for="audioSource" class="block text-sm font-medium text-gray-700 mb-1">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                            </svg>
                            Microphone
                        </div>
                    </label>
                    <select id="audioSource" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Default Microphone</option>
                    </select>
                </div>

                <div>
                    <label for="videoSource" class="block text-sm font-medium text-gray-700 mb-1">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            Camera
                        </div>
                    </label>
                    <select id="videoSource" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Default Camera</option>
                    </select>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button id="applySettings" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Apply Changes
                </button>
            </div>
        </div>
    </div>

    <script>
        // Pass PHP variables to JavaScript
        window.APP_CONFIG = {
            sessionId: '<?php echo session_id(); ?>',
            userId: '<?php echo $_SESSION['user_id']; ?>',
            isRegistered: true
        };
    </script>
    <script src="/js/app.js"></script>
</body>
</html>