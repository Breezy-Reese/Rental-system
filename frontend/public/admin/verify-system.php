<?php

/**
 * ============================================================
 * PropertyPro - Admin: System Verification (OTP)
 * ============================================================
 */

require_once __DIR__ . '/../../includes/api.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin();

$pageTitle = 'Verify Access';

/*
|--------------------------------------------------------------------------
| Handle submitted OTP
|--------------------------------------------------------------------------
*/

$error = '';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['otp'])
) {
    $otp = preg_replace('/\D/', '', (string)$_POST['otp']);

    if (strlen($otp) !== 4) {
        $error = 'Enter the 4-digit code.';
    } else {
        try {
            $response = api_post(
                '/system/verify-otp',
                ['code' => $otp]
            );

            $status = (int)($response['status'] ?? 0);

            if ($status >= 200 && $status < 300) {

                $_SESSION['system_verified_at'] = time();

                $target = $_SESSION['system_after_verify'] ?? 'system.php';
                unset($_SESSION['system_after_verify']);

                $allowed = ['system.php', 'health.php', 'database.php'];

                if (!in_array($target, $allowed, true)) {
                    $target = 'system.php';
                }

                header('Location: ' . $target);
                exit;
            }

            $error = $response['message']
                ?? 'Verification failed. Please try again.';

        } catch (Throwable $e) {

            error_log('verify-system error: ' . $e->getMessage());
            $error = 'Unable to reach verification service.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| On GET, auto-request a fresh code
|--------------------------------------------------------------------------
*/

$sendOk  = false;
$sendMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    try {
        $sendResponse = api_post('/system/send-otp', []);
        $sendStatus   = (int)($sendResponse['status'] ?? 0);

        if ($sendStatus >= 200 && $sendStatus < 300) {
            $sendOk  = true;
            $sendMsg = $sendResponse['message']
                ?? 'Verification code sent.';
        } else {
            $sendMsg = $sendResponse['message']
                ?? 'Could not send verification code.';
        }
    } catch (Throwable $e) {
        error_log('send-otp error: ' . $e->getMessage());
        $sendMsg = 'Could not reach verification service.';
    }
}

function vse($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Access | PropertyPro</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        .bg-orbs::before,
        .bg-orbs::after {
            content: "";
            position: absolute;
            border-radius: 9999px;
            filter: blur(120px);
            opacity: 0.55;
            pointer-events: none;
        }
        .bg-orbs::before {
            width: 520px;
            height: 520px;
            background: radial-gradient(circle, #f59e0b 0%, transparent 70%);
            top: -120px;
            left: -140px;
        }
        .bg-orbs::after {
            width: 620px;
            height: 620px;
            background: radial-gradient(circle, #7c3aed 0%, transparent 70%);
            bottom: -180px;
            right: -180px;
        }

        .glass-card {
            background: linear-gradient(
                145deg,
                rgba(255, 255, 255, 0.08),
                rgba(255, 255, 255, 0.02)
            );
            backdrop-filter: blur(28px) saturate(160%);
            -webkit-backdrop-filter: blur(28px) saturate(160%);
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow:
                0 30px 80px -20px rgba(0, 0, 0, 0.7),
                inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }

        .otp-box {
            width: 64px;
            height: 72px;
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            color: #fff;
            background: linear-gradient(
                160deg,
                rgba(255,255,255,0.10),
                rgba(255,255,255,0.03)
            );
            border: 1.5px solid rgba(245, 158, 11, 0.35);
            border-radius: 16px;
            outline: none;
            transition: all 0.18s ease;
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.06),
                0 6px 20px -8px rgba(245,158,11,0.35);
        }
        .otp-box:focus {
            border-color: #f59e0b;
            box-shadow:
                0 0 0 4px rgba(245, 158, 11, 0.18),
                inset 0 1px 0 rgba(255,255,255,0.08),
                0 8px 30px -8px rgba(245,158,11,0.6);
            transform: translateY(-2px);
        }
        .otp-box:not(:placeholder-shown) {
            border-color: rgba(245, 158, 11, 0.85);
        }

        .btn-amber {
            background: linear-gradient(180deg, #fbbf24, #f59e0b);
            color: #1c1917;
            box-shadow:
                0 10px 30px -10px rgba(245, 158, 11, 0.55),
                inset 0 1px 0 rgba(255,255,255,0.35);
            transition: all 0.18s ease;
        }
        .btn-amber:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow:
                0 14px 36px -10px rgba(245, 158, 11, 0.7),
                inset 0 1px 0 rgba(255,255,255,0.45);
        }
        .btn-amber:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(28, 25, 23, 0.35);
            border-top-color: #1c1917;
            border-radius: 9999px;
            animation: spin 0.7s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>

<body class="relative min-h-screen overflow-hidden bg-[#0a0a0f] text-white">

    <div class="bg-orbs fixed inset-0"></div>

    <div
        class="pointer-events-none fixed inset-0 opacity-[0.04]"
        style="background-image:radial-gradient(rgba(255,255,255,0.9) 1px, transparent 1px);background-size:3px 3px;"
    ></div>

    <main class="relative z-10 flex min-h-screen items-center justify-center px-4 py-10">

        <div class="glass-card w-full max-w-md rounded-3xl p-8 sm:p-10">

            <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl border border-amber-400/30 bg-amber-400/10 shadow-inner">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>

            <h1 class="text-center text-3xl font-bold tracking-tight">
                Verify <span class="text-amber-400">OTP</span>
            </h1>

            <p class="mt-3 text-center text-sm text-slate-400">
                Enter the 4-digit security code sent to your device
            </p>

            <?php if ($error !== ''): ?>
                <div class="mt-5 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">
                    <?= vse($error) ?>
                </div>
            <?php elseif ($sendOk && $sendMsg !== ''): ?>
                <div class="mt-5 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
                    <?= vse($sendMsg) ?>
                </div>
            <?php elseif ($sendMsg !== ''): ?>
                <div class="mt-5 rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-300">
                    <?= vse($sendMsg) ?>
                </div>
            <?php endif; ?>

            <form id="otpForm" method="POST" action="verify-system.php" class="mt-7">

                <input type="hidden" name="otp" id="otpHidden" value="">

                <div class="flex items-center justify-center gap-3 sm:gap-4">
                    <?php for ($i = 0; $i < 4; $i++): ?>
                        <input
                            type="text"
                            inputmode="numeric"
                            maxlength="1"
                            autocomplete="one-time-code"
                            class="otp-box"
                            data-index="<?= $i ?>"
                            placeholder=" "
                        >
                    <?php endfor; ?>
                </div>

                <p id="resendText" class="mt-6 text-center text-xs text-slate-400">
                    Didn't receive the code?
                    <span class="text-slate-300">Resend in </span>
                    <span id="countdown" class="font-mono text-amber-400">00:36</span>
                </p>

                <button
                    type="submit"
                    id="submitBtn"
                    disabled
                    class="btn-amber mt-7 flex w-full items-center justify-center gap-3 rounded-2xl px-6 py-4 text-sm font-bold uppercase tracking-wider"
                >
                    <span id="btnIdle" class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Verify Code
                    </span>

                    <span id="btnBusy" class="hidden items-center gap-2">
                        <span class="spinner"></span>
                        Verifying Code...
                    </span>
                </button>

            </form>

            <p class="mt-6 text-center text-xs text-slate-500">
                This code expires in 5 minutes.
            </p>

        </div>

    </main>

<script>
(function () {

    const form   = document.getElementById('otpForm');
    const hidden = document.getElementById('otpHidden');
    const boxes  = Array.from(document.querySelectorAll('.otp-box'));
    const btn    = document.getElementById('submitBtn');
    const idle   = document.getElementById('btnIdle');
    const busy   = document.getElementById('btnBusy');

    boxes[0]?.focus();

    function refresh() {
        const value = boxes.map(b => b.value).join('');
        hidden.value = value;
        btn.disabled = value.length !== 4;
    }

    boxes.forEach((box, i) => {

        box.addEventListener('input', (e) => {
            let v = e.target.value.replace(/\D/g, '');

            if (v.length > 1) {
                const chars = v.split('');
                chars.forEach((c, k) => {
                    if (boxes[i + k]) boxes[i + k].value = c;
                });
                const last = Math.min(i + chars.length, boxes.length - 1);
                boxes[last].focus();
            } else {
                e.target.value = v;
                if (v && i < boxes.length - 1) boxes[i + 1].focus();
            }

            refresh();
            autoSubmit();
        });

        box.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !box.value && i > 0) {
                boxes[i - 1].focus();
                boxes[i - 1].value = '';
                refresh();
            }
            if (e.key === 'ArrowLeft' && i > 0) boxes[i - 1].focus();
            if (e.key === 'ArrowRight' && i < boxes.length - 1) boxes[i + 1].focus();
        });

        box.addEventListener('paste', (e) => {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData)
                .getData('text')
                .replace(/\D/g, '')
                .slice(0, 4);

            text.split('').forEach((c, k) => {
                if (boxes[k]) boxes[k].value = c;
            });

            refresh();
            autoSubmit();
        });
    });

    let submitted = false;

    function autoSubmit() {
        if (submitted) return;
        if (hidden.value.length !== 4) return;

        submitted = true;

        idle.classList.add('hidden');
        busy.classList.remove('hidden');
        busy.classList.add('flex');
        btn.disabled = true;

        setTimeout(() => form.submit(), 250);
    }

    let seconds = 36;
    const countdown = document.getElementById('countdown');
    const resendText = document.getElementById('resendText');

    const timer = setInterval(() => {
        seconds--;
        if (seconds <= 0) {
            clearInterval(timer);
            resendText.innerHTML =
                'Didn\'t receive the code? ' +
                '<a href="verify-system.php" class="text-amber-400 underline">Resend</a>';
            return;
        }
        const mm = String(Math.floor(seconds / 60)).padStart(2, '0');
        const ss = String(seconds % 60).padStart(2, '0');
        countdown.textContent = mm + ':' + ss;
    }, 1000);

})();
</script>

</body>
</html>