// /home/ri309/new-app/frontend/next.config.ts
import type { NextConfig } from "next";

const backendOrigin =
  process.env.NEXT_PUBLIC_BACKEND_ORIGIN?.replace(/\/$/, "") || "http://localhost";

const nextConfig: NextConfig = {
  async rewrites() {
    return [
      // Next(3000) -> Laravel(nginx:80) へ転送
      { source: "/api/:path*", destination: `${backendOrigin}/api/:path*` },

      // ✅ Sanctum CSRF cookie（これが無いと POST 前の csrf-cookie が 404 になる）
      { source: "/sanctum/:path*", destination: `${backendOrigin}/sanctum/:path*` },
    ];
  },
};

export default nextConfig;
