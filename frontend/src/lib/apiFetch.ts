// /home/ri309/new-app/frontend/src/lib/apiFetch.ts
type ApiFetchOptions = RequestInit & {
    /** true: POST/PUT/PATCH/DELETE 前に /sanctum/csrf-cookie を叩く */
    ensureCsrf?: boolean;
};

function getCookie(name: string): string {
    if (typeof document === "undefined") return "";
    const m = document.cookie.match(new RegExp("(^|; )" + name.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&") + "=([^;]*)"));
    return m ? m[2] : "";
}

/**
 * Sanctum(SPA cookie) の CSRF Cookie をセットする
 * - Laravel: GET /sanctum/csrf-cookie
 * - credentials: "include" 必須
 */
export async function ensureCsrfCookie(): Promise<void> {
    const res = await fetch("/sanctum/csrf-cookie", {
        method: "GET",
        credentials: "include",
        headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
        },
    });

    if (!res.ok) {
        const text = await res.text().catch(() => "");
        throw new Error(`CSRF cookie fetch failed: ${res.status} ${text}`);
    }
}

function isFormLikeBody(body: RequestInit["body"]): boolean {
    return (
        typeof FormData !== "undefined" && body instanceof FormData ||
        typeof URLSearchParams !== "undefined" && body instanceof URLSearchParams ||
        typeof Blob !== "undefined" && body instanceof Blob
    );
}

/**
 * Laravel(web middleware) + Sanctum(cookie) 前提の API fetch
 * - cookieベース認証 + CSRF（XSRF-TOKEN cookie -> X-XSRF-TOKEN header）
 * - JSON APIとして扱えるよう Accept / X-Requested-With を付与
 */
export async function apiFetch(input: string, options: ApiFetchOptions = {}) {
    const method = (options.method || "GET").toUpperCase();
    const needsCsrf = ["POST", "PUT", "PATCH", "DELETE"].includes(method);
    const ensureCsrf = options.ensureCsrf ?? needsCsrf;

    // 変更系は事前に csrf-cookie を確保
    if (ensureCsrf && needsCsrf) {
        await ensureCsrfCookie();
    }

    const headers = new Headers(options.headers || {});
    headers.set("Accept", "application/json");
    headers.set("X-Requested-With", "XMLHttpRequest");

    // fetch は axios みたいに自動で X-XSRF-TOKEN を付けないので自前で付ける
    if (needsCsrf) {
        const xsrf = getCookie("XSRF-TOKEN"); // URL-encoded
        if (xsrf) {
            headers.set("X-XSRF-TOKEN", decodeURIComponent(xsrf));
        }
    }

    // body が “JSON文字列” なら Content-Type を付ける（FormData は付けない）
    const body = options.body;
    if (body && !headers.has("Content-Type") && !isFormLikeBody(body)) {
        headers.set("Content-Type", "application/json");
    }

    return fetch(input, {
        ...options,
        method,
        headers,
        credentials: "include",
    });
}

/**
 * JSONを返すAPI用ヘルパ（エラー時は message / errors を拾って投げる）
 */
export async function apiJson<T>(input: string, options: ApiFetchOptions = {}): Promise<T> {
    const res = await apiFetch(input, options);

    if (res.ok) {
        return (await res.json()) as T;
    }

    const data = (await res.json().catch(() => null)) as any;
    const msg =
        data?.message ||
        (data?.errors ? Object.values(data.errors).flat().join("\n") : "") ||
        `Request failed: ${res.status}`;

    const err = new Error(msg);
    (err as any).status = res.status;
    (err as any).data = data;
    throw err;
}
