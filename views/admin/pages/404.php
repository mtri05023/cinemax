<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>EzyTicket - 404 Not Found</title>
    <meta name="description" content="Page not found - EzyTicket movie booking">

    <!-- Icons -->
    <link rel="icon" href="/icon-light-32x32.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="/icon-dark-32x32.png" media="(prefers-color-scheme: dark)">
    <link rel="icon" href="/icon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-icon.png">

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />

    <!-- CSS -->
    <link rel="stylesheet" href="../../public/assets/css/404.css">
</head>

<body>

    <!-- WRAPPER RIÊNG CHO 404 -->
    <div class="err404">

        <div class="err404-card">

            <main class="err404-main">

                <!-- TICKET SVG  -->
                <div class="err404-ticket">
                    <svg width="380" height="260" viewBox="0 0 380 260">
                        <defs>
                            <filter id="ticketShadow" x="-20%" y="-20%" width="140%" height="140%">
                                <feDropShadow dx="3" dy="6" stdDeviation="4" flood-color="#1e3a5f" flood-opacity="0.3" />
                            </filter>
                            <linearGradient id="ticketGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="#60a5fa" />
                                <stop offset="100%" stop-color="#38bdf8" />
                            </linearGradient>

                        </defs>

                        <g filter="url(#ticketShadow)" transform="rotate(-8,100,130)">
                            <path d="M15 50 L15 40 Q15 30 25 30 L140 30 L140 45 Q130 50 130 65 Q130 80 140 85 L140 175 Q130 180 130 195 Q130 210 140 215 L140 230 L25 230 Q15 230 15 220 L15 210 Q25 205 25 190 Q25 175 15 170 L15 130 Q25 125 25 110 Q25 95 15 90 L15 50 Z" fill="url(#ticketGradient)" />
                            <text x="55" y="165" fill="#1e3a5f" font-size="72" font-weight="900">4</text>
                        </g>

                        <g filter="url(#ticketShadow)" transform="rotate(5,280,130)">
                            <path d="M240 30 L355 30 Q365 30 365 40 L365 50 Q355 55 355 70 Q355 85 365 90 L365 130 Q355 135 355 150 Q355 165 365 170 L365 210 Q355 215 355 220 L365 220 Q365 230 355 230 L240 230 L240 215 Q250 210 250 195 Q250 180 240 175 L240 85 Q250 80 250 65 Q250 50 240 45 L240 30 Z" fill="url(#ticketGradient)" />
                            <text x="275" y="165" fill="#1e3a5f" font-size="72" font-weight="900">4</text>
                        </g>

                        <g>
                            <path
                                d="M188 25 L195 50 L182 75 L198 100 L185 130 L200 160 L187 190 L195 220 L190 245"
                                stroke="#020617"
                                strokeWidth="8"
                                fill="none"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                opacity="0.7" />
                            <path
                                d="M188 25 L195 50 L182 75 L198 100 L185 130 L200 160 L187 190 L195 220 L190 245"
                                stroke="#1e40af"
                                strokeWidth="5"
                                fill="none"
                                strokeLinecap="round"
                                strokeLinejoin="round" />
                            <path
                                d="M188 25 L195 50 L182 75 L198 100 L185 130 L200 160 L187 190 L195 220 L190 245"
                                stroke="#60a5fa"
                                stroke-width="2.5"
                                fill="none"
                                stroke-linecap="round"
                                stroke-linejoin="round" />

                            <text x="163" y="162" fill="#38bdf8" font-size="72" font-weight="900">0</text>
                        </g>
                    </svg>
                </div>

                <!-- TEXT -->
                <div class="err404-text">
                    <h1>OoPS!</h1>
                    <p class="subtitle">Non-existent or disabled content</p>
                    <p class="desc">
                        Don't worry, It Happens.<br>
                        Lets go back to Home Screen
                    </p>

                    <a href="index.php" class="err404-btn">Home</a>
                </div>

            </main>

            <!-- POPCORN -->
            <div class="err404-popcorn">
                <div class="pop left"></div>
                <div class="pop center"></div>
                <div class="pop right"></div>
            </div>

        </div>

    </div>

</body>

</html>