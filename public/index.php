<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Random Chat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/peerjs@1.4.7/dist/peerjs.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h1 class="text-3xl font-bold text-center mb-8">Random Chat</h1>

                <!-- Video Container -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="relative">
                        <video id="localVideo" class="w-full rounded-lg bg-gray-800" autoplay muted playsinline></video>
                        <div class="absolute bottom-2 left-2 text-white text-sm">You</div>
                    </div>
                    <div class="relative">
                        <video id="remoteVideo" class="w-full rounded-lg bg-gray-800" autoplay playsinline></video>
                        <div class="absolute bottom-2 left-2 text-white text-sm">Stranger</div>
                    </div>
                </div>

                <!-- Controls -->
                <div class="flex justify-center space-x-4 mb-6">
                    <button id="startButton" class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg">
                        Start
                    </button>
                    <button id="nextButton" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg hidden">
                        Next
                    </button>
                    <button id="stopButton" class="bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded-lg hidden">
                        Stop
                    </button>
                </div>

                <!-- Chat Container -->
                <div class="border rounded-lg p-4 mb-4">
                    <div id="chatMessages" class="h-64 overflow-y-auto mb-4"></div>
                    <div class="flex space-x-2">
                        <input type="text" id="messageInput"
                               class="flex-1 border rounded-lg px-4 py-2 focus:outline-none focus:border-blue-500"
                               placeholder="Type your message...">
                        <button id="sendButton"
                                class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg">
                            Send
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/js/app.js"></script>
</body>
</html>
