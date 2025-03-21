<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Welcome to Titan Framework' ?></title>
    <style>
        :root {
            --primary-color: #6c63ff;
            --secondary-color: #4e46e5;
            --accent-color: #f9a826;
            --text-color: #333;
            --light-color: #f5f5f5;
            --dark-color: #333;
            --success-color: #48c774;
            --warning-color: #ffdd57;
            --danger-color: #f14668;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--light-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            text-decoration: none;
            color: white;
            display: flex;
            align-items: center;
        }

        .logo span {
            margin-left: 0.5rem;
        }

        nav ul {
            list-style: none;
            display: flex;
        }

        nav ul li {
            margin-left: 1.5rem;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        nav ul li a:hover {
            color: var(--accent-color);
        }

        main {
            flex: 1;
            padding: 3rem 0;
        }

        .hero {
            text-align: center;
            margin-bottom: 4rem;
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        .hero p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            color: #666;
        }

        .btn {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: var(--secondary-color);
        }

        .btn-secondary {
            background-color: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            margin-left: 1rem;
        }

        .btn-secondary:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 4rem;
        }

        .feature-card {
            background-color: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .feature-card h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        .feature-card p {
            color: #666;
        }

        .cta {
            text-align: center;
            background-color: var(--primary-color);
            color: white;
            padding: 4rem 0;
            border-radius: 8px;
            margin-bottom: 4rem;
        }

        .cta h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .cta p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        footer {
            background-color: var(--dark-color);
            color: white;
            padding: 2rem 0;
            margin-top: auto;
        }

        footer .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-links ul {
            list-style: none;
            display: flex;
        }

        .footer-links ul li {
            margin-left: 1.5rem;
        }

        .footer-links ul li a {
            color: white;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-links ul li a:hover {
            color: var(--accent-color);
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }

            .hero p {
                font-size: 1rem;
            }

            .features {
                grid-template-columns: 1fr;
            }

            footer .container {
                flex-direction: column;
                text-align: center;
            }

            .footer-links ul {
                margin-top: 1rem;
                justify-content: center;
            }

            .footer-links ul li {
                margin: 0 0.75rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <a href="/" class="logo">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                </svg>
                <span>Titan</span>
            </a>
            <nav>
                <ul>
                    <li><a href="/">Home</a></li>
                    <li><a href="/docs">Documentation</a></li>
                    <li><a href="/examples">Examples</a></li>
                    <li><a href="/community">Community</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <section class="hero">
                <h1><?= $title ?? 'Welcome to Titan Framework' ?></h1>
                <p><?= $description ?? 'A powerful, secure, and developer-friendly PHP framework that goes beyond Laravel.' ?></p>
                <div>
                    <a href="/docs/getting-started" class="btn">Get Started</a>
                    <a href="/docs" class="btn btn-secondary">Documentation</a>
                </div>
            </section>

            <section class="features">
                <div class="feature-card">
                    <h2>Ultra-fast Performance</h2>
                    <p>Optimized core architecture with minimal overhead for lightning-fast response times.</p>
                </div>
                <div class="feature-card">
                    <h2>Advanced Dependency Injection</h2>
                    <p>Powerful container with automatic resolution and contextual binding capabilities.</p>
                </div>
                <div class="feature-card">
                    <h2>Enhanced Security</h2>
                    <p>Built with security-first principles to protect your application from common vulnerabilities.</p>
                </div>
                <div class="feature-card">
                    <h2>Elegant Routing</h2>
                    <p>Intuitive and flexible router for handling HTTP requests and responses with ease.</p>
                </div>
                <div class="feature-card">
                    <h2>Modern Architecture</h2>
                    <p>Follows PHP 8.2+ features and best practices for maintainable, future-proof code.</p>
                </div>
                <div class="feature-card">
                    <h2>Comprehensive ORM</h2>
                    <p>Simple yet powerful database abstraction that makes working with databases a breeze.</p>
                </div>
            </section>

            <section class="cta">
                <h2>Ready to Build Something Amazing?</h2>
                <p>Start creating powerful applications with Titan Framework today. It's powerful, secure, and developer-friendly.</p>
                <a href="/docs/getting-started" class="btn">Get Started Now</a>
            </section>
        </div>
    </main>

    <footer>
        <div class="container">
            <div class="copyright">
                &copy; <?= date('Y') ?> Titan Framework. All rights reserved.
            </div>
            <div class="footer-links">
                <ul>
                    <li><a href="/docs">Documentation</a></li>
                    <li><a href="/community">Community</a></li>
                    <li><a href="/github">GitHub</a></li>
                    <li><a href="/about">About</a></li>
                </ul>
            </div>
        </div>
    </footer>
</body>
</html>
