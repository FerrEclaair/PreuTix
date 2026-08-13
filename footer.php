<?php
// Ensure session is started for consistency with header.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<link rel="stylesheet" href="assets/css/theme.css">
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Inter', sans-serif;
    }

    footer {
        background: var(--pt-gradient-header, linear-gradient(135deg, #000000, #1E40AF));
        color: #fff;
        padding: 44px 20px 28px;
        margin-top: auto; /* Ensures footer stays at the bottom if content is short */
        position: relative;
    }

    footer::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: linear-gradient(90deg, #FF0000, #0000FF);
    }

    .footer-container {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        padding-bottom: 24px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.12);
    }

    .footer-logo {
        font-weight: 800;
        font-size: 24px;
    }

    .footer-logo .preu {
        color: #FF0000;
    }

    .footer-logo .tix {
        color: #6c9bff;
    }

    .footer-nav ul {
        display: flex;
        list-style: none;
        gap: 30px;
    }

    .footer-nav ul li a {
        text-decoration: none;
        color: rgba(255, 255, 255, 0.85);
        font-weight: 500;
        font-size: 15px;
        transition: color 0.25s ease;
    }

    .footer-nav ul li a:hover {
        color: #FF6B6B;
    }

    .footer-bottom {
        text-align: center;
        margin-top: 20px;
        font-size: 13.5px;
        color: rgba(255, 255, 255, 0.55);
    }

    /* Media Queries */
    @media (max-width: 768px) {
        .footer-container {
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .footer-nav ul {
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .footer-logo {
            font-size: 20px;
        }
    }
</style>
<footer>
    <div class="footer-container">
        <div class="footer-logo">
            <span class="preu">Preu</span><span class="tix">Tix</span>
        </div>
        <nav class="footer-nav">
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="event.php">Events</a></li>
                <li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="history.php">History</a>
                    <?php else: ?>
                        <a href="#" onclick="alert('You must register and login to your account.'); return false;">History</a>
                    <?php endif; ?>
                </li>
            </ul>
        </nav>
    </div>
    <div class="footer-bottom">
        &copy; <?php echo date('Y'); ?> PreuTix. All rights reserved.
    </div>
</footer>