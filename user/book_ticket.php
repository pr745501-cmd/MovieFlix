<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit();
}

include '../config.php';

if (!isset($_GET['movie_id'])) {
    header('Location: index.php');
    exit();
}

$movie_id = (int)$_GET['movie_id'];
$stmt = $conn->prepare("SELECT * FROM movies WHERE id = ?");
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$result = $stmt->get_result();
$movie = $result->fetch_assoc();

if (!$movie) {
    header('Location: index.php');
    exit();
}

$user_id   = $_SESSION['user_id'];
$username  = $_SESSION['username'];

// Screens, times, price config
$screens   = ['Screen 1', 'Screen 2', 'Screen 3', 'Screen 4'];
$times     = ['10:00 AM', '1:00 PM', '4:00 PM', '7:00 PM', '10:00 PM'];
$base_price = 299; // ₹ per seat
$gst_rate   = 0.18;

// Seat layout: 10 rows (A–J), 10 cols
$rows = ['A','B','C','D','E','F','G','H','I','J'];
$cols = range(1, 10);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Book Ticket – <?= htmlspecialchars($movie['title']) ?> | MovieFlix</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="ticket.css"/>
</head>
<body class="bms-body">

<!-- ═══════════════════════════ NAVBAR ═══════════════════════════ -->
<header class="bms-navbar">
    <div class="bms-navbar-inner">
        <a href="index.php" class="bms-logo">🎬 MovieFlix</a>
        <div class="bms-nav-right">
            <span class="bms-welcome">👤 <?= htmlspecialchars($username) ?></span>
            <a href="../auth/logout.php" class="bms-logout-btn">Logout</a>
        </div>
    </div>
</header>

<!-- ═══════════════════════════ MAIN ═══════════════════════════ -->
<main class="bms-main">

    <!-- ── Movie Info Banner ── -->
    <section class="bms-movie-banner">
        <div class="bms-banner-inner">
            <div class="bms-poster-wrap">
                <img src="../uploads/<?= htmlspecialchars($movie['image']) ?>"
                     alt="<?= htmlspecialchars($movie['title']) ?>"
                     class="bms-poster"/>
            </div>
            <div class="bms-movie-meta">
                <span class="bms-genre-badge"><?= htmlspecialchars($movie['genre']) ?></span>
                <h1 class="bms-movie-title"><?= htmlspecialchars($movie['title']) ?></h1>
                <p class="bms-movie-sub">
                    Dir. <?= htmlspecialchars($movie['director']) ?> &nbsp;·&nbsp; <?= $movie['release_year'] ?>
                </p>
                <div class="bms-rating-row">
                    <span class="bms-star">★★★★☆</span>
                    <span class="bms-rating-text">4.1 / 5 &nbsp;·&nbsp; UA</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ── Booking Steps Container ── -->
    <div class="bms-container">

        <!-- ─────────── STEP 1: Select Screen / Date / Time ─────────── -->
        <div class="bms-card bms-step-card" id="step1">
            <div class="bms-step-header">
                <span class="bms-step-badge">1</span>
                <h2 class="bms-step-title">Select Show</h2>
            </div>

            <div class="bms-options-grid">

                <!-- Screen -->
                <div class="bms-option-group">
                    <label class="bms-option-label">🎭 Screen</label>
                    <div class="bms-chip-row" id="screenChips">
                        <?php foreach ($screens as $i => $scr): ?>
                            <button type="button"
                                    class="bms-chip <?= $i === 0 ? 'bms-chip-active' : '' ?>"
                                    data-value="<?= htmlspecialchars($scr) ?>"
                                    data-group="screen"
                                    onclick="selectChip(this,'screen')">
                                <?= htmlspecialchars($scr) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Date -->
                <div class="bms-option-group">
                    <label class="bms-option-label">📅 Date</label>
                    <div class="bms-chip-row" id="dateChips">
                        <?php
                        for ($d = 0; $d < 14; $d++) {
                            $ts    = strtotime("+$d days");
                            $val   = date('Y-m-d', $ts);
                            $label = $d === 0 ? 'Today' : ($d === 1 ? 'Tomorrow' : date('D, d M', $ts));
                            $active = $d === 0 ? 'bms-chip-active' : '';
                            echo "<button type='button' class='bms-chip $active' data-value='$val' data-group='date' onclick='selectChip(this,\"date\")'>"
                                 . htmlspecialchars($label) . "</button>";
                        }
                        ?>
                    </div>
                </div>

                <!-- Time -->
                <div class="bms-option-group">
                    <label class="bms-option-label">🕐 Show Time</label>
                    <div class="bms-chip-row" id="timeChips">
                        <?php foreach ($times as $i => $t): ?>
                            <button type="button"
                                    class="bms-chip <?= $i === 0 ? 'bms-chip-active' : '' ?>"
                                    data-value="<?= htmlspecialchars($t) ?>"
                                    data-group="time"
                                    onclick="selectChip(this,'time')">
                                <?= htmlspecialchars($t) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div><!-- /options-grid -->
        </div><!-- /step1 -->

        <!-- ─────────── STEP 2: Seat Layout ─────────── -->
        <div class="bms-card bms-step-card" id="step2">
            <div class="bms-step-header">
                <span class="bms-step-badge">2</span>
                <h2 class="bms-step-title">Choose Your Seats</h2>
            </div>

            <!-- Screen Visual -->
            <div class="bms-screen-wrap">
                <div class="bms-screen-curve"></div>
                <div class="bms-screen-label">SCREEN THIS WAY</div>
            </div>

            <!-- Legend -->
            <div class="bms-legend">
                <span class="bms-legend-item"><span class="bms-dot bms-dot-available"></span> Available</span>
                <span class="bms-legend-item"><span class="bms-dot bms-dot-selected"></span> Selected</span>
                <span class="bms-legend-item"><span class="bms-dot bms-dot-booked"></span> Booked</span>
            </div>

            <!-- Loading Overlay -->
            <div class="bms-seats-loading" id="seatsLoading">
                <div class="bms-spinner"></div>
                <p>Loading seats…</p>
            </div>

            <!-- Seat Grid -->
            <div class="bms-seat-layout" id="seatLayout">
                <?php foreach ($rows as $row): ?>
                    <div class="bms-seat-row">
                        <span class="bms-row-label"><?= $row ?></span>
                        <div class="bms-seat-cells">
                            <?php foreach ($cols as $col): ?>
                                <?php $seatId = $row . $col; ?>
                                <button type="button"
                                        class="bms-seat bms-seat-available"
                                        id="seat-<?= $seatId ?>"
                                        data-seat="<?= $seatId ?>"
                                        onclick="toggleSeat(this)"
                                        title="Seat <?= $seatId ?>">
                                    <span class="bms-seat-num"><?= $col ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div><!-- /seat-layout -->
        </div><!-- /step2 -->

        <!-- ─────────── STEP 3: Your Details & Summary ─────────── -->
        <div class="bms-card bms-step-card" id="step3">
            <div class="bms-step-header">
                <span class="bms-step-badge">3</span>
                <h2 class="bms-step-title">Confirm Booking</h2>
            </div>

            <div class="bms-confirm-grid">

                <!-- Left: User Info Form -->
                <div class="bms-confirm-left">
                    <h3 class="bms-section-subtitle">Contact Details</h3>
                    <div class="bms-form-group">
                        <label class="bms-form-label">Full Name *</label>
                        <input type="text" id="bookingName"
                               placeholder="Your full name"
                               class="bms-input"
                               value="<?= htmlspecialchars($username) ?>"/>
                    </div>
                    <div class="bms-form-group">
                        <label class="bms-form-label">Mobile Number *</label>
                        <input type="tel" id="bookingPhone"
                               placeholder="10-digit mobile number"
                               class="bms-input"
                               maxlength="10"/>
                    </div>
                    <div class="bms-form-group">
                        <label class="bms-form-label">Email Address *</label>
                        <input type="email" id="bookingEmail"
                               placeholder="your@email.com"
                               class="bms-input"/>
                    </div>
                </div>

                <!-- Right: Summary -->
                <div class="bms-confirm-right">
                    <h3 class="bms-section-subtitle">Booking Summary</h3>
                    <div class="bms-summary-box" id="summaryBox">

                        <div class="bms-summary-movie">
                            <img src="../uploads/<?= htmlspecialchars($movie['image']) ?>"
                                 class="bms-summary-poster"
                                 alt="poster"/>
                            <div>
                                <div class="bms-summary-title"><?= htmlspecialchars($movie['title']) ?></div>
                                <div class="bms-summary-meta" id="summaryShow">Select screen, date &amp; time above</div>
                            </div>
                        </div>

                        <div class="bms-summary-divider"></div>

                        <!-- Selected Seats Display -->
                        <div class="bms-summary-row">
                            <span class="bms-summary-label">Seats</span>
                            <span class="bms-summary-val" id="summarySeats">None selected</span>
                        </div>
                        <div class="bms-summary-row">
                            <span class="bms-summary-label">Tickets</span>
                            <span class="bms-summary-val" id="summaryCount">0</span>
                        </div>

                        <div class="bms-summary-divider"></div>

                        <!-- Price Breakdown -->
                        <div class="bms-summary-row">
                            <span class="bms-summary-label">Base Price</span>
                            <span class="bms-summary-val" id="summaryBase">₹0</span>
                        </div>
                        <div class="bms-summary-row">
                            <span class="bms-summary-label">GST (18%)</span>
                            <span class="bms-summary-val" id="summaryGst">₹0</span>
                        </div>
                        <div class="bms-summary-row bms-summary-total">
                            <span>Total Payable</span>
                            <span id="summaryTotal">₹0</span>
                        </div>

                        <!-- Seat Badges -->
                        <div class="bms-seat-badges" id="seatBadges"></div>
                    </div><!-- /summary-box -->
                </div>

            </div><!-- /confirm-grid -->

            <!-- Proceed Button -->
            <div class="bms-proceed-wrap">
                <button type="button"
                        id="proceedBtn"
                        class="bms-proceed-btn bms-proceed-disabled"
                        onclick="proceedToPayment()">
                    🎟️ Proceed to Payment
                </button>
                <p class="bms-proceed-hint" id="proceedHint">Please select at least 1 seat to continue</p>
            </div>
        </div><!-- /step3 -->

    </div><!-- /container -->
</main>

<!-- ═══════════════════════════ SUCCESS POPUP ═══════════════════════════ -->
<div class="bms-overlay" id="popupOverlay">
    <div class="bms-popup" id="successPopup">
        <div class="bms-popup-icon">🎉</div>
        <h2 class="bms-popup-title">Booking Confirmed!</h2>
        <p class="bms-popup-sub" id="popupSub">Your seats have been reserved.</p>
        <div class="bms-popup-ref" id="popupRef">#000000</div>
        <div class="bms-popup-actions">
            <a id="downloadLink" href="#" class="bms-popup-btn bms-popup-btn-primary">⬇ Download Ticket</a>
            <a href="index.php" class="bms-popup-btn bms-popup-btn-secondary">← Back to Movies</a>
        </div>
    </div>
</div>

<!-- ═══════════════════════════ HIDDEN FORM (submitted via JS) ═══════════════════════════ -->
<form id="bookingForm" method="POST" action="process_payment.php" style="display:none;">
    <input type="hidden" name="movie_id"     value="<?= $movie_id ?>"/>
    <input type="hidden" name="name"         id="fName"/>
    <input type="hidden" name="phone"        id="fPhone"/>
    <input type="hidden" name="email"        id="fEmail"/>
    <input type="hidden" name="screen"       id="fScreen"/>
    <input type="hidden" name="show_date"    id="fDate"/>
    <input type="hidden" name="show_time"    id="fTime"/>
    <input type="hidden" name="seat_numbers" id="fSeats"/>
    <input type="hidden" name="seats"        id="fSeatCount"/>
    <input type="hidden" name="amount"       id="fAmount"/>
</form>

<!-- ═══════════════════════════ SCRIPT ═══════════════════════════ -->
<script>
// ── State ──
const BASE_PRICE  = <?= $base_price ?>;
const GST_RATE    = <?= $gst_rate ?>;
const MOVIE_ID    = <?= $movie_id ?>;

let selectedScreen = document.querySelector('[data-group="screen"].bms-chip-active')?.dataset.value || '';
let selectedDate   = document.querySelector('[data-group="date"].bms-chip-active')?.dataset.value   || '';
let selectedTime   = document.querySelector('[data-group="time"].bms-chip-active')?.dataset.value   || '';
let selectedSeats  = []; // array of seat IDs e.g. ['A1','B3']
let bookedSeats    = []; // from server

// ── Chip Selection ──
function selectChip(el, group) {
    document.querySelectorAll(`[data-group="${group}"]`).forEach(c => c.classList.remove('bms-chip-active'));
    el.classList.add('bms-chip-active');

    if (group === 'screen') selectedScreen = el.dataset.value;
    if (group === 'date')   selectedDate   = el.dataset.value;
    if (group === 'time')   selectedTime   = el.dataset.value;

    updateSummaryShow();
    refreshSeats();
}

function updateSummaryShow() {
    const el = document.getElementById('summaryShow');
    if (selectedScreen && selectedDate && selectedTime) {
        const d = new Date(selectedDate);
        const label = d.toLocaleDateString('en-IN', {weekday:'short', day:'numeric', month:'short'});
        el.textContent = `${selectedScreen}  ·  ${label}  ·  ${selectedTime}`;
    }
}

// ── Fetch Booked Seats via AJAX ──
function refreshSeats() {
    if (!selectedScreen || !selectedDate || !selectedTime) return;

    document.getElementById('seatsLoading').style.display = 'flex';
    document.getElementById('seatLayout').style.opacity  = '0.3';

    const params = new URLSearchParams({
        movie_id: MOVIE_ID,
        screen:   selectedScreen,
        date:     selectedDate,
        time:     selectedTime
    });

    fetch(`get_booked_seats.php?${params}`)
        .then(r => r.json())
        .then(data => {
            bookedSeats = data.booked || [];
            renderSeats();
        })
        .catch(() => { renderSeats(); })
        .finally(() => {
            document.getElementById('seatsLoading').style.display = 'none';
            document.getElementById('seatLayout').style.opacity   = '1';
        });
}

// ── Render Seats ──
function renderSeats() {
    // Reset previously selected seats that may now be booked
    selectedSeats = selectedSeats.filter(s => !bookedSeats.includes(s));

    document.querySelectorAll('.bms-seat').forEach(btn => {
        const seat = btn.dataset.seat;
        btn.classList.remove('bms-seat-available','bms-seat-selected','bms-seat-booked');

        if (bookedSeats.includes(seat)) {
            btn.classList.add('bms-seat-booked');
            btn.disabled = true;
        } else if (selectedSeats.includes(seat)) {
            btn.classList.add('bms-seat-selected');
            btn.disabled = false;
        } else {
            btn.classList.add('bms-seat-available');
            btn.disabled = false;
        }
    });

    updateSummary();
}

// ── Toggle Seat ──
function toggleSeat(btn) {
    if (btn.disabled) return;
    const seat = btn.dataset.seat;

    if (selectedSeats.includes(seat)) {
        selectedSeats = selectedSeats.filter(s => s !== seat);
        btn.classList.remove('bms-seat-selected');
        btn.classList.add('bms-seat-available');
    } else {
        if (selectedSeats.length >= 10) {
            showToast('You can book max 10 seats at once.', 'warn');
            return;
        }
        selectedSeats.push(seat);
        btn.classList.remove('bms-seat-available');
        btn.classList.add('bms-seat-selected');
        // Animate
        btn.classList.add('bms-seat-bounce');
        setTimeout(() => btn.classList.remove('bms-seat-bounce'), 400);
    }

    updateSummary();
}

// ── Update Summary Panel ──
function updateSummary() {
    const count    = selectedSeats.length;
    const base     = count * BASE_PRICE;
    const gst      = Math.round(base * GST_RATE);
    const total    = base + gst;

    document.getElementById('summarySeats').textContent = count > 0 ? selectedSeats.join(', ') : 'None selected';
    document.getElementById('summaryCount').textContent = count;
    document.getElementById('summaryBase').textContent  = `₹${base}`;
    document.getElementById('summaryGst').textContent   = `₹${gst}`;
    document.getElementById('summaryTotal').textContent = `₹${total}`;

    // Seat badges
    const badgeBox = document.getElementById('seatBadges');
    badgeBox.innerHTML = selectedSeats.map(s =>
        `<span class="bms-badge-seat">${s}</span>`
    ).join('');

    // Proceed button state
    const btn  = document.getElementById('proceedBtn');
    const hint = document.getElementById('proceedHint');

    if (count > 0) {
        btn.classList.remove('bms-proceed-disabled');
        btn.classList.add('bms-proceed-active');
        hint.textContent = `${count} seat${count>1?'s':''} selected · Total ₹${total}`;
    } else {
        btn.classList.remove('bms-proceed-active');
        btn.classList.add('bms-proceed-disabled');
        hint.textContent = 'Please select at least 1 seat to continue';
    }
}

// ── Proceed to Payment ──
function proceedToPayment() {
    const name  = document.getElementById('bookingName').value.trim();
    const phone = document.getElementById('bookingPhone').value.trim();
    const email = document.getElementById('bookingEmail').value.trim();

    if (!name)  { showToast('Please enter your full name.', 'error');  document.getElementById('bookingName').focus();  return; }
    if (!phone || phone.length < 10) { showToast('Please enter a valid 10-digit mobile number.', 'error'); document.getElementById('bookingPhone').focus(); return; }
    if (!email || !email.includes('@')) { showToast('Please enter a valid email address.', 'error'); document.getElementById('bookingEmail').focus(); return; }
    if (selectedSeats.length === 0) { showToast('Please select at least one seat.', 'error'); return; }
    if (!selectedScreen || !selectedDate || !selectedTime) { showToast('Please select screen, date and time.', 'error'); return; }

    const count = selectedSeats.length;
    const base  = count * BASE_PRICE;
    const gst   = Math.round(base * GST_RATE);
    const total = base + gst;

    // Fill hidden form
    document.getElementById('fName').value      = name;
    document.getElementById('fPhone').value     = phone;
    document.getElementById('fEmail').value     = email;
    document.getElementById('fScreen').value    = selectedScreen;
    document.getElementById('fDate').value      = selectedDate;
    document.getElementById('fTime').value      = selectedTime;
    document.getElementById('fSeats').value     = selectedSeats.join(',');
    document.getElementById('fSeatCount').value = count;
    document.getElementById('fAmount').value    = total;

    // Show payment loading
    const btn = document.getElementById('proceedBtn');
    btn.textContent = '⏳ Processing Payment…';
    btn.classList.add('bms-proceed-loading');
    btn.disabled = true;

    document.getElementById('bookingForm').submit();
}

// ── Toast Notification ──
function showToast(msg, type = 'info') {
    let toast = document.getElementById('bmsToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'bmsToast';
        document.body.appendChild(toast);
    }
    toast.className = `bms-toast bms-toast-${type} bms-toast-show`;
    toast.textContent = msg;
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => toast.classList.remove('bms-toast-show'), 3200);
}

// ── Show Success Popup (called after redirect with booking_id param) ──
function showSuccessPopup(bookingId, seatsStr) {
    document.getElementById('popupRef').textContent   = `#${String(bookingId).padStart(6,'0')}`;
    document.getElementById('popupSub').textContent   = `Seats ${seatsStr} confirmed!`;
    document.getElementById('downloadLink').href      = `download_ticket.php?booking_id=${bookingId}`;
    document.getElementById('popupOverlay').classList.add('bms-overlay-show');
}

// ── On Page Load ──
document.addEventListener('DOMContentLoaded', () => {
    updateSummaryShow();
    refreshSeats();

    // Check if redirected back with success
    const params = new URLSearchParams(window.location.search);
    if (params.get('success') === '1') {
        const bookingId = params.get('booking_id') || '0';
        const seatsStr  = params.get('seats')      || '';
        showSuccessPopup(bookingId, decodeURIComponent(seatsStr));
    }
});

// Close popup on overlay click
document.getElementById('popupOverlay').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('bms-overlay-show');
});
</script>

</body>
</html>
