import { onBeforeUnmount, onMounted } from "vue";

export function usePageTracker(options = {}) {
    let visitId = null;
    let activeSeconds = 0;
    let interval = null;
    let interactionsCount = 0;
    let hasStarted = false;

    const csrfToken = () =>
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content") || "";

    const generateId = () => {
        if (window.crypto?.randomUUID) {
            return window.crypto.randomUUID();
        }

        return `id-${Date.now()}-${Math.random().toString(16).slice(2)}`;
    };

    const getVisitorId = () => {
        const key = "wgm_visitor_id";
        let value = localStorage.getItem(key);

        if (!value) {
            value = generateId();
            localStorage.setItem(key, value);
        }

        return value;
    };

    const getSessionId = () => {
        const key = "wgm_session_id";
        const lastActivityKey = "wgm_session_last_activity";
        const now = Date.now();
        const thirtyMinutes = 30 * 60 * 1000;

        const existingSessionId = sessionStorage.getItem(key);
        const lastActivity = Number(
            sessionStorage.getItem(lastActivityKey) || 0,
        );

        if (
            existingSessionId &&
            lastActivity &&
            now - lastActivity <= thirtyMinutes
        ) {
            sessionStorage.setItem(lastActivityKey, String(now));
            return existingSessionId;
        }

        const newSessionId = generateId();

        sessionStorage.setItem(key, newSessionId);
        sessionStorage.setItem(lastActivityKey, String(now));

        return newSessionId;
    };

    const getDevice = () => {
        const width = window.innerWidth || 0;
        const userAgent = navigator.userAgent || "";

        if (/Mobi|Android|iPhone|iPad|iPod/i.test(userAgent) || width < 768) {
            return "mobile";
        }

        return "desktop";
    };

    const getPagePath = () => {
        return options.pagePath || window.location.pathname || "/";
    };

    const getPageType = () => {
        return options.pageType || "page";
    };

    const startTimer = () => {
        if (interval) return;

        interval = window.setInterval(() => {
            if (document.visibilityState === "visible") {
                activeSeconds += 1;
            }
        }, 1000);
    };

    const stopTimer = () => {
        if (!interval) return;

        window.clearInterval(interval);
        interval = null;
    };

    const sendPing = () => {
        if (!visitId) return;

        const payload = {
            _token: csrfToken(),
            visit_id: visitId,
            duration_seconds: activeSeconds,
            interactions_count: interactionsCount,
        };

        const jsonPayload = JSON.stringify(payload);

        if (navigator.sendBeacon) {
            const blob = new Blob([jsonPayload], {
                type: "application/json",
            });

            navigator.sendBeacon("/analytics/visit/ping", blob);
            return;
        }

        fetch("/analytics/visit/ping", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken(),
                "X-Requested-With": "XMLHttpRequest",
            },
            body: jsonPayload,
            keepalive: true,
        }).catch(() => {});
    };

    const startVisit = async () => {
        if (hasStarted) return;

        hasStarted = true;

        try {
            const response = await fetch("/analytics/visit/start", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken(),
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: JSON.stringify({
                    visitor_id: getVisitorId(),
                    session_id: getSessionId(),
                    page_path: getPagePath(),
                    page_url: window.location.href,
                    page_title: options.pageTitle || document.title,
                    page_type: getPageType(),
                    referrer: document.referrer || null,
                    device: getDevice(),
                }),
            });

            const data = await response.json();

            if (data?.ignored) {
                return;
            }

            visitId = data.visit_id;
            activeSeconds = Number(data.baseline_duration_seconds || 0);

            startTimer();

            window.setTimeout(sendPing, 5500);
        } catch (error) {
            console.warn("Analytics visit start failed.", error);
        }
    };

    const trackInteraction = () => {
        interactionsCount += 1;
    };

    const handleVisibilityChange = () => {
        if (document.visibilityState === "visible") {
            startTimer();
            return;
        }

        stopTimer();
        sendPing();
    };

    const handlePageHide = () => {
        stopTimer();
        sendPing();
    };

    onMounted(() => {
        startVisit();

        window.addEventListener("click", trackInteraction, { passive: true });
        window.addEventListener("scroll", trackInteraction, {
            passive: true,
            once: true,
        });

        document.addEventListener("visibilitychange", handleVisibilityChange);
        window.addEventListener("pagehide", handlePageHide);

        window.setInterval(() => {
            sessionStorage.setItem(
                "wgm_session_last_activity",
                String(Date.now()),
            );

            sendPing();
        }, 15000);
    });

    onBeforeUnmount(() => {
        stopTimer();
        sendPing();

        window.removeEventListener("click", trackInteraction);
        document.removeEventListener(
            "visibilitychange",
            handleVisibilityChange,
        );
        window.removeEventListener("pagehide", handlePageHide);
    });
}