# ✅ Dockerfile CORRECTO para el frontend
FROM node:20-alpine AS builder
WORKDIR /app
# Instalar pnpm (tu proyecto usa pnpm@11.1.3)
RUN corepack enable && corepack prepare pnpm@11.1.3 --activate
COPY lab-dashboard/package.json lab-dashboard/pnpm-lock.yaml ./
RUN pnpm install --frozen-lockfile
COPY lab-dashboard/ ./
RUN pnpm run build

FROM node:20-alpine AS runner
WORKDIR /app
COPY --from=builder /app/.output/ ./.output/
EXPOSE 3000
ENV PORT=3000
ENV HOST=0.0.0.0
CMD ["node", ".output/server/index.mjs"]
