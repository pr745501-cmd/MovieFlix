<?php
/**
 * download_ticket.php
 * Renders a beautiful, printable movie ticket.
 * Uses html2canvas + jsPDF to allow one-click image/PDF download.
 */

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit();
}

include '../config.php';

if (!isset($_GET['booking_id'])) {
    header('Location: index.php');
    exit();
}

$booking_id = (int)$_GET['booking_id'];

// Only allow the booking owner to view
$user_id = (int)$_SESSION['user_id'];
$stmt = $conn->prepare(
    "SELECT b.*, m.title, m.genre, m.director, m.release_year, m.image
     FROM bookings b
     JOIN movies m ON b.movie_id = m.id
     WHERE b.id = ? AND b.user_id = ?"
);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result  = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    header('Location: index.php');
    exit();
}

$seats_array  = array_map('trim', explode(',', $booking['seat_numbers']));
$base         = $booking['seats'] * 299;
$gst          = (int)round($base * 0.18);
$ref          = '#' . str_pad($booking['id'], 6, '0', STR_PAD_LEFT);
$booked_on    = date('d M Y, h:i A', strtotime($booking['created_at']));
$show_date_f  = date('D, d M Y', strtotime($booking['show_date']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Ticket <?= $ref ?> – MovieFlix</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        /* ─── Reset & Base ──────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --green:   #059669;
            --green-d: #047857;
            --green-l: #d1fae5;
            --gray-1:  #111827;
            --gray-4:  #6b7280;
            --gray-6:  #e5e7eb;
            --bg:      #f3f4f6;
        }
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 50%, #a5d6a7 100%);
            min-height: 100vh;
            padding: 28px 16px 48px;
        }

        /* ─── Action Bar ─────────────────────────────────── */
        .action-bar {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 28px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 22px;
            border-radius: 10px;
            font-size: .95rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all .25s;
            font-family: 'Outfit', sans-serif;
            text-decoration: none;
        }
        .btn-green {
            background: var(--green);
            color: #fff;
            box-shadow: 0 4px 14px rgba(5,150,105,.35);
        }
        .btn-green:hover { background: var(--green-d); transform: translateY(-2px); }
        .btn-white {
            background: #fff;
            color: var(--gray-1);
            border: 2px solid var(--gray-6);
        }
        .btn-white:hover { border-color: var(--green); color: var(--green); }
        .btn:disabled { opacity: .6; cursor: not-allowed; transform: none !important; }

        /* ─── Ticket Wrapper (max-width for readability) ─── */
        .ticket-wrap { max-width: 820px; margin: 0 auto; }

        /* ─── Ticket Card ────────────────────────────────── */
        .ticket {
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 24px 60px rgba(0,0,0,.13);
        }

        /* ─── Header ─────────────────────────────────────── */
        .ticket-header {
            background: linear-gradient(135deg, var(--green) 0%, var(--green-d) 100%);
            padding: 34px 36px 28px;
            text-align: center;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .ticket-header::before {
            content: '';
            position: absolute;
            top: -40px; right: -40px;
            width: 160px; height: 160px;
            background: rgba(255,255,255,.07);
            border-radius: 50%;
        }
        .ticket-header::after {
            content: '';
            position: absolute;
            bottom: -50px; left: -50px;
            width: 200px; height: 200px;
            background: rgba(255,255,255,.05);
            border-radius: 50%;
        }
        .ticket-brand {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: 2px;
            position: relative;
            z-index: 1;
        }
        .ticket-tagline {
            font-size: .9rem;
            opacity: .88;
            margin-top: 4px;
            position: relative;
            z-index: 1;
        }

        /* ─── Tear Line ──────────────────────────────────── */
        .tear-line {
            position: relative;
            height: 0;
            border-top: 2px dashed var(--gray-6);
            margin: 0 28px;
        }
        .tear-line::before, .tear-line::after {
            content: '';
            position: absolute;
            top: -16px;
            width: 30px; height: 30px;
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            border-radius: 50%;
        }
        .tear-line::before { left: -42px; }
        .tear-line::after  { right: -42px; }

        /* ─── Body ───────────────────────────────────────── */
        .ticket-body { padding: 32px 36px; }

        /* Movie Row */
        .movie-row {
            display: grid;
            grid-template-columns: 160px 1fr;
            gap: 24px;
            margin-bottom: 28px;
        }
        .movie-poster {
            width: 100%;
            height: 230px;
            object-fit: contain;
            border-radius: 12px;
            background: #f3f4f6;
            border: 2px solid var(--gray-6);
            box-shadow: 0 6px 20px rgba(0,0,0,.1);
        }
        .movie-info { display: flex; flex-direction: column; gap: 8px; justify-content: center; }
        .movie-title { font-size: 1.8rem; font-weight: 800; color: var(--gray-1); line-height: 1.25; }
        .movie-pill {
            display: inline-block;
            background: var(--green-l);
            color: var(--green);
            font-size: .78rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .meta-line { font-size: .9rem; color: var(--gray-4); }
        .meta-line span { font-weight: 600; color: var(--green); margin-right: 4px; }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 24px;
        }
        .info-card {
            background: linear-gradient(135deg, #f0fdf4, #f9fafb);
            border: 2px solid var(--green-l);
            border-radius: 12px;
            padding: 16px 12px;
            text-align: center;
        }
        .info-label {
            font-size: .72rem;
            font-weight: 700;
            color: var(--green);
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 6px;
        }
        .info-value { font-size: 1rem; font-weight: 700; color: var(--gray-1); }

        /* Dashed divider */
        .dash-line {
            border: none;
            border-top: 2px dashed var(--gray-6);
            margin: 22px 0;
        }

        /* Seats + Price */
        .bottom-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }
        .seats-box, .price-box {
            border-radius: 12px;
            padding: 18px;
        }
        .seats-box {
            background: rgba(5,150,105,.05);
            border: 2px solid var(--green-l);
        }
        .price-box {
            background: #fafafa;
            border: 2px solid var(--gray-6);
        }
        .box-title {
            font-size: .8rem;
            font-weight: 700;
            color: var(--green);
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 12px;
        }
        .seats-list { display: flex; flex-wrap: wrap; gap: 8px; }
        .seat-chip {
            background: #fff;
            border: 2px solid var(--green);
            color: var(--green);
            padding: 5px 12px;
            border-radius: 8px;
            font-weight: 700;
            font-size: .88rem;
        }
        .price-row {
            display: flex;
            justify-content: space-between;
            font-size: .9rem;
            margin-bottom: 9px;
        }
        .price-row .lbl { color: var(--gray-4); }
        .price-row .val { font-weight: 600; color: var(--gray-1); }
        .price-row.total {
            border-top: 2px solid var(--gray-6);
            padding-top: 9px;
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--green);
            margin-bottom: 0;
        }
        .price-row.total .val { color: var(--green); }

        /* Booking Reference */
        .ref-box {
            text-align: center;
            padding: 20px;
            background: #f9fafb;
            border: 2px dashed #d1d5db;
            border-radius: 12px;
        }
        .ref-label { font-size: .8rem; color: var(--gray-4); margin-bottom: 6px; }
        .ref-code {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--green);
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
        }
        .ref-date { font-size: .78rem; color: #9ca3af; margin-top: 6px; }

        /* ─── Footer ─────────────────────────────────────── */
        .ticket-footer {
            background: #f3f4f6;
            border-top: 2px solid var(--gray-6);
            padding: 16px 36px;
            text-align: center;
            font-size: .88rem;
            color: var(--gray-4);
        }
        .ticket-footer strong { color: var(--green); }

        /* ─── Print ──────────────────────────────────────── */
        @media print {
            body { background: #fff; padding: 0; }
            .action-bar { display: none !important; }
            .ticket { box-shadow: none; }
            .tear-line::before, .tear-line::after { background: #fff; }
        }

        /* ─── Responsive ─────────────────────────────────── */
        @media (max-width: 640px) {
            .ticket-body { padding: 20px 18px; }
            .movie-row { grid-template-columns: 110px 1fr; gap: 14px; }
            .movie-poster { height: 165px; }
            .movie-title { font-size: 1.3rem; }
            .info-grid { grid-template-columns: repeat(2, 1fr); }
            .bottom-grid { grid-template-columns: 1fr; }
            .ticket-header { padding: 24px 18px; }
            .ticket-brand { font-size: 1.5rem; }
            .tear-line { margin: 0 14px; }
        }
    </style>
</head>
<body>

<div class="ticket-wrap">

    <!-- Action Buttons -->
    <div class="action-bar">
        <button class="btn btn-green" id="dlBtn" onclick="downloadTicket()">
            ⬇️ Download Ticket
        </button>
        <button class="btn btn-white" onclick="window.print()">🖨️ Print</button>
        <a href="index.php" class="btn btn-white">← Back to Movies</a>
    </div>

    <!-- THE TICKET -->
    <div class="ticket" id="ticketCard">

        <!-- Header -->
        <div class="ticket-header">
            <div class="ticket-brand">🎬 MOVIEFLIX</div>
            <div class="ticket-tagline">Your Premium Cinema Experience</div>
        </div>

        <!-- Tear Line -->
        <div class="tear-line"></div>

        <!-- Body -->
        <div class="ticket-body">

            <!-- Movie Info -->
            <div class="movie-row">
                <img src="../uploads/<?= htmlspecialchars($booking['image']) ?>"
                     alt="Poster" class="movie-poster"/>
                <div class="movie-info">
                    <div class="movie-pill"><?= htmlspecialchars($booking['genre']) ?></div>
                    <div class="movie-title"><?= htmlspecialchars($booking['title']) ?></div>
                    <div class="meta-line"><span>Director:</span><?= htmlspecialchars($booking['director']) ?></div>
                    <div class="meta-line"><span>Year:</span><?= $booking['release_year'] ?></div>
                    <div class="meta-line"><span>Booked by:</span><?= htmlspecialchars($booking['name']) ?></div>
                </div>
            </div>

            <hr class="dash-line"/>

            <!-- Show Info Cards -->
            <div class="info-grid">
                <div class="info-card">
                    <div class="info-label">Screen</div>
                    <div class="info-value"><?= htmlspecialchars($booking['screen']) ?></div>
                </div>
                <div class="info-card">
                    <div class="info-label">Date</div>
                    <div class="info-value"><?= $show_date_f ?></div>
                </div>
                <div class="info-card">
                    <div class="info-label">Time</div>
                    <div class="info-value"><?= htmlspecialchars($booking['show_time']) ?></div>
                </div>
                <div class="info-card">
                    <div class="info-label">Seats</div>
                    <div class="info-value"><?= $booking['seats'] ?> Ticket<?= $booking['seats'] > 1 ? 's' : '' ?></div>
                </div>
            </div>

            <hr class="dash-line"/>

            <!-- Seats + Price -->
            <div class="bottom-grid">
                <div class="seats-box">
                    <div class="box-title">✓ Booked Seats</div>
                    <div class="seats-list">
                        <?php foreach ($seats_array as $seat): ?>
                            <div class="seat-chip"><?= htmlspecialchars($seat) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="price-box">
                    <div class="box-title">💰 Price Breakdown</div>
                    <div class="price-row">
                        <span class="lbl"><?= $booking['seats'] ?> × ₹299</span>
                        <span class="val">₹<?= $base ?></span>
                    </div>
                    <div class="price-row">
                        <span class="lbl">GST (18%)</span>
                        <span class="val">₹<?= $gst ?></span>
                    </div>
                    <div class="price-row total">
                        <span class="lbl">Total Paid</span>
                        <span class="val">₹<?= $booking['amount'] ?></span>
                    </div>
                </div>
            </div>

            <hr class="dash-line"/>

            <!-- Reference -->
            <div class="ref-box">
                <div class="ref-label">Booking Reference</div>
                <div class="ref-code"><?= $ref ?></div>
                <div class="ref-date">Booked on <?= $booked_on ?></div>
            </div>

        </div><!-- /ticket-body -->

        <!-- Footer -->
        <div class="ticket-footer">
            🍿 Please arrive <strong>15 minutes</strong> before show time &nbsp;·&nbsp;
            Enjoy your movie experience at <strong>MovieFlix!</strong>
        </div>

    </div><!-- /ticket -->
</div><!-- /ticket-wrap -->

<script>
async function downloadTicket() {
    const btn = document.getElementById('dlBtn');
    btn.textContent = '⏳ Generating…';
    btn.disabled = true;

    try {
        const canvas = await html2canvas(document.getElementById('ticketCard'), {
            scale: 2,
            useCORS: true,
            backgroundColor: '#ffffff'
        });

        const link = document.createElement('a');
        link.download = 'MovieFlix-Ticket-<?= $booking['id'] ?>.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    } catch (e) {
        alert('Download failed. Please use the Print button instead.');
        console.error(e);
    } finally {
        btn.textContent = '⬇️ Download Ticket';
        btn.disabled = false;
    }
}
</script>

</body>
</html>
