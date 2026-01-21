// /home/ri309/new-app/frontend/src/lib/apiFormData.ts

import { ensureCsrfCookie } from "./apiFetch";

type ApiFormDataOptions = Omit<RequestInit, "body"> & {
    /** FormData を送る */
    body: FormData;
    /** true: POST/PUT/PATCH/DELETE の前に必ず CSRF Cookie を確保する（デフォルト: methodに応じて自動） */
    ensureCsrf?: boolean;
};

/** Cookie から XSRF-TOKEN を取り出す（Sanctum SPA用） */
function getCookie(name: string): string {
    if (typeof document === "undefined") return "";
    const m = document.cookie.match(
        new RegExp(
            "(^|; )" +
            name.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&") +
            "=([^;]*)"
        )
    );
    return m ? m[2] : "";
}

/**
 * FormData 専用 fetch（Laravel web middleware + Sanctum(cookie) 前提）
 * - Content-Type は絶対にセットしない（boundary はブラウザに任せる）
 * - XSRF-TOKEN cookie を X-XSRF-TOKEN に詰める（axios相当を自前で）
 * - credentials: "include" 必須
 */
export async function apiFormData(input: string, options: ApiFormDataOptions) {
    const method = (options.method || "POST").toUpperCase();
    const needsCsrf = ["POST", "PUT", "PATCH", "DELETE"].includes(method);
    const ensureCsrf = options.ensureCsrf ?? needsCsrf;

    // 変更系は事前に Sanctum の CSRF Cookie を確保
    if (ensureCsrf && needsCsrf) {
        await ensureCsrfCookie();
    }

    const headers = new Headers(options.headers || {});
    headers.set("Accept", "application/json");
    headers.set("X-Requested-With", "XMLHttpRequest");

    // ✅ FormData 送信では Content-Type を付けない（付けると boundary が欠けて壊れる）
    headers.delete("Content-Type");

    // ✅ fetch は自動で X-XSRF-TOKEN を付けないので自前で付ける
    if (needsCsrf) {
        const xsrf = getCookie("XSRF-TOKEN"); // URL-encoded のことがある
        if (xsrf) {
            headers.set("X-XSRF-TOKEN", decodeURIComponent(xsrf));
        }
    }

    return fetch(input, {
        ...options,
        method,
        headers,
        body: options.body,
        credentials: "include",
    });
}

/**
 * JSON を返す FormData API 用ヘルパ（エラー時は message / errors を拾って投げる）
 */
export async function apiFormDataJson<T>(
    input: string,
    options: ApiFormDataOptions
): Promise<T> {
    const res = await apiFormData(input, options);

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
