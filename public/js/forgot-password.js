document.addEventListener("DOMContentLoaded", () => {
    const alertBox = document.getElementById("forgotAlert");
    const countdown = document.getElementById("otpCountdown");
    const sendButton = document.getElementById("sendCode");
    let countdownInterval = null;

    const show = (message, success = false) => {
        alertBox.textContent = message;
        alertBox.className = "alert " + (success ? "alert-success" : "alert-danger");
    };

    const startCountdown = (seconds = 60) => {
        if (countdownInterval) clearInterval(countdownInterval);

        let remaining = seconds;
        countdown.classList.remove("d-none");
        sendButton.disabled = true;

        const render = () => {
            const minutes = Math.floor(remaining / 60);
            const secondsLeft = remaining % 60;
            countdown.textContent = remaining > 0
                ? `Mã OTP còn hiệu lực: ${String(minutes).padStart(2, "0")}:${String(secondsLeft).padStart(2, "0")}`
                : "Mã OTP đã hết hạn. Vui lòng gửi mã mới.";
            countdown.classList.toggle("text-danger", remaining === 0);
        };

        render();
        countdownInterval = setInterval(() => {
            remaining -= 1;
            render();

            if (remaining <= 0) {
                clearInterval(countdownInterval);
                countdownInterval = null;
                sendButton.disabled = false;
            }
        }, 1000);
    };

    document.getElementById("forgotForm").addEventListener("submit", async (e) => {
        e.preventDefault();
        sendButton.disabled = true;

        try {
            const response = await fetch("/api/v1/auth/forgot-password", {
                method: "POST",
                headers: { Accept: "application/json", "Content-Type": "application/json" },
                body: JSON.stringify({ email: document.getElementById("email").value.trim() })
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || "Không gửi được mã xác nhận.");

            show(result.message, true);
            startCountdown(60);
        } catch (error) {
            show(error.message);
            sendButton.disabled = false;
        }
    });

    document.getElementById("resetForm").addEventListener("submit", async (e) => {
        e.preventDefault();
        const password = document.getElementById("newPassword").value;
        const confirmation = document.getElementById("confirmPassword").value;
        if (password !== confirmation) return show("Mật khẩu nhập lại không khớp.");

        try {
            const response = await fetch("/api/v1/auth/reset-password", {
                method: "POST",
                headers: { Accept: "application/json", "Content-Type": "application/json" },
                body: JSON.stringify({
                    email: document.getElementById("email").value.trim(),
                    code: document.getElementById("code").value.trim(),
                    mat_khau_moi: password,
                    mat_khau_moi_confirmation: confirmation
                })
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || "Không thể đặt lại mật khẩu.");

            if (countdownInterval) clearInterval(countdownInterval);
            countdown.classList.add("d-none");
            show(result.message + " Đang chuyển về trang đăng nhập...", true);
            setTimeout(() => window.location.href = "/login", 1500);
        } catch (error) {
            show(error.message);
        }
    });
});
