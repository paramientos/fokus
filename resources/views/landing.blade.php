<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fokus - Modern Project Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #f8fafc;
        }

        .gradient-text {
            background: linear-gradient(90deg, #38bdf8, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .feature-card {
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            border-color: rgba(56, 189, 248, 0.5);
        }

        .btn-primary {
            background: linear-gradient(90deg, #38bdf8, #818cf8);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(56, 189, 248, 0.4);
        }

        .hero-image {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
            100% {
                transform: translateY(0px);
            }
        }
    </style>
</head>
<body class="min-h-screen">
<header class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center">
        <div class="flex items-center">
            <i class="fas fa-cube text-3xl text-blue-400 mr-3"></i>
            <h1 class="text-2xl font-bold gradient-text">Fokus</h1>
        </div>
        <nav class="hidden md:flex space-x-8">
            <a href="#features" class="text-gray-300 hover:text-white transition">Features</a>
            <a href="#workflow" class="text-gray-300 hover:text-white transition">Workflow</a>
            <a href="#testimonials" class="text-gray-300 hover:text-white transition">Testimonials</a>
        </nav>
        <div class="flex space-x-4">
            @auth('web')
                <a href="{{ route('dashboard') }}"
                   class="px-4 py-2 rounded-lg transition btn-primary">My Dashboard</a>
            @else
                <a href="{{ route('login') }}"
                   class="px-4 py-2 rounded-lg text-white hover:text-blue-400 transition">Login</a>

                <a href="{{ route('register') }}" class="px-6 py-2 rounded-lg btn-primary font-medium">Sign Up</a>
            @endauth
        </div>
    </div>
</header>

<main>
    <!-- Hero Section -->
    <section class="container mx-auto px-4 py-20 flex flex-col md:flex-row items-center">
        <div class="md:w-1/2 mb-10 md:mb-0">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6">
                Stop Tab Switching <span class="gradient-text"><br>Start Shipping...</span>
            </h1>
            <p class="text-xl text-gray-300 mb-8">
                Fokus combines the best of Jira, Slack, and retrospective tools into one seamless platform. Manage
                projects, collaborate, and improve - all in one place.
            </p>
            <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
                <a href="{{ route('login') }}" class="px-8 py-3 rounded-lg btn-primary font-medium text-center">
                    Get Started Free
                </a>
                <a href="#features"
                   class="px-8 py-3 rounded-lg border border-gray-600 text-center hover:border-blue-400 hover:text-blue-400 transition">
                    Learn More
                </a>
            </div>
        </div>
        <div class="md:w-1/2 flex justify-center">
            <img src="{{ asset('/assets/images/sprint-overview-chart-fokus.png') }}"
                 alt="Fokus Dashboard Preview" class="rounded-xl shadow-2xl hero-image max-w-full">
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-slate-900">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-16">
                Everything Your Team Needs in <span class="gradient-text">One Place</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1: Project Management -->
                <div class="feature-card rounded-xl p-6">
                    <div class="w-14 h-14 bg-blue-500 bg-opacity-20 rounded-lg flex items-center justify-center mb-6">
                        <i class="fas fa-project-diagram text-2xl text-blue-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Project Management</h3>
                    <p class="text-gray-400">Create and manage projects, track health metrics, and manage team members
                        with customizable roles and permissions.</p>
                </div>

                <!-- Feature 2: Task Management -->
                <div class="feature-card rounded-xl p-6">
                    <div class="w-14 h-14 bg-purple-500 bg-opacity-20 rounded-lg flex items-center justify-center mb-6">
                        <i class="fas fa-tasks text-2xl text-purple-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Task Management</h3>
                    <p class="text-gray-400">Create, assign, and track tasks with customizable workflows, status
                        transitions, and detailed history tracking.</p>
                </div>

                <!-- Feature 3: Sprint Management -->
                <div class="feature-card rounded-xl p-6">
                    <div class="w-14 h-14 bg-green-500 bg-opacity-20 rounded-lg flex items-center justify-center mb-6">
                        <i class="fas fa-chart-line text-2xl text-green-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Sprint Management</h3>
                    <p class="text-gray-400">Plan sprints, track progress with burndown charts, generate reports, and
                        conduct retrospective meetings all in one place.</p>
                </div>

                <!-- Feature 4: Kanban Board -->
                <div class="feature-card rounded-xl p-6">
                    <div class="w-14 h-14 bg-yellow-500 bg-opacity-20 rounded-lg flex items-center justify-center mb-6">
                        <i class="fas fa-columns text-2xl text-yellow-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Kanban Board</h3>
                    <p class="text-gray-400">Visualize your workflow with drag-and-drop task management, customizable
                        columns, and real-time updates.</p>
                </div>

                <!-- Feature 5: Meeting Management -->
                <div class="feature-card rounded-xl p-6">
                    <div class="w-14 h-14 bg-red-500 bg-opacity-20 rounded-lg flex items-center justify-center mb-6">
                        <i class="fas fa-calendar-alt text-2xl text-red-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Meeting Management</h3>
                    <p class="text-gray-400">Schedule, conduct, and document meetings with integrated video conferencing
                        and exportable meeting notes.</p>
                </div>

                <!-- Feature 6: Wiki -->
                <div class="feature-card rounded-xl p-6">
                    <div class="w-14 h-14 bg-indigo-500 bg-opacity-20 rounded-lg flex items-center justify-center mb-6">
                        <i class="fas fa-book text-2xl text-indigo-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Team Wiki</h3>
                    <p class="text-gray-400">Create and maintain documentation, guides, and knowledge bases for your
                        team with rich text editing and version history.</p>
                </div>

                <!-- Feature 7: Password Vault -->
                <div class="feature-card rounded-xl p-6">
                    <div class="w-14 h-14 bg-blue-500 bg-opacity-20 rounded-lg flex items-center justify-center mb-6">
                        <i class="fas fa-landmark text-2xl text-blue-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Password Vault</h3>
                    <p class="text-gray-400">Securely store and share team credentials with encrypted password
                        management, security checks, and vault locking.</p>
                </div>

                <!-- Feature 8: API Tester -->
                <div class="feature-card rounded-xl p-6">
                    <div class="w-14 h-14 bg-purple-500 bg-opacity-20 rounded-lg flex items-center justify-center mb-6">
                        <i class="fas fa-code text-2xl text-purple-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">API Tester</h3>
                    <p class="text-gray-400">Test API endpoints, save request history, and integrate with your project
                        tasks for seamless development workflows.</p>
                </div>

                <!-- Feature 9: Asset Management -->
                <div class="feature-card rounded-xl p-6">
                    <div class="w-14 h-14 bg-green-500 bg-opacity-20 rounded-lg flex items-center justify-center mb-6">
                        <i class="fas fa-laptop text-2xl text-green-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Asset Management</h3>
                    <p class="text-gray-400">Track hardware and software assets, manage categories, and keep software
                        licenses organized and up to date.</p>
                </div>

                <!-- Feature 10: Activity Timeline -->
                <div class="feature-card rounded-xl p-6">
                    <div class="w-14 h-14 bg-yellow-500 bg-opacity-20 rounded-lg flex items-center justify-center mb-6">
                        <i class="fas fa-history text-2xl text-yellow-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Activity Timeline</h3>
                    <p class="text-gray-400">Track all project activities, task changes, and sprint progress with a
                        comprehensive and filterable timeline view.</p>
                </div>

                <!-- Feature 11: Workspace Management -->
                <div class="feature-card rounded-xl p-6">
                    <div class="w-14 h-14 bg-red-500 bg-opacity-20 rounded-lg flex items-center justify-center mb-6">
                        <i class="fas fa-users text-2xl text-red-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Workspace Management</h3>
                    <p class="text-gray-400">Create and manage workspaces, invite team members, and assign roles with
                        granular permission controls.</p>
                </div>

                <!-- Feature 12: Git Integration -->
                <div class="feature-card rounded-xl p-6">
                    <div class="w-14 h-14 bg-indigo-500 bg-opacity-20 rounded-lg flex items-center justify-center mb-6">
                        <i class="fab fa-git-alt text-2xl text-indigo-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Git Integration</h3>
                    <p class="text-gray-400">Connect to GitHub, GitLab, or Bitbucket repositories, track commits, and
                        automate workflows with webhook support.</p>
                </div>

                <!-- Feature 13: HR Management -->
                <div class="feature-card rounded-xl p-6">
                    <div class="w-14 h-14 bg-blue-500 bg-opacity-20 rounded-lg flex items-center justify-center mb-6">
                        <i class="fas fa-id-card text-2xl text-blue-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">HR Management</h3>
                    <p class="text-gray-400">Manage employee records, track time off, handle onboarding processes, and
                        maintain organizational structure.</p>
                </div>

                <!-- Feature 14: Video Conferencing -->
                <div class="feature-card rounded-xl p-6">
                    <div class="w-14 h-14 bg-purple-500 bg-opacity-20 rounded-lg flex items-center justify-center mb-6">
                        <i class="fas fa-video text-2xl text-purple-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Video Conferencing</h3>
                    <p class="text-gray-400">Conduct virtual meetings with integrated video calls, screen sharing, and
                        recording capabilities.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Workflow Section -->
    <section id="workflow" class="py-20">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-16">
                How <span class="gradient-text">Fokus</span> Works
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Step 1 -->
                <div class="text-center">
                    <div
                        class="w-20 h-20 bg-blue-500 bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="text-2xl font-bold text-blue-400">1</span>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Create Workspace</h3>
                    <p class="text-gray-400">Set up your team workspace and invite members to collaborate.</p>
                </div>

                <!-- Step 2 -->
                <div class="text-center">
                    <div
                        class="w-20 h-20 bg-blue-500 bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="text-2xl font-bold text-blue-400">2</span>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Plan Projects</h3>
                    <p class="text-gray-400">Create projects, define workflows, and set up sprints for your team.</p>
                </div>

                <!-- Step 3 -->
                <div class="text-center">
                    <div
                        class="w-20 h-20 bg-blue-500 bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="text-2xl font-bold text-blue-400">3</span>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Execute & Improve</h3>
                    <p class="text-gray-400">Track progress, conduct meetings, and continuously improve with
                        retrospectives.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="py-20 bg-slate-900">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-16">
                Trusted by <span class="gradient-text">Teams</span> Worldwide
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Testimonial 1 -->
                <div class="feature-card rounded-xl p-6">
                    <div class="flex items-center mb-4">
                        <div
                            class="w-12 h-12 bg-blue-500 bg-opacity-20 rounded-full flex items-center justify-center mr-4">
                            <span class="text-xl font-bold text-blue-400">A</span>
                        </div>
                        <div>
                            <h4 class="font-semibold">Alex Johnson</h4>
                            <p class="text-sm text-gray-400">Product Manager</p>
                        </div>
                    </div>
                    <p class="text-gray-300">"Fokus has transformed how our team works. We've eliminated the need for
                        multiple tools and streamlined our entire workflow."</p>
                </div>

                <!-- Testimonial 2 -->
                <div class="feature-card rounded-xl p-6">
                    <div class="flex items-center mb-4">
                        <div
                            class="w-12 h-12 bg-purple-500 bg-opacity-20 rounded-full flex items-center justify-center mr-4">
                            <span class="text-xl font-bold text-purple-400">S</span>
                        </div>
                        <div>
                            <h4 class="font-semibold">Sarah Miller</h4>
                            <p class="text-sm text-gray-400">Scrum Master</p>
                        </div>
                    </div>
                    <p class="text-gray-300">"The integrated sprint planning and retrospective features have made our
                        agile process much more effective and transparent."</p>
                </div>

                <!-- Testimonial 3 -->
                <div class="feature-card rounded-xl p-6">
                    <div class="flex items-center mb-4">
                        <div
                            class="w-12 h-12 bg-green-500 bg-opacity-20 rounded-full flex items-center justify-center mr-4">
                            <span class="text-xl font-bold text-green-400">M</span>
                        </div>
                        <div>
                            <h4 class="font-semibold">Michael Chen</h4>
                            <p class="text-sm text-gray-400">Tech Lead</p>
                        </div>
                    </div>
                    <p class="text-gray-300">"The password vault feature alone has saved us countless hours of searching
                        for credentials and improved our security posture."</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl md:text-4xl font-bold mb-6">
                Ready to <span class="gradient-text">Transform</span> Your Team's Workflow?
            </h2>
            <p class="text-xl text-gray-300 mb-8 max-w-3xl mx-auto">
                Join thousands of teams who have streamlined their work with Fokus. Get started for free today.
            </p>
            <a href="{{ route('register') }}"
               class="inline-block px-8 py-3 rounded-lg btn-primary font-medium text-center">
                Start Your Free Trial
            </a>
        </div>
    </section>
</main>

<footer class="bg-slate-900 py-12">
    <div class="container mx-auto px-4">
        <div class="flex flex-col md:flex-row justify-between items-center">
            <div class="flex items-center mb-6 md:mb-0">
                <i class="fas fa-cube text-2xl text-blue-400 mr-3"></i>
                <h2 class="text-xl font-bold gradient-text">Fokus</h2>
            </div>
            <div class="flex space-x-6">
                <a href="#" class="text-gray-400 hover:text-blue-400 transition">
                    <i class="fab fa-twitter text-xl"></i>
                </a>
                <a href="#" class="text-gray-400 hover:text-blue-400 transition">
                    <i class="fab fa-linkedin text-xl"></i>
                </a>
                <a href="#" class="text-gray-400 hover:text-blue-400 transition">
                    <i class="fab fa-github text-xl"></i>
                </a>
            </div>
        </div>
        <div class="border-t border-gray-800 mt-8 pt-8 flex flex-col md:flex-row justify-between">
            <p class="text-gray-500 text-center md:text-left mb-4 md:mb-0">
                &copy; {{ date('Y') }} Fokus. All rights reserved.
            </p>
            <div class="flex flex-wrap justify-center md:justify-end space-x-6">
                <a href="#" class="text-gray-500 hover:text-gray-300 transition mb-2">Privacy Policy</a>
                <a href="#" class="text-gray-500 hover:text-gray-300 transition mb-2">Terms of Service</a>
                <a href="#" class="text-gray-500 hover:text-gray-300 transition mb-2">Contact Us</a>
            </div>
        </div>
    </div>
</footer>
</body>
</html>
