<?php
    require_once __DIR__ . '/bootstrap.php';

    session_start();

    // Check if user is registered
    $isRegistered = isset($_SESSION['user_id']);

    // If user is registered and trying to access index, redirect to chat
    if ($isRegistered && $_SERVER['REQUEST_URI'] === '/') {
        header('Location: /chat.php');
        exit;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Random Video Chat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg fixed w-full z-50">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="/" class="text-2xl font-bold text-purple-600">
                        <i class="fas fa-video mr-2"></i>RandomChat
                    </a>
                </div>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#features" class="text-gray-600 hover:text-purple-600">Features</a>
                    <a href="#how-it-works" class="text-gray-600 hover:text-purple-600">How It Works</a>
                    <a href="#safety" class="text-gray-600 hover:text-purple-600">Safety</a>
                    <?php if ($isRegistered): ?>
                        <a href="/chat.php" class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700">Start Chatting</a>
                        <a href="/logout.php" class="text-red-600 hover:text-red-800">Logout</a>
                    <?php else: ?>
                        <button id="startButton" class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700">Start Chatting</button>
                    <?php endif; ?>
                </div>
                <div class="md:hidden">
                    <button class="text-gray-600 hover:text-purple-600">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="pt-24 pb-12 bg-gradient-to-br from-purple-600 to-blue-500">
        <div class="container mx-auto px-4 py-16">
            <div class="text-center text-white">
                <h1 class="text-5xl font-bold mb-6">Connect with People Worldwide</h1>
                <p class="text-xl mb-8">Experience random video chats with people from around the globe</p>
                <button id="heroStartButton" class="bg-white text-purple-600 px-8 py-4 rounded-full text-xl font-bold hover:bg-opacity-90 transition-all transform hover:scale-105">
                    Start Chatting <i class="fas fa-video ml-2"></i>
                </button>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12">Why Choose RandomChat?</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-gray-50 p-6 rounded-lg shadow-sm">
                    <i class="fas fa-random text-4xl text-purple-600 mb-4"></i>
                    <h3 class="text-xl font-bold mb-2">Random Matching</h3>
                    <p class="text-gray-600">Get connected with random people from around the world instantly</p>
                </div>
                <div class="bg-gray-50 p-6 rounded-lg shadow-sm">
                    <i class="fas fa-shield-alt text-4xl text-purple-600 mb-4"></i>
                    <h3 class="text-xl font-bold mb-2">Safe & Secure</h3>
                    <p class="text-gray-600">Your privacy and security are our top priorities</p>
                </div>
                <div class="bg-gray-50 p-6 rounded-lg shadow-sm">
                    <i class="fas fa-comments text-4xl text-purple-600 mb-4"></i>
                    <h3 class="text-xl font-bold mb-2">Text Chat</h3>
                    <p class="text-gray-600">Chat with text while on video call</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12">How It Works</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="bg-white w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm">
                        <i class="fas fa-user-plus text-2xl text-purple-600"></i>
                    </div>
                    <h3 class="font-bold mb-2">1. Register</h3>
                    <p class="text-gray-600">Create your account in seconds</p>
                </div>
                <div class="text-center">
                    <div class="bg-white w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm">
                        <i class="fas fa-video text-2xl text-purple-600"></i>
                    </div>
                    <h3 class="font-bold mb-2">2. Start Chat</h3>
                    <p class="text-gray-600">Click the start button</p>
                </div>
                <div class="text-center">
                    <div class="bg-white w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm">
                        <i class="fas fa-random text-2xl text-purple-600"></i>
                    </div>
                    <h3 class="font-bold mb-2">3. Get Matched</h3>
                    <p class="text-gray-600">Connect with random people</p>
                </div>
                <div class="text-center">
                    <div class="bg-white w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm">
                        <i class="fas fa-comments text-2xl text-purple-600"></i>
                    </div>
                    <h3 class="font-bold mb-2">4. Chat</h3>
                    <p class="text-gray-600">Start video chatting</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Safety Section -->
    <section id="safety" class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12">Safety First</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="bg-gray-50 p-6 rounded-lg">
                    <h3 class="text-xl font-bold mb-4">Our Safety Features</h3>
                    <ul class="space-y-3">
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            <span>Age verification required</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            <span>Report inappropriate behavior</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            <span>Easy to skip to next person</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            <span>No personal information shared</span>
                        </li>
                    </ul>
                </div>
                <div class="bg-gray-50 p-6 rounded-lg">
                    <h3 class="text-xl font-bold mb-4">Community Guidelines</h3>
                    <ul class="space-y-3">
                        <li class="flex items-center">
                            <i class="fas fa-exclamation-circle text-yellow-500 mr-2"></i>
                            <span>Be respectful to others</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-exclamation-circle text-yellow-500 mr-2"></i>
                            <span>No inappropriate content</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-exclamation-circle text-yellow-500 mr-2"></i>
                            <span>No harassment or bullying</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-exclamation-circle text-yellow-500 mr-2"></i>
                            <span>No spamming or advertising</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4">RandomChat</h3>
                    <p class="text-gray-400">Connect with people worldwide through random video chats.</p>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="#features" class="text-gray-400 hover:text-white">Features</a></li>
                        <li><a href="#how-it-works" class="text-gray-400 hover:text-white">How It Works</a></li>
                        <li><a href="#safety" class="text-gray-400 hover:text-white">Safety</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Legal</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white">Terms of Service</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Privacy Policy</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Cookie Policy</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Connect</h4>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                <p>&copy;                                                   <?php echo date('Y'); ?> RandomChat. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Registration Modal -->
    <div id="registrationModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 w-full max-w-md">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold">Join Random Chat</h2>
                <button id="closeModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="registrationForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" name="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Date of Birth</label>
                    <input type="date" name="dob" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Gender</label>
                    <select name="gender" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                        <option value="">Select Gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <button type="submit" class="w-full bg-purple-600 text-white py-2 px-4 rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                    Start Chatting
                </button>
            </form>
        </div>
    </div>

    <script>
        // Pass PHP variables to JavaScript
        window.APP_CONFIG = {
            isRegistered:                                                   <?php echo $isRegistered ? 'true' : 'false'; ?>,
            userId: '<?php echo $_SESSION['user_id'] ?? ''; ?>'
        };

        // Modal handling
        const modal = document.getElementById('registrationModal');
        const startButtons = document.querySelectorAll('#startButton, #heroStartButton');
        const closeModal = document.getElementById('closeModal');
        const registrationForm = document.getElementById('registrationForm');

        startButtons.forEach(button => {
            button.addEventListener('click', () => {
                if (window.APP_CONFIG.isRegistered) {
                    window.location.href = '/chat.php';
                } else {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                }
            });
        });

        closeModal.addEventListener('click', () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        });

        // Close modal when clicking outside
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        });

        // Registration form handling
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
                    window.location.href = '/chat.php';
                } else {
                    alert(data.error || 'Registration failed');
                }
            } catch (error) {
                console.error('Registration error:', error);
                alert('Registration failed. Please try again.');
            }
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>
